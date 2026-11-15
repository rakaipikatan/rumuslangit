# Panduan Membangun Pembayaran QRIS (SumoPod Managed Payment) di Laravel

Dokumen ini adalah cetak biru yang bisa dipakai ulang di proyek lain. Isinya disarikan dari implementasi yang **sudah berjalan di produksi** (Rumus Langit, Laravel 13, PostgreSQL, Redis/Horizon) dan sudah teruji dengan transaksi Live sungguhan. Kode di bawah sengaja dibuat generik (`Order`, `item`), bukan khusus Rumus Langit.

> **Penting sebelum memakai:** bentuk permintaan/respons API dan nama event di bawah berasal dari implementasi yang bekerja per 15 Nov 2026 dan dari dokumentasi di dashboard SumoPod (menu *Managed Payment → Quick Start*). Selalu cocokkan dengan dokumentasi SumoPod terbaru sebelum merilis. Dokumen ini **tidak memuat rahasia apa pun**: semua kunci memakai placeholder.

---

## 1. Ringkasan

| Hal | Isi |
|---|---|
| Metode bayar | **QRIS saja** (satu QR bisa dibayar dari semua m-banking/e-wallet Indonesia) |
| Penyedia | SumoPod Managed Payment (REST API + webhook bertanda tangan **Svix**) |
| Pola | Buat *order* lokal → minta *payment* ke API → arahkan pembeli ke link bayar → **webhook** melunasi order → pembeli kembali ke halaman "selesai" yang menunggu konfirmasi |
| Prinsip utama | Satu tempat pelunasan, **idempoten**, webhook **diverifikasi**, nominal **dicocokkan**, bukti bayar lewat **antrean** |
| Biaya (acuan) | 0,7% + Rp300 per transaksi QRIS, dipotong dari saldo merchant; dana cair T+2 (cek tarif terbaru di dashboard) |
| Batas | Minimum nominal **Rp1.000**; masa berlaku link maksimum **24 jam** |

### Diagram alur

```
Pembeli                Aplikasi Anda                         SumoPod
  │  klik "Bayar"          │                                    │
  ├───────────────────────►│ 1. cari/buat Order (pending)       │
  │                        │ 2. POST /api/v1/payments ─────────►│
  │                        │◄──── payment_id + payment_link_url │
  │◄─ redirect ke link ────│ 3. simpan payment_id & link        │
  │  scan & bayar QRIS ───────────────────────────────────────► │
  │                        │◄── webhook payment.completed ──────│ (bertanda tangan)
  │                        │ 4. verifikasi signature            │
  │                        │ 5. cocokkan nominal                │
  │                        │ 6. settle() idempoten → akses aktif│
  │                        │ 7. antre email bukti bayar         │
  │◄─ redirect return URL ─│ 8. halaman "selesai" polling status│
  │  (otomatis masuk produk)                                    │
```

**Ada dua jalur konfirmasi** dan keduanya harus benar: *webhook* (sumber kebenaran) dan *halaman selesai* (hanya membaca status order di database, tidak pernah melunasi sendiri).

---

## 2. Prasyarat di SumoPod

1. Akun SumoPod dengan **Managed Payment** aktif.
2. **Dua pasang kunci**: *Sandbox* (uji) dan *Live* (uang nyata). Keduanya punya **host berbeda**:
   - Sandbox: `https://api-pay-sandbox.sumopod.com`
   - Live: `https://api-pay.sumopod.com`
3. **API Key** (dikirim lewat header `X-Api-Key`) dan **Webhook Secret** berawalan `whsec_…`.
4. **Webhook endpoint** di dashboard: `https://domainanda.com/webhook/sumopod`, dengan event: `payment.completed`, `payment.failed`, `payment.expired` (dan opsional `payment.test` untuk tombol uji).
5. (Opsional) *Success/Cancel Return URL* bawaan di dashboard; aplikasi juga boleh mengirim URL sendiri per payment (lihat §6).

> **Jebakan yang pernah terjadi:** memakai API key **Sandbox** pada host **Live** (atau sebaliknya) menghasilkan **HTTP 401**. Pasangan key dan host harus sama.

---

## 3. Konfigurasi

`.env` (jangan di-commit):

```dotenv
SUMOPOD_PAY_BASE_URL=https://api-pay-sandbox.sumopod.com   # ganti ke https://api-pay.sumopod.com untuk Live
SUMOPOD_PAY_API_KEY=isi_dari_dashboard
SUMOPOD_PAY_WEBHOOK_SECRET=whsec_isi_dari_dashboard
PAYMENT_EXPIRE_HOURS=24
```

`config/services.php`:

```php
'sumopod_pay' => [
    'base_url'       => env('SUMOPOD_PAY_BASE_URL', 'https://api-pay.sumopod.com'),
    'api_key'        => env('SUMOPOD_PAY_API_KEY'),
    'webhook_secret' => env('SUMOPOD_PAY_WEBHOOK_SECRET'),
],
```

`config/payment.php` (harga dan aturan bisnis dipusatkan di sini, bukan di controller):

```php
return [
    'expire_hours' => env('PAYMENT_EXPIRE_HOURS', 24), // maks. 24 jam di sisi SumoPod
    // contoh harga paket:
    'harga_bulanan' => env('PAYMENT_HARGA_BULANAN', 59000),
    'harga_tahunan' => env('PAYMENT_HARGA_TAHUNAN', 499000),
];
```

> Setelah mengubah `.env` di server yang memakai cache: jalankan `php artisan config:cache`.

---

## 4. Database

Satu tabel `orders` cukup. Kolom penting dan **alasannya**:

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('item', 50);                       // kode produk: id fitur / 'langganan-bulanan' / dst.
    $table->integer('amount');                        // rupiah, integer (tanpa desimal)
    $table->string('payment_method', 30)->nullable(); // 'qris' | 'komplimen' (akses gratis dari admin)
    $table->string('gateway_order_id', 100)->unique();// ID order Anda yang dikirim ke SumoPod (mis. RL-AB12CD34EF56)
    $table->string('payment_id', 64)->nullable()->unique(); // ID payment dari SumoPod
    $table->text('payment_link_url')->nullable();     // link bayar (QR) dari SumoPod
    $table->timestamp('payment_expires_at')->nullable();
    $table->string('status', 20)->default('pending'); // pending | settlement | cancel | expire
    $table->timestamp('settled_at')->nullable();
    $table->text('catatan')->nullable();              // catatan admin (mis. "transaksi uji", "komplimen")
    $table->timestamps();
    $table->index(['user_id', 'item', 'status']);
});
```

**Mesin status** (satu arah, tidak boleh mundur):

```
pending ──► settlement   (webhook payment.completed + nominal cocok)
pending ──► cancel       (webhook payment.failed, atau API gagal saat membuat payment)
pending ──► expire       (webhook payment.expired, atau link sudah lewat masa berlaku saat dibuka lagi)
```

Hanya order `pending` yang boleh diubah. Itu yang membuat webhook ganda aman.

Model:

```php
class Order extends Model
{
    protected $fillable = ['user_id','item','amount','payment_method','gateway_order_id',
        'payment_id','payment_link_url','payment_expires_at','status','settled_at','catatan'];
    protected $casts = ['settled_at' => 'datetime', 'payment_expires_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function isSettled(): bool { return $this->status === 'settlement'; }
    public function isPending(): bool { return $this->status === 'pending'; }
}
```

> Simpan nominal sebagai **integer rupiah**. Jangan float.

---

## 5. Rute

```php
// Halaman pembeli
Route::get ('/payment/{item}',                 [PaymentController::class, 'konfirmasi'])->name('payment.konfirmasi');
Route::post('/payment/{item}',                 [PaymentController::class, 'proses'])->name('payment.proses');
Route::get ('/payment/selesai/{gatewayOrderId}', [PaymentController::class, 'selesai'])->name('payment.selesai');

// Webhook (publik, tanpa CSRF, tanpa login — diamankan oleh signature)
Route::post('/webhook/sumopod', [SumopodWebhookController::class, 'handle'])->name('webhook.sumopod');
```

**Wajib: kecualikan webhook dari proteksi CSRF**, jika tidak, SumoPod akan menerima 419. Laravel 11+ di `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['webhook/sumopod']);
    // (Laravel 13 memakai nama preventRequestForgery(except: [...]) — sesuaikan versi Anda)
})
```

Urutan rute penting: `/payment/selesai/{id}` harus didaftarkan **sebelum** `/payment/{item}`, atau "selesai" akan dikira nilai `{item}`.

---

## 6. Klien API (`SumopodPayService`)

Dua tugas: membuat payment dan memverifikasi webhook.

```php
namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SumopodPayService
{
    private const TOLERANSI_DETIK = 300; // cegah replay webhook

    public function terkonfigurasi(): bool
    {
        return filled(config('services.sumopod_pay.api_key'));
    }

    public function buatPembayaran(Order $order, int $expiresInHours = 24): Order
    {
        if (! $this->terkonfigurasi()) {
            throw new RuntimeException('SumoPod Payment belum dikonfigurasi.');
        }

        $payload = [
            'order_id'                 => $order->gateway_order_id,
            'amount'                   => (int) $order->amount,
            'currency'                 => 'IDR',
            'expires_in_hours'         => min($expiresInHours, 24),
            'payment_method_type_code' => 'qris',
        ];

        // Arahkan pembeli kembali ke halaman yang tahu apa yang dibeli.
        $denganReturn = $payload + [
            'success_return_url' => route('payment.selesai', $order->gateway_order_id),
            'cancel_return_url'  => route('payment.konfirmasi', $order->item),
        ];

        $kirim = fn (array $body) => Http::timeout(20)
            ->acceptJson()
            ->withHeaders(['X-Api-Key' => config('services.sumopod_pay.api_key')])
            ->post(rtrim(config('services.sumopod_pay.base_url'), '/') . '/api/v1/payments', $body);

        $response = $kirim($denganReturn);

        // Bila API menolak override return URL (validasi 400/422), ulangi tanpa itu:
        // alamat bawaan di dashboard dipakai.
        if (in_array($response->status(), [400, 422], true)) {
            \Log::warning('SumoPod menolak return URL per-payment, ulangi tanpa override');
            $response = $kirim($payload);
        }

        if ($response->failed()) {
            throw new RuntimeException('SumoPod API ' . $response->status() . ': ' . substr($response->body(), 0, 300));
        }

        // Respons bisa di level atas atau di bawah "data": tangani keduanya.
        $paymentId = $response->json('payment_id') ?? $response->json('data.payment_id');
        $link      = $response->json('payment_link_url') ?? $response->json('data.payment_link_url');
        if (! $paymentId || ! $link) {
            throw new RuntimeException('Respons SumoPod tidak memuat payment_id / payment_link_url.');
        }

        $expiresAt = $response->json('expires_at') ?? $response->json('data.expires_at');

        $order->update([
            'payment_id'         => $paymentId,
            'payment_link_url'   => $link,
            'payment_expires_at' => $expiresAt ? \Carbon\Carbon::parse($expiresAt) : now()->addHours($expiresInHours),
        ]);

        return $order;
    }

    /**
     * Verifikasi tanda tangan webhook (standar Svix):
     * HMAC-SHA256 atas "{svix-id}.{svix-timestamp}.{rawBody}" memakai kunci = base64_decode(secret tanpa "whsec_").
     * Header svix-signature bisa memuat beberapa nilai "v1,<signature>" dipisah spasi.
     */
    public function verifikasiWebhook(string $rawBody, ?string $svixId, ?string $svixTimestamp, ?string $svixSignature): bool
    {
        $secret = (string) config('services.sumopod_pay.webhook_secret');

        if ($secret === '' || ! $svixId || ! $svixTimestamp || ! $svixSignature) {
            return false;
        }
        if (! ctype_digit($svixTimestamp) || abs(time() - (int) $svixTimestamp) > self::TOLERANSI_DETIK) {
            return false;
        }

        $kunci = base64_decode(str_replace('whsec_', '', $secret), true);
        if ($kunci === false || $kunci === '') {
            return false;
        }

        $diharapkan = base64_encode(hash_hmac('sha256', "{$svixId}.{$svixTimestamp}.{$rawBody}", $kunci, true));

        foreach (explode(' ', $svixSignature) as $bagian) {
            [, $sig] = array_pad(explode(',', $bagian, 2), 2, '');
            if ($sig !== '' && hash_equals($diharapkan, $sig)) { // hash_equals: perbandingan waktu-konstan
                return true;
            }
        }
        return false;
    }
}
```

Hal yang sering salah di sini:
- **Verifikasi memakai body MENTAH** (`$request->getContent()`), bukan hasil `json_decode` lalu di-encode ulang. Satu spasi berbeda membuat signature tidak cocok.
- Tolak timestamp yang selisihnya > 5 menit (anti-replay).
- Gunakan `hash_equals`, bukan `===`.

---

## 7. Checkout (`PaymentController@proses`)

Tujuan: membuat order sekali, meminta payment sekali, lalu mengarahkan ke link bayar. Aturan:

```php
public function proses(string $item, Request $request)
{
    $user = $this->userLogin();                      // wajib login/terautentikasi
    $harga = $this->hargaItem($item);                // dari config, BUKAN dari input pembeli
    $sumopod = app(SumopodPayService::class);

    if (! $sumopod->terkonfigurasi()) {
        return back()->withErrors(['metode' => 'Pembayaran QRIS belum tersedia.']);
    }

    // IDEMPOTENSI: pakai ulang order pending yang sama (klik dua kali tidak membuat payment ganda)
    $order = Order::where('user_id', $user->id)->where('item', $item)
        ->where('status', 'pending')->where('payment_method', 'qris')->latest()->first();

    // Link yang sudah lewat masa berlaku → tutup, buat baru
    if ($order && $order->payment_expires_at && $order->payment_expires_at->isPast()) {
        $order->update(['status' => 'expire']);
        $order = null;
    }

    $order ??= Order::create([
        'user_id'          => $user->id,
        'item'             => $item,
        'amount'           => $harga,
        'payment_method'   => 'qris',
        'gateway_order_id' => 'ORD-' . strtoupper(Str::random(12)), // unik, tak bisa ditebak
        'status'           => 'pending',
    ]);

    if (! $order->payment_link_url) {
        try {
            $sumopod->buatPembayaran($order, (int) config('payment.expire_hours', 24));
        } catch (\Throwable $e) {
            \Log::error('SumoPod: gagal membuat pembayaran', ['order' => $order->id, 'error' => $e->getMessage()]);
            $order->update(['status' => 'cancel']);       // jangan biarkan order yatim berstatus pending
            return back()->withErrors(['metode' => 'Gagal membuat pembayaran. Coba lagi.']);
        }
    }

    return redirect()->away($order->payment_link_url);   // ke halaman bayar SumoPod
}
```

Aturan emas checkout:
1. **Harga selalu dari server** (config/database), tidak pernah dari form.
2. **Jangan buat order untuk produk gratis (Rp0)**: SumoPod menolak nominal di bawah Rp1.000.
3. **Cegah beli ganda**: bila pembeli sudah punya akses/langganan aktif, jangan buat order baru (arahkan ke produk).
4. `gateway_order_id` harus **unik** dan jadi kunci pencarian saat webhook datang.

---

## 8. Webhook (`SumopodWebhookController`)

```php
public function handle(Request $request, SumopodPayService $sumopod, OrderSettlementService $settlement)
{
    $raw = $request->getContent();   // BODY MENTAH

    if (! $sumopod->verifikasiWebhook($raw, $request->header('svix-id'),
            $request->header('svix-timestamp'), $request->header('svix-signature'))) {
        \Log::warning('Webhook: signature tidak valid', ['ip' => $request->ip()]);
        return response('Invalid signature', 401);
    }

    $event = $request->json('event_type');
    $data  = (array) $request->json('data', []);

    if ($event === 'payment.test') {
        return response()->json(['ok' => true]);        // tombol "Test" di dashboard
    }

    $order = Order::where('payment_method', 'qris')
        ->where(fn ($q) => $q->where('payment_id', $data['payment_id'] ?? '__none__')
                              ->orWhere('gateway_order_id', $data['order_id'] ?? '__none__'))
        ->first();

    if (! $order) {
        // Tetap 200 agar SumoPod tidak mengulang terus; catat untuk ditelusuri.
        \Log::warning('Webhook: order tidak ditemukan', ['event' => $event]);
        return response()->json(['ok' => true, 'note' => 'order tidak ditemukan']);
    }

    switch ($event) {
        case 'payment.completed':
            // CEGAH settle dengan jumlah yang salah
            if ((int) ($data['amount'] ?? -1) !== (int) $order->amount) {
                \Log::error('Webhook: nominal tidak cocok', ['order' => $order->id]);
                break;                                   // tidak melunasi
            }
            $settlement->settle($order);
            break;
        case 'payment.failed':
            if ($order->status === 'pending') $order->update(['status' => 'cancel']);
            break;
        case 'payment.expired':
            if ($order->status === 'pending') $order->update(['status' => 'expire']);
            break;
    }

    return response()->json(['ok' => true]);              // balas 2xx SECEPAT mungkin (< 10 detik)
}
```

Kontrak yang harus dipenuhi:
| Aturan | Alasan |
|---|---|
| Signature tidak valid → **401** | Menolak pemalsu |
| Order tidak dikenal → **200** | Hindari pengulangan tanpa akhir oleh SumoPod |
| Nominal tidak cocok → **jangan lunasi** | Anti-manipulasi |
| Selalu balas **cepat** (2xx) | Pekerjaan berat (email, dll.) dikerjakan lewat **antrean**, bukan di request webhook |
| Event ganda aman | `settle()` idempoten (lihat §9) |

---

## 9. Pelunasan (`OrderSettlementService`) — satu-satunya pintu

Semua jalur (webhook, konfirmasi admin darurat) harus melewati satu fungsi ini.

```php
class OrderSettlementService
{
    /** @return bool true bila order BARU saja menjadi lunas */
    public function settle(Order $order): bool
    {
        $baruLunas = DB::transaction(function () use ($order) {
            // KUNCI BARIS: dua webhook bersamaan tidak bisa sama-sama lolos
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $order || $order->status !== 'pending') {
                return false;                       // sudah diproses → tidak melakukan apa-apa
            }

            $order->update(['status' => 'settlement', 'settled_at' => now()]);

            $this->berikanAkses($order);            // ← logika bisnis ANDA di sini
            return true;
        });

        // Setelah transaksi tersimpan, baru kirim bukti bayar lewat ANTREAN.
        // Kegagalan email tidak boleh membatalkan pelunasan.
        if ($baruLunas) {
            $this->kirimBuktiBayar($order->fresh('user'));
        }
        return $baruLunas;
    }
}
```

`berikanAkses()` adalah titik pemasangan logika produk Anda. Contoh yang dipakai di proyek asal:
- **Pembelian satuan:** akses/jatah dihitung dari jumlah order `settlement` (tidak perlu kolom tambahan).
- **Langganan:** buat record langganan; bila masih aktif, **perpanjang dari tanggal berakhir** (bukan dari hari ini), agar pembeli tidak rugi.
- **Komisi afiliasi (opsional):** `firstOrCreate` per order agar tidak ganda; abaikan order Rp0.

Prinsip: lakukan **semua perubahan database** di dalam transaksi, dan **semua efek samping eksternal** (email, notifikasi) **setelah** transaksi.

---

## 10. Halaman setelah bayar (pengalaman pembeli)

Masalah umum: pembeli sudah membayar tetapi halaman tidak berpindah sendiri → mengira gagal. Solusi: `success_return_url` mengarah ke halaman **"selesai"** yang menunggu status.

`GET /payment/selesai/{gatewayOrderId}`:
```php
$order = Order::where('gateway_order_id', $id)->where('user_id', $user->id)->firstOrFail(); // hanya milik sendiri

if ($request->expectsJson()) {                         // dipanggil polling
    return response()->json([
        'status'   => $order->status,
        'redirect' => $order->status === 'pending' ? null : route('payment.selesai', $id),
    ]);
}
if ($order->isSettled())                               return redirect()->to(/* langsung ke produk */)->with('success', '…');
if (in_array($order->status, ['cancel', 'expire']))    return redirect()->route('payment.konfirmasi', $order->item)->withErrors([...]);
return view('payment.selesai', compact('order'));      // masih pending → tampilkan "menunggu"
```

Halaman "menunggu" cukup JavaScript kecil yang memanggil URL yang sama (header `Accept: application/json`) tiap ±2,5 detik; saat `redirect` terisi, pindah. Setelah ±90 detik tampilkan pesan "lebih lama dari biasanya, jangan bayar dua kali, cek kembali sebentar lagi".

> Halaman ini **tidak pernah melunasi** order. Hanya membaca. Kebenaran ada di webhook.

---

## 11. Bukti bayar (email)

- Kirim lewat **antrean** (`Mail::to(...)->queue(...)`), dipicu hanya saat `settle()` mengembalikan `true` → satu email per order.
- Jalankan worker (Horizon/`queue:work`) di server; tanpa worker email menumpuk.
- Pembeli tanpa email tetap boleh dilunasi (jangan gagalkan pelunasan karena email).
- Catat kegagalan email ke log/status layanan agar terlihat.
- Beri tahu pembeli untuk **cek folder Spam** (email transaksional dari domain baru sering masuk spam).

---

## 12. Daftar keamanan (wajib)

- [ ] Signature webhook diverifikasi atas **body mentah**, dengan toleransi waktu ±5 menit dan `hash_equals`.
- [ ] Endpoint webhook dikecualikan dari CSRF, **tetapi** tidak mengubah apa pun sebelum signature lolos.
- [ ] Nominal webhook = nominal order (integer) sebelum melunasi.
- [ ] Harga dari server, bukan dari input pembeli.
- [ ] `settle()` idempoten dengan `lockForUpdate()` + cek `status === 'pending'`.
- [ ] Halaman "selesai" hanya menampilkan order milik pengguna yang sedang login (`user_id` dicocokkan).
- [ ] API key dan webhook secret hanya di `.env`; **jangan** di-commit, jangan dicetak ke log, jangan ditempel ke chat/tiket.
- [ ] Timeout HTTP ke SumoPod (±20 detik) dan penanganan galat yang tidak membocorkan isi respons ke pembeli.
- [ ] Order `pending` yang gagal dibuat di API → ditandai `cancel` (tidak menggantung).
- [ ] Panel admin untuk **konfirmasi manual** hanya sebagai cadangan (bila webhook terlambat), memakai `settle()` yang sama.

---

## 13. Pengujian

### 13.1 Strategi
Jangan menguji ke API sungguhan. Gunakan `Http::fake()` untuk API, dan buat **webhook palsu bertanda tangan benar** memakai rahasia uji:

```php
private function webhook(array $payload, ?string $secret = null): TestResponse
{
    $raw = json_encode($payload);
    $id  = 'msg_' . Str::random(8);
    $ts  = (string) time();
    $kunci = base64_decode(str_replace('whsec_', '', $secret ?? config('services.sumopod_pay.webhook_secret')));
    $sig = base64_encode(hash_hmac('sha256', "{$id}.{$ts}.{$raw}", $kunci, true));

    return $this->call('POST', '/webhook/sumopod', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_SVIX_ID' => $id, 'HTTP_SVIX_TIMESTAMP' => $ts, 'HTTP_SVIX_SIGNATURE' => "v1,{$sig}",
    ], $raw);
}
```

### 13.2 Kasus uji yang minimal harus ada
1. Checkout membuat payment dan mengarahkan ke link bayar.
2. Submit dua kali **tidak** membuat payment ganda.
3. API gagal → order `cancel` + pesan ramah.
4. API key kosong → ditolak dengan pesan.
5. Webhook `payment.completed` → order `settlement` + akses aktif.
6. **Webhook ganda** → tidak menggandakan akses/komisi/email.
7. Signature salah → 401. Tanpa header → 401. Timestamp kedaluwarsa → 401.
8. **Nominal tidak cocok** → tidak melunasi.
9. `payment.failed` / `payment.expired` → menutup order.
10. `payment.test` dan order tak dikenal → 200.
11. Halaman selesai: lunas → redirect ke produk; pending → halaman tunggu + JSON; gagal → kembali ke halaman bayar; milik pengguna lain → 404.
12. Bukti bayar diantrekan **sekali**; tidak dikirim untuk pembayaran gagal; pelunasan tetap berhasil untuk pengguna tanpa email.
13. Return URL ditolak API → diulang tanpa override.

### 13.3 Jebakan pengujian di Laravel
- Tes **jangan** menyentuh database produksi: pakai skema/database terpisah.
- Bila aplikasi memakai `config:cache`/`route:cache`, tes bisa memakai konfigurasi lama (gejalanya 419 atau rute tidak ditemukan): pastikan tes mengabaikan cache.
- Atur ulang konfigurasi "harga uji" di `setUp()` agar `.env` produksi tidak bocor ke hasil tes.

---

## 14. Dari Sandbox ke Live (prosedur rilis)

1. Selesaikan seluruh uji otomatis di Sandbox; kirim `payment.test` dari dashboard dan pastikan 200.
2. Ganti tiga nilai di `.env`: **host Live**, **API key Live**, **webhook secret Live** (ketiganya satu paket), lalu `config:cache`.
3. Pastikan webhook endpoint juga terdaftar untuk **kunci Live** (pengaturan Sandbox dan Live bisa terpisah di dashboard; periksa, jangan berasumsi).
4. **Uji dengan uang sungguhan bernominal kecil** (≥ Rp1.000) memakai **harga uji sementara** (lihat §15), untuk setiap alur: satuan, bulanan, tahunan. Pastikan: order `settlement`, akses aktif, email bukti bayar masuk, status di dashboard SumoPod benar.
5. Matikan harga uji, `config:cache`, dan beri **catatan** pada order uji agar tidak mengotori laporan pendapatan.

---

## 15. Harga uji aman (jangan lupa dimatikan)

Untuk menguji Live tanpa mengubah harga publik:

```php
// config/payment.php
'test_price' => env('PAYMENT_TEST_PRICE'),   // mis. 1000
'test_email' => env('PAYMENT_TEST_EMAIL'),   // satu atau beberapa email, dipisah koma
'test_until' => env('PAYMENT_TEST_UNTIL'),   // ISO 8601; mati otomatis setelah waktu ini
```

Aturan yang membuatnya aman:
- Hanya berlaku untuk **email yang terverifikasi** dan terdaftar di daftar uji.
- **Kedaluwarsa otomatis** (`test_until`), sehingga tidak bisa tertinggal aktif di produksi.
- Tidak berlaku untuk produk gratis; nominal tidak boleh di bawah Rp1.000 (batas SumoPod).
- Tampilkan **peringatan** di panel admin selama harga uji aktif.

---

## 16. Operasional & pemantauan

| Yang dipantau | Cara |
|---|---|
| Webhook masuk | Simpan "detak terakhir" webhook (sukses/gagal) dan tampilkan di panel admin |
| Kegagalan membuat payment | Log + status layanan "pembayaran" |
| Order `pending` lama | Biarkan kedaluwarsa lewat webhook `payment.expired`; link lama dianggap `expire` saat dibuka |
| Rekonsiliasi | Cocokkan order `settlement` dengan mutasi saldo SumoPod secara berkala |
| Saldo email/notifikasi | Bila email transaksional memakai saldo (SumoPod), pantau saldo agar kode login/bukti bayar tidak berhenti |
| Pendapatan | Hitung dari order `settlement` ber-`amount > 0` saja (akses gratis dari admin bernominal 0 tidak dihitung) |

**Akses gratis dari admin** sebaiknya dicatat sebagai order lunas bernominal Rp0 dengan `payment_method='komplimen'` dan `catatan`, bukan dengan mengutak-atik tabel akses. Dengan begitu satu mekanisme dipakai untuk semuanya dan jejak audit tetap ada.

---

## 17. Pelajaran dari produksi (jebakan nyata)

| Gejala | Penyebab | Solusi |
|---|---|---|
| HTTP **401** saat membuat payment | Key Sandbox dipakai di host Live (atau sebaliknya) | Samakan key, secret, dan host |
| Webhook selalu **419** | CSRF belum dikecualikan | Tambahkan pengecualian `webhook/sumopod` |
| Signature selalu salah | Body diformat ulang sebelum diverifikasi, atau secret salah | Pakai `getContent()`; cek `whsec_` |
| Pembayaran berhasil tapi halaman tidak berpindah | Hanya mengandalkan redirect dari gateway | Halaman "selesai" dengan polling status |
| Order ganda/komisi ganda | `settle()` tidak idempoten | Kunci baris + cek status `pending` |
| Gagal membuat payment untuk produk gratis | Nominal < Rp1.000 | Jangan buat order Rp0 |
| Return URL ditolak API | Validasi di sisi gateway | Ulangi tanpa override (sudah ditangani di §6) |
| Konfigurasi baru tidak terbaca | `config:cache` lama | Jalankan ulang `config:cache` setelah ubah `.env` |
| Email bukti bayar tidak terkirim | Worker antrean mati | Jalankan/pantau Horizon atau `queue:work` |
| Harga uji tertinggal di produksi | Lupa dimatikan | `test_until` + peringatan di admin |
| Pembeli luar negeri tidak bisa bayar | QRIS hanya untuk m-banking/e-wallet Indonesia | Jelaskan di halaman bayar; sediakan metode lain bila perlu |

---

## 18. Keterbatasan yang perlu Anda sadari

- **QRIS saja.** Tidak ada kartu kredit/transfer bank/e-wallet lain pada integrasi ini.
- **Tidak ada alur refund otomatis.** Pembatalan setelah lunas dikerjakan manual (dan komisi/akses harus dibatalkan manual).
- **SumoPod tidak dipakai untuk mengirim uang keluar** (mis. membayar komisi afiliator). Pengiriman dana ke rekening pihak ketiga butuh penyedia *disbursement* terpisah.
- Dokumentasi ini dibuat dari implementasi dan dashboard; bila SumoPod mengubah format API/webhook, sesuaikan §6 dan §8.

---

## 19. Daftar tugas saat menerapkan di proyek lain

1. [ ] Buat akun/kunci Sandbox dan Live; catat host masing-masing.
2. [ ] Tambah `.env` dan `config/services.php` (§3).
3. [ ] Migrasi tabel `orders` (§4) + model.
4. [ ] Rute + pengecualian CSRF webhook (§5).
5. [ ] `SumopodPayService` (§6).
6. [ ] `PaymentController`: konfirmasi, proses, selesai (§7, §10).
7. [ ] `SumopodWebhookController` (§8).
8. [ ] `OrderSettlementService::settle` + logika akses produk Anda (§9).
9. [ ] Email bukti bayar lewat antrean + worker berjalan (§11).
10. [ ] Halaman bayar & halaman menunggu (UI), pesan galat yang ramah.
11. [ ] Tes otomatis §13.2 lulus semua.
12. [ ] Uji Sandbox ujung-ke-ujung, lalu uji Live bernominal kecil (§14, §15).
13. [ ] Panel admin: daftar order, konfirmasi manual cadangan, status webhook, peringatan harga uji.
14. [ ] Matikan harga uji; catat order uji; `config:cache`.

---

## 20. Prompt siap pakai untuk asisten koding

Salin ke asisten (mis. Claude Code) di proyek baru, lalu lampirkan dokumen ini:

```text
Terapkan pembayaran QRIS SumoPod Managed Payment di proyek Laravel ini mengikuti dokumen PEMBAYARAN-QRIS-SUMOPOD.md.
Produk yang dijual: <jelaskan produk, harga, dan apa yang terjadi setelah lunas>.
Syarat: order idempoten, webhook diverifikasi (Svix) atas body mentah, nominal dicocokkan, settle() memakai lockForUpdate,
bukti bayar lewat antrean, halaman "selesai" dengan polling, dan tes otomatis sesuai §13.2.
Jangan menulis kunci/secret ke kode atau log; gunakan placeholder di .env.example.
Setelah selesai: jalankan seluruh tes, lalu jelaskan langkah uji Sandbox dan Live yang harus saya lakukan.
```

---

*Dokumen ini ditulis dari implementasi produksi Rumus Langit (Laravel 13). File sumber acuan: `app/Services/SumopodPayService.php`, `app/Http/Controllers/PaymentController.php`, `app/Http/Controllers/SumopodWebhookController.php`, `app/Services/OrderSettlementService.php`, `config/payment.php`, dan `tests/Feature/SumopodPaymentTest.php`.*
