# Dokumentasi Teknis — Rumus Langit

> Platform konsultan metafisika & wellness berbasis AI (weton, shio, zodiak + laporan naratif dari Gemini).
> Dokumen ini mendeskripsikan kondisi kode **per 10 November 2026**. Untuk visi produk & roadmap, lihat [PRD-RumusLangit.md](PRD-RumusLangit.md) (catatan: PRD menyebut Laravel 11, kenyataannya sudah Laravel 13).

---

## 1. Ringkasan

| Aspek | Nilai |
|---|---|
| Domain | rumuslangit.biz.id |
| Framework | Laravel 13 (`laravel/framework` v13.40), PHP 8.3 |
| Database | PostgreSQL (remote), sqlite hanya cadangan lokal |
| Cache / Session / Queue | Redis (`predis`) |
| Queue dashboard | Laravel Horizon (`/horizon`, terkunci — gate kosong, hasilnya 403) |
| AI | Google Gemini (`gemini-2.5-flash-lite` default) via REST |
| PDF | `barryvdh/laravel-dompdf` (terpasang; model `PdfDownload` ada, belum ada alur unduh PDF di route) |
| Frontend | Blade + Tailwind CSS 4 + Vite 8 (tanpa SPA) |
| Web server | nginx + php-fpm 8.3 (pool `www`, user `www-data`) |
| Bahasa UI | Indonesia (`id`) dan Inggris (`en`) |
| Pembayaran | **QRIS** via SumoPod Managed Payment (otomatis lewat webhook). Transfer bank manual untuk pelanggan sudah dihapus (10 Nov 2026) |

Ada juga situs lain di server yang sama (`assessly.biz.id`), vhost terpisah.

---

## 2. Alur Pengguna

```
Landing (/) ─► Isi data lahir (/mulai-analisis) ─► POST /proses
      │                                                  │
      │                         KalkulasiEngine hitung weton/shio/zodiak/elemen/radar
      │                         User + BirthProfile dibuat, id disimpan di session
      ▼                                                  ▼
 Hasil trial (/hasil) ◄──── job profil AI (feature_id=0) dijalankan di queue
      │
      ├─► Verifikasi email via OTP (/otp/kirim → /otp/verifikasi)
      ▼
 Katalog fitur (/analisis)  ─► pilih fitur ─► /laporan/{id}
      │                                         │
      │            punya akses? ── ya ──► form kuesioner ─► POST ─► queue ─► status (polling) ─► hasil
      │                         └─ tidak ─► paywall ─► /payment/{id} ─► transfer ─► admin konfirmasi
```

Poin penting:

- **Tidak ada registrasi klasik.** Identitas pengguna = baris `users` yang dibuat dari form data lahir; `user_id` disimpan di **session**. Login email+password (`/login`) hanya untuk akun yang sudah punya password (mis. dibuat admin).
- **Akses fitur** (`User::hasAccessToFeature`): pelanggan aktif **atau** punya order `settlement` untuk fitur itu.
- **Trial** dibatasi per email: satu email (di-hash SHA-256) hanya bisa pakai trial sekali (`email_verifications.trial_used_at`). Pembatasan dimatikan saat `APP_ENV=local`.
- **Aturan hasil laporan (ditetapkan pemilik, 11 Nov 2026):** (1) **hari yang sama (WIB), jawaban sama → hasil yang sama persis** (laporan tersimpan dipakai ulang: tanpa AI, tanpa jatah); (2) **hari berikutnya, jawaban sama → laporan BARU** dari Gemini (hasil bisa berbeda; **tidak memotong jatah**); (3) **jatah hanya terpakai untuk jawaban yang BERBEDA** (3 jatah per pembelian satuan Rp29.000); ganti bahasa membuat laporan baru tanpa memotong jatah. Berlaku untuk **semua fitur** secara default; daftar fitur harian diatur `.env` `PAYMENT_FITUR_HARIAN` (default `semua`; atau daftar ID `3,10,11`; kosong = jawaban sama SELALU memakai laporan tersimpan, tanpa AI). Biaya AI terbatas: maksimal 1 pembuatan per hari per kumpulan jawaban (±Rp25–250 per laporan). Untuk fitur **3 Hari Baik, 10 Ruwat, 11 Angka & Warna Harian**, prompt ke AI memuat **"KONTEKS WAKTU: tanggal hari ini (WIB)"** (AI tidak punya jam sendiri; sebelumnya tanggal tidak pernah dikirim); cache respons Gemini per hari kalender WIB untuk fitur harian, tetap untuk profil karakter (fitur 0). Halaman hasil menampilkan **tanggal pembuatan laporan** (WIB), bukan tanggal hari ini. "Hari" dihitung `App\Support\HariWib` (server & database tetap UTC).

---

## 3. Fitur Produk

Didefinisikan di [app/Data/FiturData.php](app/Data/FiturData.php) (metadata bahasa-netral + teks per locale). Harga per laporan **Rp29.000**.

| ID | Hub | Fitur | Catatan |
|---|---|---|---|
| 0 | — | Profil karakter (trial) | Otomatis, gratis, tidak ada di katalog |
| 1 | Wealth | Arah Mata Angin Rezeki | |
| 2 | Wealth | Potensi Bisnis & Gaya Investasi | |
| 3 | Wealth | Hari Baik & Momentum Usaha | |
| 4 | Wealth | Arah Tempat Usaha & Ruko | |
| 5 | Social | Kalkulator Jodoh & Asmara | Wajib isi data pasangan |
| 6 | Social | Radar Toksisitas & Politik Kantor | |
| 7 | Social | Karakter Anak & Pola Asuh | |
| 8 | Wellness | Audit & Analisis Nama | |
| 9 | Wellness | Kesehatan & Vitalitas | Tambahan input golongan darah |
| 10 | Wellness | Panduan Ruwat & Mitigasi Sial | |
| 11 | Wellness | Angka & Warna Keberuntungan Harian | `subscriber_only`, harga 0 |
| 12 | Traffic Driver | Tafsir Mimpi | `freemium`, input teks bebas (maks 500 karakter), form khusus `form_mimpi` |

Fitur 1–10 memakai 10 pertanyaan Ya/Tidak (`q1..q10`). Tarot (opsional, maks 3 kartu) dari [app/Data/TarotData.php](app/Data/TarotData.php) lewat `/tarot/pilih`.

### Harga & skema akses (config: [config/payment.php](config/payment.php))

| Produk | Harga | Isi | Marker `orders.feature_id` |
|---|---|---|---|
| **Laporan satuan** | Rp29.000 | **3 kali pembuatan dengan jawaban berbeda** untuk fitur itu (`PAYMENT_JATAH_SATUAN`); jawaban yang sama tidak memakai jatah (laporan tersimpan dipakai ulang); hasil yang sudah dibuat **tersimpan selamanya**. Setiap pembelian baru menambah 3 jatah | 1–10 |
| **Langganan bulanan** | **Rp59.000** (`PAYMENT_LANGGANAN_BULANAN_HARGA`) | Semua fitur tanpa jatah (batas wajar **20 laporan/hari**, `PAYMENT_BATAS_HARIAN_LANGGANAN`) + fitur 11 + Hoki Harian | `-1` (`Order::SUBSCRIPTION_MONTHLY_FEATURE_ID`) |
| **Langganan tahunan** | **Rp499.000** (hemat 30% dari 12 × Rp59.000; `PAYMENT_LANGGANAN_TAHUNAN_HARGA`) | Sama dengan bulanan, 12 bulan | `-2` (`Order::SUBSCRIPTION_YEARLY_FEATURE_ID`) |

**Biaya per transaksi (acuan perhitungan margin, per 11 Nov 2026):** QRIS SumoPod 0,7% + Rp300 (dari saldo merchant, cair T+2); email SumoPod **Rp5 per email** (OTP, bukti bayar, Hoki Harian; Hoki Harian ≈ Rp150/bulan atau Rp1.825/tahun per pelanggan); AI Gemini taksiran Rp25–250 per laporan; komisi afiliasi 20% (maks. 12 bulan per pelanggan). Perkiraan sisa bersih: tahunan Rp499.000 ≈ Rp388.000, bulanan Rp59.000 ≈ Rp45.400, satuan Rp29.000 ≈ Rp22.500 (harga bulanan/tahunan dinaikkan dari Rp49.000/Rp399.000 pada 11 Nov 2026; impas konversi: harga baru lebih menguntungkan selama pembeli tidak turun lebih dari ±17%). **Saldo email habis = OTP/login kode gagal** — top up secara berkala dan pantau saldo di dashboard SumoPod (tidak bisa dibaca dari aplikasi).

**Alasan skema (ditetapkan 11 Nov 2026):** sebelumnya membeli 10 laporan satuan (Rp290.000, akses selamanya tanpa batas) lebih murah daripada langganan tahunan (Rp350.000), sehingga langganan tidak masuk akal. Sekarang satuan = untuk mencoba (jatah terbatas), langganan = pemakaian terus-menerus. Pembeli 3 laporan (Rp87.000) sudah lebih untung berlangganan bulanan (Rp59.000); 2 laporan (Rp58.000) kira-kira impas.

**Cara kerja jatah** (`User::kuotaTotal/kuotaTerpakai/kuotaSisa`, `ai_reports.consumed_quota`):
- Akses fitur = fitur gratis, **atau** pelanggan aktif, **atau** `kuotaSisa > 0`. Jatah total = 3 × jumlah pembelian lunas fitur itu; terpakai = pembuatan BARU yang menandai `consumed_quota=true`.
- **Jatah dihitung per kumpulan jawaban yang BERBEDA** (hash fitur + kalkulasi + jawaban + tarot, tanpa memandang bahasa). **Tidak memakai jatah:** jawaban yang sama dengan yang pernah dibuat (hari yang sama memakai laporan tersimpan, hari berikutnya dibuat baru; keduanya tanpa memotong jatah; ganti bahasa juga tidak), pembuatan oleh pelanggan, fitur gratis, dan pembuatan yang gagal/macet lebih dari 15 menit. Pembuatan yang sedang berjalan (< 15 menit) dianggap sudah ada. Bila percobaan lama gagal lalu jawaban yang sama diulang, beban jatah **dipindah** ke percobaan terbaru (`ReportController::pakaiJatahUntuk`) sehingga satu kumpulan jawaban tak pernah memotong jatah dua kali.
- Jatah habis → paywall menampilkan "Jatah pembuatan laporan ini sudah habis" (beli lagi atau langganan) dan tautan **"Lihat laporan terakhir saya"** (`GET /laporan/{id}/terakhir`, tanpa memakai jatah dan tanpa butuh akses aktif; hanya laporan milik sendiri).
- Form laporan menampilkan "Sisa jatah pembuatan: X dari 3" untuk pembeli satuan.
- **Batas harian** (melindungi biaya AI; hari = hari kalender WIB, berganti tengah malam WIB): pelanggan 20 laporan/hari; fitur gratis (Tafsir Mimpi) 5/hari per pengguna (`PAYMENT_BATAS_HARIAN_GRATIS`). Hanya pembuatan baru yang dihitung.

Fitur **freemium (ID 12, Tafsir Mimpi) terbuka untuk semua pengguna** tanpa bayar; fitur 11 hanya untuk pelanggan; halaman `/payment/{id}` untuk fitur bernominal 0 dialihkan (tidak pernah membuat order Rp0) dan kartu beli eceran disembunyikan di paywall fitur khusus langganan. Pelanggan aktif mengakses semua fitur dan menerima email **Hoki Harian** pukul 06.00 WIB.

---

## 4. Arsitektur Kode

```
app/
├── Console/Commands/      hoki:kirim, subscription:sync
├── Data/                  FiturData (katalog fitur), TarotData
├── Exceptions/            GeminiRateLimitException
├── Http/
│   ├── Controllers/       Trial, Report, Payment, Otp, Auth, Admin, Wilayah, Locale, Tarot
│   └── Middleware/        SetLocale, CaptureReferral, AdminMiddleware (alias `admin`)
├── Jobs/GenerateAIReport  panggilan Gemini di queue
├── Mail/                  OtpMail, HokiHarianMail
├── Models/                User, Order, Subscription, AiReport, BirthProfile, QuestionnaireSession,
│                          EmailVerification, PdfDownload, Affiliate, AffiliateCommission, Wilayah*
├── Providers/             AppServiceProvider, HorizonServiceProvider
└── Services/
    ├── KalkulasiEngine    hitung weton/shio/zodiak/elemen/radar (murni PHP, deterministik)
    ├── GeminiService      bangun prompt per fitur+locale, panggil Gemini, perbaiki JSON
    └── EmailOtpService    OTP 6 digit, TTL 5 menit di cache
```

### 4.1 KalkulasiEngine
Dihitung **tanpa AI**: weton (hari + pasaran Jawa, neptu), shio (tahun baru Imlek disederhanakan: sebelum Februari dihitung tahun sebelumnya), zodiak Barat, elemen (dari digit akhir tahun), dan 5 skor "radar" (karir, asmara, cuan, kesehatan, spiritual) dari seed tanggal+jam lahir. Skor radar bersifat pseudo-acak deterministik, bukan perhitungan tradisional.

### 4.2 GeminiService & GenerateAIReport
- `GeminiService::generate()` membangun prompt (ID/EN) per fitur, `temperature` 0.87 (0.75 untuk Tafsir Mimpi), `maxOutputTokens` dari config, timeout 90 detik.
- HTTP 429 → `GeminiRateLimitException` (dengan `retryAfterSeconds`); 503 → retry/backoff.
- `GenerateAIReport` (queue `default`): `tries=10`, `maxExceptions=3`, backoff 30/60/120/180 detik, **rate limiter global 12 panggilan/menit** (`gemini-api-calls`) plus jitter saat di-release.
- Fitur 0 (profil) menyimpan **JSON** di `ai_reports.response_text`; fitur lain menyimpan teks. `repairJson()` memperbaiki newline literal dan **trailing comma**; hasil profil **divalidasi** sebelum disimpan/di-cache — jika tetap rusak, job melempar exception dan di-retry. Pada sisi baca (`TrialController::decodeProfil`) JSON rusak diperbaiki dulu; jika tak terpulihkan, baris dibuang dan profil digenerate ulang. Header autentikasi Gemini: `x-goog-api-key`. Tafsir Mimpi dicoba sinkron dulu, jatuh ke queue jika gagal.

**Kuota Gemini & ketahanan (11 Nov 2026):** kunci API proyek ini masih **paket gratis**: Google menolak dengan `429 RESOURCE_EXHAUSTED` (`GenerateRequestsPerDayPerProjectPerModel-FreeTier`, **batas 20 permintaan/hari per model**) — jauh di bawah kebutuhan produksi. **Langganan konsumen Google (AI Plus/Pro/Ultra) TIDAK memberi kuota API**; API ditagih terpisah lewat Google Cloud Billing. Solusi: aktifkan billing proyek di Google AI Studio (API keys → *Set up billing*; prabayar, tier 1) — lihat checklist bagian 14-A. Aplikasi kini menangani kuota habis dengan benar: (1) `GeminiService` membaca `RetryInfo.retryDelay` resmi Google dan mendeteksi kuota **harian/paket gratis**, lalu mencatat pesan yang bisa ditindaklanjuti di Status layanan ("Kuota harian PAKET GRATIS habis (batas 20/hari, model …); pulih ±3.9 jam lagi — aktifkan billing"); (2) `GenerateAIReport` memakai **batas waktu 8 jam** (`retryUntil`) bukan 10 percobaan, melepas pekerjaan ke antrean mengikuti waktu tunggu Google (maks. 1 jam sekali) sehingga laporan **menunggu lalu selesai sendiri** setelah kuota pulih, bukan gagal permanen; (3) halaman tunggu laporan menampilkan pesan jujur setelah 3 menit (layanan padat, laporan tetap diproses, tak perlu menunggu). Catatan: kuota per **model** — `gemini-2.5-flash` punya kuota harian gratis sendiri (cadangan sementara bila perlu; matikan fitur "thinking" dengan `thinkingBudget: 0` agar JSON tidak terpotong).

**Penyedia AI cadangan (11 Nov 2026) — xAI Grok atau penyedia lain berformat OpenAI (mis. Groq).** Gemini tetap jalur utama. Bila Gemini gagal dalam satu permintaan — kuota habis (429), gangguan, kunci ditolak, atau JSON rusak — `GeminiService::generate()` otomatis mencoba **Grok** dengan prompt yang sama (`App\Services\GrokService`, API kompatibel OpenAI di `XAI_BASE_URL`, default `https://api.x.ai/v1`, endpoint `chat/completions`, autentikasi Bearer). Aktif **hanya bila `XAI_API_KEY` dan `XAI_MODEL` terisi** di `.env` (nama model sengaja tidak ditebak: tekan **Tes Grok** di Admin → Sistem untuk melihat daftar model yang tersedia bagi akun, lalu isi `XAI_MODEL`; setelah mengubah `.env` jalankan `php artisan config:cache`). Detail perilaku: (1) respons Grok diminta JSON (`response_format: json_object`, `max_completion_tokens`, `temperature`); bila model menolak parameter opsional (HTTP 400/422) diulang dengan permintaan minimal; teks dibersihkan (pagar kode, `repairJson`) dan **harus JSON valid**, kalau tidak dianggap gagal dan **tidak disimpan**. (2) Bila Gemini dan Grok sama-sama gagal, **galat Gemini yang diteruskan** (mis. rate-limit → pekerjaan menunggu sesuai aturan 8 jam). (3) **Penanda jeda:** saat 429 kuota **harian** Gemini, `Cache gemini:kuota-habis` aktif (maks. 6 jam / sesuai waktu tunggu Google) sehingga permintaan berikutnya **langsung ke Grok** tanpa memanggil Gemini; bila Grok gagal saat jeda, Gemini dicoba sekali. (4) Tanpa cadangan perilaku lama tidak berubah. (4a) **Tombol "Tes AI cadangan" menguji jalur yang sama dengan laporan nyata** (mode JSON, ruang penalaran, perbaikan, validasi) dan hanya menyatakan "berfungsi" bila JSON valid dan berisi (versi awal memakai batas 20 token sehingga model penalaran membalas kosong tetapi tetap dinyatakan berfungsi — diperbaiki 11 Nov 2026). (4b) **Model penalaran** (mis. `openai/gpt-oss-120b` di Groq) memakai sebagian token untuk "berpikir": batas keluaran ditambah `XAI_RUANG_PENALARAN` (default 2500) agar JSON tak terpotong; `XAI_REASONING_EFFORT` (low/medium/high) opsional. (5) `ai_reports.provider` (`gemini`/`grok`) mencatat penyedia tiap laporan (cache mempertahankan penyedia aslinya) — terlihat di Detail pengguna → laporan; Status layanan memiliki baris **"AI Grok (cadangan)"** dan info cadangan aktif/jeda. **Perhatian:** **Awas kekeliruan nama: _Groq_ (groq.com, kunci berawalan `gsk_`, alamat `https://api.groq.com/openai/v1`) BUKAN _Grok_ milik xAI (kunci berawalan `xai-`, alamat `https://api.x.ai/v1`).** Layanan memakai format OpenAI-kompatibel yang sama; untuk Groq isi `XAI_BASE_URL=https://api.groq.com/openai/v1` + `XAI_MODEL` dari daftar di tombol **Tes AI cadangan**; tombol itu mendeteksi ketidakcocokan kunci/alamat dan menjelaskannya **tanpa mengirim kunci** ke alamat yang salah. (a) data pengguna (data lahir, jawaban kuesioner, nama) ikut dikirim ke penyedia cadangan saat dipakai — cantumkan xAI sebagai pemroses data di Kebijakan Privasi dan periksa syarat penggunaan data pada paket API xAI (paket gratis/kredit gratis kadang mensyaratkan berbagi data untuk pelatihan; **belum diverifikasi**); (b) kualitas/gaya keluaran Grok dapat berbeda dari Gemini untuk prompt yang sama; (c) batas pemakaian paket gratis xAI tidak diketahui — jadikan cadangan, bukan pengganti billing Gemini.

**Status uji (10 Nov 2026):** panggilan langsung ke `gemini-2.5-flash-lite` → HTTP 200 dan respons benar; autentikasi lewat header `x-goog-api-key` juga berhasil (bisa menggantikan `?key=` di query string). Tidak ada failed job; 13 laporan tersimpan, semuanya terisi (0 macet). Error Gemini terakhir di log tercatat Juni 2026.

### 4.3 Pembayaran
Satu-satunya metode pembayaran pelanggan adalah **QRIS** (bagian 4.3b). Transfer bank manual (kode unik 3 digit + konfirmasi admin) **dihapus pada 10 Nov 2026**: tidak ada lagi pilihan rekening di halaman bayar, config `payment.bank_accounts` dan env `PAYMENT_BCA_*`/`PAYMENT_CIMB_*` dibuang. Kolom lama `orders.unique_code`, `transfer_amount`, `bank_tujuan` tetap ada di database (riwayat; tidak dihapus). Kedaluwarsa order ditangani SumoPod (`payment.expired` → status `expire`; masa berlaku link `PAYMENT_EXPIRE_HOURS`, maks. 24 jam).

Konfirmasi admin (`POST /admin/orders/{id}/konfirmasi`) **dipertahankan sebagai cadangan** bila webhook gagal, misalnya order QRIS pending yang pembayarannya terbukti masuk di dashboard SumoPod. Ia memakai `OrderSettlementService` yang sama dengan webhook.

### 4.3b Pembayaran QRIS (SumoPod Managed Payment)
Metode pembayaran tunggal. Bila `SUMOPOD_PAY_API_KEY` kosong, halaman `/payment/{id}` menampilkan pesan "QRIS sedang tidak tersedia" (tidak ada jalur alternatif).

1. `POST /payment/{id}` → order `pending` (`payment_method='qris'`, `gateway_order_id='RL-xxxx'`, tanpa kode unik) → [SumopodPayService](app/Services/SumopodPayService.php) `POST {base}/api/v1/payments` (header `X-Api-Key`, `payment_method_type_code=qris`, kedaluwarsa maks. 24 jam) → `payment_id`, `payment_link_url`, `payment_expires_at` disimpan → user di-redirect ke halaman bayar SumoPod. Submit ulang memakai order yang sama (tanpa payment ganda). API gagal → order `cancel` + pesan error.
2. SumoPod memanggil **`POST /webhook/sumopod`** ([SumopodWebhookController](app/Http/Controllers/SumopodWebhookController.php)). Dikecualikan dari CSRF, diamankan **signature Svix** (HMAC-SHA256 atas `{svix-id}.{svix-timestamp}.{raw body}` dengan `SUMOPOD_PAY_WEBHOOK_SECRET`, toleransi waktu 5 menit, perbandingan constant-time). Signature salah → 401.
2b. **Alur setelah bayar:** `buatPembayaran` mengirim `success_return_url` = `/payment/selesai/{gateway_order_id}` dan `cancel_return_url` = halaman bayar produk itu (jika API menolak override, otomatis diulang tanpa override sehingga alamat bawaan dashboard dipakai). Halaman `payment.selesai` (hanya pemilik order): lunas → langsung ke kuesioner fitur (atau katalog untuk langganan) dengan pesan sukses; belum masuk → halaman tunggu yang polling tiap 2,5 detik dan berpindah otomatis begitu webhook melunasi order (setelah 90 detik muncul petunjuk bahwa akses terbuka otomatis); batal/kedaluwarsa → kembali ke halaman bayar. Katalog dan daftar "Analisis lainnya" menandai fitur yang sudah dibeli dengan "Sudah dibeli" (bukan harga).
3a. **Pengingat folder Spam (aturan untuk SEMUA pengiriman email):** domain pengirim masih baru sehingga email awal sering masuk Spam. Karena itu setiap email dan setiap pesan di layar yang menyebut "email telah dikirim" **wajib** memuat pengingat cek folder Spam/Junk (dan di dalam email: tandai "Bukan spam"). Sudah diterapkan di: modal OTP (`hasil.otp.kode_info`), login kode (`auth.kode_terkirim`, `auth.kode_desc`), pesan sukses bayar (`payment.selesai_sukses_*`), email OTP, bukti bayar, Hoki Harian, dan persetujuan afiliasi; halaman `/afiliasi` sudah memuatnya. **Email baru yang ditambahkan di masa depan harus mengikuti aturan ini** (ada tes yang memeriksanya).
3c. **Pilih paket langganan (`GET /langganan`):** tombol "Mulai Berlangganan" di katalog membuka halaman yang menampilkan **bulanan dan tahunan berdampingan** (harga, "Hemat X%", setara per bulan, daftar manfaat); sebelumnya tombol itu langsung ke halaman bulanan sehingga paket tahunan tidak terlihat. Halaman bayar langganan juga memuat tautan **ganti paket** (bulanan ↔ tahunan). Pelanggan yang masih aktif dialihkan ke katalog (lihat checklist: perpanjangan saat aktif belum diizinkan). Paywall per laporan tetap menampilkan kedua paket.
3b. **Bukti bayar otomatis:** begitu order menjadi lunas (webhook QRIS **atau** konfirmasi admin), `OrderSettlementService` mengantrekan email [BuktiBayarMail](app/Mail/BuktiBayarMail.php) ke pelanggan (HTML + teks polos): nomor order (`RL-...`), produk, masa aktif (langganan), metode, waktu bayar (WIB), akun, total, tombol ke kuesioner/katalog, dan catatan "bukan faktur pajak". Dikirim **lewat queue** (webhook harus membalas < 10 detik), hanya sekali per order (pelunasan idempoten), dan kegagalan email tidak membatalkan pelunasan (hanya dicatat di log). Satu pembayaran = satu kredit email SumoPod. Pembayaran gagal/nominal salah tidak mengirim apa pun.
3. `payment.completed` → nominal harus sama dengan order, lalu [OrderSettlementService](app/Services/OrderSettlementService.php) melunasi order (idempoten, dengan row lock): membuka akses fitur / mengaktifkan langganan dan mencatat komisi afiliasi. `payment.failed` → `cancel`; `payment.expired` → `expire`; `payment.test` dan order tak dikenal → 200 (dicatat di log).

**Sandbox vs Live:** key Sandbox hanya berlaku di host sandbox. Untuk uji, set `SUMOPOD_PAY_BASE_URL=https://api-pay-sandbox.sumopod.com` (hasil uji 10 Nov 2026: key Sandbox di host Live → `401 Unauthorized`; di host Sandbox → payment berhasil dibuat). Untuk produksi: `SUMOPOD_PAY_BASE_URL=https://api-pay.sumopod.com` + key Live, lalu `php artisan config:cache`.

**Batas minimum SumoPod: Rp1.000** (nominal lebih kecil ditolak `400 INVALID_INPUT`), jadi harga uji minimal 1000.

**Harga uji (sementara):** `PAYMENT_TEST_PRICE` + `PAYMENT_TEST_EMAIL` (**satu atau beberapa email dipisah koma**, tak peka huruf besar-kecil, spasi/entri kosong ditoleransi; + pengaman **`PAYMENT_TEST_UNTIL`**, ISO 8601: setelah waktu itu harga uji **mati sendiri**; panel Admin → Sistem menampilkan peringatan selama aktif) di `.env` menurunkan harga **hanya untuk email terverifikasi yang tercantum** (berlaku juga untuk langganan; minimum SumoPod Rp1.000) (untuk tes transaksi Live bernominal kecil). Pengguna lain tetap membayar harga normal. Setelah pengujian, **kosongkan kedua variabel** lalu `php artisan config:cache`.

Biaya QRIS menurut dashboard SumoPod: 0,7% + Rp300 (dipotong dari saldo merchant), dana cair T+2. Kolom baru `orders.payment_id` (unik), `payment_link_url`, `payment_expires_at` (migration `2026_11_10_150000_...`).

### 4.4 Program afiliasi
- Afiliator mendaftar lewat halaman publik `/afiliasi` dan **disetujui admin** (atau ditambahkan manual oleh admin); kode referral unik, komisi default `AFFILIATE_DEFAULT_COMMISSION` (20%).
- Link `?ref=KODE` ditangkap `CaptureReferral` (first-touch, di session). Saat `User` pertama dibuat, `referred_by_affiliate_id` diisi **permanen**.
- Tiap order `settlement` mencatat komisi `unpaid` di `affiliate_commissions` (satu per order, idempoten) lewat `OrderSettlementService::catatKomisiAfiliasi`. Afiliator nonaktif tidak dapat komisi baru.
- **Aturan komisi (juga menjadi Syarat & Ketentuan yang dibaca dan disetujui afiliator di `/afiliasi`, dan dikirim di email persetujuan; teks di `lang/*/afiliasi.php`, angka dari config):**
  1. Komisi = persentase × harga yang dibayar (sebelum biaya gateway dan pajak), untuk setiap pembayaran berhasil pelanggan yang direferensikan.
  2. **Batas waktu:** hanya untuk pembayaran dalam `AFFILIATE_COMMISSION_MONTHS` (12) bulan sejak **pembayaran pertama** pelanggan itu (termasuk perpanjangan langganan); sesudahnya tanpa komisi.
  3. **Tanpa komisi untuk pembelian sendiri:** email pelanggan sama dengan email afiliator (tidak peka huruf besar-kecil).
  4. **Masa tahan** `AFFILIATE_HOLD_DAYS` (14) hari: admin hanya bisa menandai lunas komisi yang sudah lewat masa tahan ("Siap dibayar"); yang masih "Ditahan" tidak ikut dan masuk periode berikutnya. Payout dibuat **otomatis** tiap tanggal 25 bila total siap dibayar ≥ Rp100.000 (lihat 4.4b); transfer dilakukan admin sekali sebulan.
  5. Tidak ada komisi untuk transaksi gagal/batal/refund/terindikasi penipuan (komisi hanya dicatat saat order `settlement`; pembatalan setelahnya perlu dibatalkan manual oleh admin — belum ada otomatisasi refund).
- Admin menandai lunas komisi yang siap dibayar setelah transfer manual selesai.

#### Pendaftaran afiliator (publik)
- Halaman **`/afiliasi`** (tautan di footer semua halaman): penjelasan program (komisi `AFFILIATE_DEFAULT_COMMISSION`, default 20%, bayar tiap tanggal 25) + formulir (nama, email, HP, rencana promosi opsional, rekening payout, persetujuan).
- Disimpan di tabel **`affiliate_applications`** dengan status `pending` — **tidak** langsung menjadi afiliator. Perlindungan spam: 5 kiriman/jam per IP, kolom honeypot `website`, validasi format HP/rekening, satu email hanya satu pendaftaran menunggu / satu afiliator.
- Admin → **Afiliator**: kartu "Pendaftar menunggu persetujuan" (badge jumlah di menu). **Setujui** (komisi % bisa diubah per orang) membuat `Affiliate` aktif dengan kode referral otomatis (`NAMADEPAN` + 2 digit, unik) dan mengirim email `AffiliateDisetujuiMail` berisi link `?ref=KODE`; bila email gagal, link ditampilkan di pesan admin untuk dikirim manual. **Tolak** menandai `rejected`. Tambah afiliator manual (modal) tetap ada.

### 4.4b Dashboard afiliator, payout otomatis, panduan, chat bantuan (11 Nov 2026)

- **Masuk afiliator** (`/afiliasi/masuk`): kode 6 digit lewat email (tanpa password), sesi terpisah dari pelanggan (kunci sesi `affiliate_id`); hanya afiliator **aktif**; balasan sama untuk email tak terdaftar (tidak membocorkan daftar); batas 3 kode/10 menit/email. Setelah masuk: **`/afiliasi/dashboard`** dan **`/afiliasi/panduan`** (ID/EN, `lang/*/afiliator.php`). Controller `AfiliatorController`.
- **Dashboard:** link referral + tombol salin, jumlah pendaftar via link, jumlah pelanggan yang sudah membayar, total komisi, saldo **siap dibayar / ditahan / diproses / sudah dibayar**, 20 komisi terbaru (nama pembeli disamarkan `B***`), riwayat payout, tanggal payout berikutnya, info rekening (nomor disamarkan). Hanya data milik afiliator itu sendiri.
- **Payout otomatis (tanpa pengajuan penarikan):** `AffiliatePayoutService` + perintah `afiliasi:payout`, dijadwalkan **tanggal `AFFILIATE_PAYOUT_DAY` (25) pukul 09:00 WIB**. Afiliator aktif dengan rekening lengkap dan saldo "siap dibayar" (belum dibayar, lewat masa tahan, belum masuk payout) **≥ `AFFILIATE_MIN_WITHDRAWAL` (Rp100.000)** mendapat satu baris `affiliate_withdrawals` (salinan rekening saat itu); komisinya ditautkan lewat `affiliate_commissions.withdrawal_id` (tidak bisa masuk payout ganda). Di bawah minimum, saldo terakumulasi. Syarat & Ketentuan, halaman `/afiliasi`, dan email persetujuan memuat angka minimum.
- **Admin → Payout** (`/admin/payout`, menu "💸 Payout" dengan badge): daftar payout menunggu, **unduh CSV** (bank, rekening, atas nama, jumlah) untuk transfer massal, **Tandai semua sudah ditransfer** (payout + komisi di dalamnya → `paid`), tombol per payout, tombol "Buat payout sekarang" (cadangan), riwayat 30 terakhir. Tombol lama "tandai lunas" di detail afiliator ikut menutup payout pending milik afiliator itu.
- **Transfer uang tetap manual (satu kali per bulan, bulk).** Transfer otomatis penuh ke rekening afiliator butuh penyedia *disbursement* (mis. Xendit/Midtrans Iris/Duitku) — SumoPod Managed Payment yang dipakai saat ini untuk menerima pembayaran, dan dokumentasinya tidak menunjukkan fitur kirim dana ke rekening. Belum dibangun; opsi untuk tahap berikutnya.
- **Chat bantuan otomatis** (widget 💬 di semua halaman, `partials/bantuan.blade.php`; endpoint `POST /bantuan/chat`, `BantuanController`, `BantuanBotService`): tanpa email/WhatsApp admin. Urutan jawaban: **Groq/AI cadangan** (hemat kuota Gemini; timeout 20 dtk) → Gemini (hanya bila tidak sedang jeda kuota) → **FAQ berdasar kata kunci** → langkah umum. Basis pengetahuan = `lang/*/bantuan.php` (FAQ + aturan AI). Untuk pengguna login, AI diberi data akun **miliknya saja** (status langganan, 5 pesanan terakhir) sehingga bisa menjawab "pembayaran saya berhasil?"; untuk afiliator login: ringkasan saldo. Pengaman: maks. 500 karakter, 12 pesan/menit, **60 pesan/hari per akun atau IP**, riwayat percakapan hanya 6 pesan terakhir, aturan melarang meminta password/OTP/kunci API, menolak topik di luar layanan, tanpa janji refund. Semua percakapan dicatat di `bantuan_logs` (gagal mencatat tidak mengganggu chat); **Admin → Chat bantuan** menampilkan log + filter "Tidak terjawab" untuk melengkapi FAQ.
- **Panduan setelah login:** pelanggan — `/panduan` (sudah ada) kini punya banner di katalog (halaman pertama setelah login); afiliator — `/afiliasi/panduan` + tautan di dashboard.
- **Migration baru (`2026_11_11_190000`):** tabel `affiliate_withdrawals`, `bantuan_logs`, kolom `affiliate_commissions.withdrawal_id`. **Harus dijalankan di produksi: `php artisan migrate --force`.**
- Env baru (opsional): `AFFILIATE_MIN_WITHDRAWAL` (100000), `AFFILIATE_PAYOUT_DAY` (25).

### 4.4c Halaman legal (11 Nov 2026)

- **`/privasi`, `/syarat`, `/refund`** (publik, ID/EN, isi di berkas bahasa `legal.php`, `HalamanController`), tautan di footer semua halaman. Keputusan pemilik: **tanpa nama usaha, tanpa batas usia, tanpa refund sama sekali** (pembelian final; "bayar berhasil tetapi akses belum aktif" diperlakukan sebagai pemenuhan pesanan, bukan refund), dan **chat bantuan adalah satu-satunya saluran kontak — tidak ada email/WhatsApp**. Tes memastikan halaman tidak memuat alamat email.
- Catatan risiko: sebagian penyedia pembayaran/platform dapat mewajibkan kontak resmi; pantau bila ada permintaan.
- Keputusan pemilik: **billing Gemini tidak akan diaktifkan** (tanpa anggaran); AI utama praktis menjadi Groq bila kuota gratis Gemini habis — perlu memantau batas gratis Groq.

### 4.5 Wilayah
Dropdown berjenjang provinsi → kota → kecamatan → kelurahan lewat `/api/wilayah/*`. Provinsi dan kota di-seed; kecamatan/kelurahan **di-seed lazy** saat pertama kali diminta (`WilayahController::seedKecamatan/seedKelurahan`).

### 4.6a Panduan pengguna (`/panduan`)
Halaman panduan **hanya untuk pengguna yang sudah login** (tanpa sesi → redirect ke login); tautan "Panduan" ada di navbar dan "Apa itu jatah?" di paywall menuju `/panduan#jatah`. Isi (ID/EN, `lang/*/panduan.php`): cara mendapat laporan, paket dan harga, penjelasan jatah dengan contoh, aturan hari yang sama/berikutnya, pembayaran QRIS, akun dan login, FAQ, disclaimer. Angka (harga, jatah, batas harian) diambil dari konfigurasi sehingga tidak kedaluwarsa. **Bila aturan jatah/harga/hari berubah, perbarui teks panduan** (ada tes yang memeriksa angka dan aturan pokoknya).

### 4.6b Login pengguna
Pengguna **tidak punya password** (akun dibuat dari form data lahir + verifikasi email OTP), sehingga jalur login utama adalah **kode email**: `/login` → masukkan email → kode 6 digit dikirim (berlaku 5 menit) → masuk ke katalog. Rinciannya:
- Hanya akun dengan email **terverifikasi** yang dikirimi kode; respons selalu sama (tidak membocorkan apakah email terdaftar).
- Batas: 6 permintaan/menit per IP, 3 kode/10 menit per email, maks. **5 tebakan salah** per kode (setelah itu kode dibatalkan), 10 verifikasi/menit per IP.
- Login password (`POST /login`, 10/menit) tetap ada, dilipat di bawah ("Punya password?"), untuk akun yang dibuat admin (user testing).
- **Menu Pengaturan** (`/pengaturan`, ikon ⚙ di navbar): pengguna login dengan email terverifikasi dapat membuat password (akun belum punya) atau mengubahnya (wajib password lama), min. 8 karakter + konfirmasi, 5 percobaan/menit. Kode email tetap bisa dipakai kapan saja.
- Pengguna yang kehilangan sesi/cookie cukup login ulang dengan kode email; data laporan dan pembelian mengikuti akun (berdasarkan email).

### 4.6 Lokalisasi
`SetLocale` membaca `session('locale')` (default `id`); ganti bahasa lewat `/lokal/{id|en}`. File terjemahan di [lang/](lang/). Laporan AI disimpan per `locale`.

---

## 5. Rute

| Area | Rute |
|---|---|
| Publik | `/`, `/mulai-analisis`, `POST /proses`, `/hasil`, `/profil-status` (AJAX), `/analisis` (katalog), `/lokal/{locale}`, `/tarot/pilih`, `/health` |
| Auth | `/login`, `POST /login` (password), `POST /login/kode` + `POST /login/kode/verifikasi` (kode email), `POST /logout`, `GET /pengaturan`, `POST /pengaturan/password` |
| Afiliasi | `GET/POST /afiliasi` (publik), admin: `POST /admin/affiliates/pendaftar/{id}/setuju`, `.../tolak` |
| OTP | `POST /otp/kirim`, `POST /otp/verifikasi` |
| Laporan | `/laporan/{id}` (form), `POST`, `/laporan/{id}/status` (polling), `/laporan/{id}/hasil` — throttle 10 req/menit |
| Payment | `GET/POST /payment/{id}` `POST /webhook/sumopod` (id numerik atau `langganan`, `langganan-bulanan`, `langganan-tahunan`) |
| Wilayah | `/api/wilayah/{provinsi,kota,kecamatan,kelurahan}` |
| Admin | `/admin/login`, `/admin` (dashboard), `/users`, `/reports`, `/orders`, `/affiliates`, `/system` |
| Sistem | `/up` (health bawaan Laravel), `/health` (JSON `{status, ts}`), `/horizon` |

Lihat daftar lengkap dengan `php artisan route:list` (69 rute).

---

## 6. Database

Tabel utama (PostgreSQL): `users`, `birth_profiles`, `questionnaire_sessions`, `ai_reports`, `orders`, `subscriptions`, `pdf_downloads`, `email_verifications`, `affiliates`, `affiliate_commissions`, `wilayah_provinsi|kota|kecamatan|kelurahan`, serta tabel framework (`sessions`, `cache`, `jobs`, `failed_jobs`, `job_batches`, `password_reset_tokens`).

Relasi inti:

```
users 1─1 birth_profiles
users 1─* orders          users 1─* subscriptions      users 1─* ai_reports
users *─1 affiliates      affiliates 1─* affiliate_commissions *─1 orders
```

Kolom khusus: `orders.gateway_order_id (RL-xxxx), payment_id, payment_link_url, payment_expires_at` (kolom lama `unique_code / transfer_amount / bank_tujuan` tak dipakai lagi), `ai_reports.data_hash / locale`, `users.referred_by_affiliate_id`.

> **Perhatian:** migration project ini mengandung SQL mentah khusus Postgres (mis. `ALTER COLUMN ... DROP NOT NULL`), sehingga tidak bisa dijalankan di sqlite.

---

## 7. Admin Panel (`/admin`)

Login memakai `ADMIN_USERNAME` / `ADMIN_PASSWORD` dari `.env` (password boleh teks biasa atau hash bcrypt), dibatasi 5 percobaan/menit; flag `admin_logged_in` di session. Menu:

- **Dashboard:** statistik pengguna/laporan; **pendapatan hari ini / 7 hari / 30 hari / rata-rata per hari** (hanya order lunas berbayar; "hari" = hari WIB; akses gratis dari admin bernominal Rp0 tidak dihitung); pelanggan aktif sebenarnya (status aktif **dan** masa langganan belum berakhir); laporan hari ini (tengah malam WIB).
- **Users:** cari/filter; buat akun uji; reset password; hapus (**riwayat komisi afiliator tetap disimpan** — kunci asing `affiliate_commissions.user_id/order_id` kini `SET NULL`, bukan cascade). Klik nama → **Detail pengguna**.
- **Detail pengguna** (`/admin/users/{id}`): profil (email terverifikasi, lahir, daftar, afiliator pembawa, apakah sudah punya password), jatah per laporan (total/terpakai/sisa), riwayat langganan, semua order (dengan **Kirim ulang bukti**), 15 laporan terakhir (bahasa, waktu WIB, memakai jatah atau tidak), dan **Beri akses gratis**: *beri jatah* (laporan berbayar saja, 1–10 paket × 3 jatah) dan *beri langganan* (1/3/6/12 bulan, menyambung masa aktif). Pemberian dicatat sebagai **order lunas bernominal Rp0, `payment_method='komplimen'`, `gateway_order_id='GRANT-…'`, dengan kolom `catatan`** — tidak masuk pendapatan, tanpa komisi afiliasi (komisi diabaikan untuk amount ≤ 0), tanpa email bukti bayar.
- **Laporan:** daftar laporan AI per fitur.
- **Email** (`/admin/email`): **riwayat setiap email keluar** untuk konsolidasi pemakaian dan rekonsiliasi dengan SumoPod — filter periode (hari WIB), jenis, status, cari penerima/pengguna; ringkasan (terkirim, gagal, perkiraan biaya = jumlah × `PAYMENT_BIAYA_EMAIL`, default Rp5); rincian **per jenis**, **per hari (WIB)**, **per pengguna (15 teratas)**, baris terpisah untuk **email uji** (tidak menempel ke pengguna) dan penerima bukan pengguna; kotak **Rekonsiliasi** (isi jumlah email menurut dashboard SumoPod untuk periode sama → selisih + penjelasan kemungkinan penyebab); **ekspor CSV** (waktu WIB) mengikuti filter. Di **Detail pengguna** ada kartu pemakaian email (jumlah, biaya, per jenis, 10 terbaru, tautan ke riwayat lengkap).
- **Orders:** kolom **Pembayaran** menampilkan metode (QRIS / GRATIS (admin) / transfer lama), ID pembayaran SumoPod, masa berlaku link untuk order pending dan catatan; nama produk; tautan ke detail pengguna; aksi **Konfirmasi/Tolak** (cadangan bila webhook gagal) dan **Kirim ulang bukti** (hanya order berbayar yang lunas, pengguna harus punya email).
- **Afiliator:** pendaftar menunggu persetujuan, tambah manual, komisi siap dibayar/ditahan, tandai lunas.
- **Sistem → Status layanan:** ringkasan kesehatan per layanan (**email, Gemini, pembuatan QRIS, webhook SumoPod, Hoki Harian**: status, terakhir berhasil, jumlah gagal 24 jam, kegagalan terakhir), **penjadwal/cron** (detak tiap menit; peringatan bila > 5 menit), **Horizon** (berjalan/dijeda/mati), kelengkapan konfigurasi (kunci Gemini, kunci & secret SumoPod, host SMTP — hanya terisi/kosong, tanpa nilai) dan **mode SumoPod (LIVE/SANDBOX)**; tombol **Tes kirim email** dan **Tes Gemini** (hasil ditampilkan apa adanya); tombol bersihkan cache dan antrean gagal.

**Riwayat email (`email_logs`, `App\Support\EmailLogger`):** setiap email yang diterima server SMTP dicatat lewat event `MessageSent` (satu baris per penerima): jenis, penerima, pengguna (dicari dari email penerima, tak peka huruf besar-kecil), status, biaya saat itu, waktu. **Jenis dibawa header `X-RL-Jenis`** yang dipasang tiap Mailable (`otp`, `login_kode`, `bukti_bayar`, `hoki_harian`, `afiliasi_disetujui`) dan email uji (`tes_admin` dari panel, `tes_cli` dari `mail:uji`); tanpa header = `lainnya`. **Jenis berawalan `tes` tidak pernah dikaitkan ke pengguna** walau alamatnya milik pengguna. Kegagalan dicatat `status='gagal'`, `biaya=0` di titik pengiriman (kode OTP/login, Hoki, bukti bayar, persetujuan afiliasi, tes) dan untuk pekerjaan antrean email gagal sebagai jenis `antrean` (tanpa rincian penerima). Riwayat **tidak ikut terhapus saat pengguna dihapus** (`user_id` jadi NULL, FK `SET NULL`) dan disimpan ±400 hari. **Email baru wajib memasang header jenis** (tambahkan di `Envelope(using: [...])`) agar tercatat dengan jenis yang benar. Pencatatan tidak pernah mengganggu pengiriman. Catatan akurasi: "terkirim" berarti **diterima server SMTP SumoPod** (itu yang menimbulkan biaya), bukan jaminan sampai di Inbox.

**Cara kerja pencatatan status layanan** (`App\Support\LayananStatus`, tabel `service_events`): keberhasilan hanya menyimpan penanda waktu terakhir di cache (murah); **kegagalan** disimpan sebagai baris `service_events` (dibuang setelah 30 hari oleh penjadwal) dan dihitung 24 jam terakhir. Titik pencatatan: kirim kode OTP/login (`EmailOtpService`), event `MessageSent` (email sukses), pekerjaan antrean email gagal (`JobFailed` → `dariJobGagal`), Gemini (rate-limit 429, HTTP gagal, sukses), pembuatan QRIS, webhook SumoPod (signature salah, nominal tak cocok, event valid), Hoki Harian (ringkasan harian), bukti bayar & email persetujuan afiliasi. Layanan ditandai **"perlu dicek"** bila kegagalan terakhir lebih baru daripada keberhasilan terakhir dalam 24 jam. Pencatatan **tidak pernah mengganggu alur utama** (semua kegagalan menulis dengan `try/catch`). Cron yang harus hidup: `schedule:run` tiap menit.

---

## 7b. Backup database (`db:backup`, `db:uji-pulih`)

- **Jadwal:** `db:backup` otomatis **setiap hari 02:00 WIB** (penjadwal Laravel). Bisa dijalankan manual: `php artisan db:backup`.
- **Cara kerja** (`App\Support\DatabaseBackup`): `pg_dump -Fc` (klien PostgreSQL 16 dipasang di server) hanya skema `public` (skema uji `rumuslangit_test` tidak ikut), kata sandi dilewatkan lewat **environment proses** (tidak muncul di argumen/log), ditulis ke berkas `.tmp` lalu di-`rename` (tidak ada berkas setengah jadi). **Verifikasi isi**: `pg_restore --list` harus berhasil dan memuat tabel inti (`users, orders, subscriptions, ai_reports, affiliates, affiliate_commissions, email_logs`); jika tidak, berkas dihapus dan backup dinyatakan gagal.
- **Lokasi:** `/home/ubuntu/backup-db/` (folder izin 700, berkas 600; `BACKUP_DIR`). **Rotasi:** semua backup 14 hari terakhir, lalu 1 backup per bulan untuk 6 bulan terakhir (`BACKUP_HARIAN_SIMPAN`, `BACKUP_BULANAN_SIMPAN`).
- **Pemantauan:** muncul di Admin → Sistem → Status layanan ("Backup database"); ditandai **perlu dicek** bila gagal atau bila keberhasilan terakhir lebih dari **26 jam** lalu (jadwal macet).
- **Uji pulih aman:** `php artisan db:uji-pulih` memuat isi **setiap tabel** dari backup ke **tabel sementara** di dalam transaksi yang selalu dibatalkan, lalu membandingkan jumlah baris dan checksum dengan database hidup (tabel hidup hanya dibaca). Dibuat karena akun database tidak boleh membuat database untuk uji pulih biasa. Hasil pertama (11 Nov 2026): 25 tabel pulih, checksum semua cocok kecuali `cache` (wajar berubah).
- **Batasan yang diketahui:** salinan ini **berada di server yang sama** dengan aplikasi — melindungi dari kesalahan data/penghapusan, **bukan** dari hilangnya server. Untuk perlindungan penuh: salin ke penyimpanan lain (mis. SumoPod Object Storage) dan/atau pastikan backup otomatis di sisi layanan database SumoPod. Untuk **memulihkan** sungguhan: `pg_restore --clean --if-exists -n public -d <database> <berkas>` (setelah membuat database kosong/persetujuan; jangan dijalankan di database produksi tanpa rencana).

---

## 8. Konfigurasi Environment

Variabel yang dipakai (nilai rahasia jangan di-commit; `.env` sudah di-gitignore):

| Grup | Variabel |
|---|---|
| App | `APP_NAME, APP_ENV, APP_KEY, APP_DEBUG, APP_URL, APP_LOCALE` |
| Database | `DB_CONNECTION=pgsql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_SSLMODE` |
| Redis | `SESSION_DRIVER=redis, QUEUE_CONNECTION=redis, CACHE_DRIVER=redis, REDIS_*` |
| Mail | `MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_SCHEME (smtps untuk port 465), MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_*`. Domain pengirim harus punya SPF/DKIM (DNS domain dikelola di SumoPod); tanpa itu email OTP masuk spam/terbuang |
| Gemini | `GEMINI_API_KEY, GEMINI_MODEL` |
| Cadangan AI (xAI Grok / Groq) | `XAI_API_KEY, XAI_MODEL` (wajib keduanya agar aktif), `XAI_BASE_URL` (default `https://api.x.ai/v1`; Groq: `https://api.groq.com/openai/v1`), `XAI_TIMEOUT` (120 dtk), `XAI_RUANG_PENALARAN` (2500), `XAI_REASONING_EFFORT` (opsional) |
| Pembayaran | `PAYMENT_EXPIRE_HOURS, PAYMENT_LANGGANAN_BULANAN_HARGA, PAYMENT_LANGGANAN_TAHUNAN_HARGA` |
| Admin | `ADMIN_USERNAME, ADMIN_PASSWORD` |
| SumoPod Payment (QRIS) | `SUMOPOD_PAY_API_KEY, SUMOPOD_PAY_WEBHOOK_SECRET, SUMOPOD_PAY_BASE_URL` (default `https://api-pay.sumopod.com`) |

`.env.example` masih berisi template bawaan Laravel (sqlite) dan **belum** mencantumkan variabel di atas.

**Pembersihan `.env` (11 Nov 2026):** dihapus variabel yang tidak dipakai — `CACHE_DRIVER` (Laravel 13 membaca `CACHE_STORE`, jadi nilai ini diabaikan), `VITE_APP_NAME`, `GEMINI_MAX_OUTPUT_TOKENS`, sisa integrasi lama (`FONNTE_*`, `WHATSAPP_PROVIDER`, `WAHA_*`, `DUITKU_*`), placeholder `AWS_*` bawaan skeleton, dan komentar placeholder. `PAYMENT_TEST_PRICE`/`PAYMENT_TEST_EMAIL` **tetap ada tetapi dikomentari (`#`) / nonaktif** — hapus tanda `#` lalu `config:cache` bila perlu tes transaksi kecil, lalu komentari lagi setelahnya. Kredensial lama yang sudah tidak dipakai (Fonnte, WAHA, Duitku) disimpan terpisah di `/home/ubuntu/backup/kredensial-lama-tidak-dipakai.env` (izin 600, di luar repo); bila layanannya tidak akan dipakai lagi, cabut/regenerate token di penyedianya. Cadangan `.env` sebelum dibersihkan: `/home/ubuntu/backup/` (berisi rahasia, hapus bila tak dibutuhkan). **Catatan:** `cache.default` saat ini **`database`** (bukan Redis) karena `CACHE_STORE` tidak diatur; OTP, rate limiter, dan cache Gemini memakai tabel `cache`. Untuk memindah ke Redis set `CACHE_STORE=redis` lalu `config:cache` (kode OTP yang sedang aktif hangus).

---

## 9. Menjalankan & Deploy

### Pengembangan lokal
```bash
composer install && npm install --ignore-scripts
cp .env.example .env && php artisan key:generate   # lalu isi DB/Redis/Gemini
php artisan migrate
composer dev        # serve + queue:listen + pail + vite
```

### Produksi (server `genji`)
```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl reload php8.3-fpm
php artisan horizon:terminate      # supervisor akan menjalankan ulang Horizon
```

Proses yang harus hidup: **nginx**, **php-fpm**, **`php artisan horizon`** (worker queue), dan **cron** untuk scheduler (`* * * * * php artisan schedule:run`).

Scheduler ([bootstrap/app.php](bootstrap/app.php)): `hoki:kirim` harian 06:00 WIB (= 23:00 UTC); `subscription:sync` tiap jam. **Syarat berjalan:** cron user `ubuntu` menjalankan `* * * * * cd /var/www/rumuslangit && php artisan schedule:run` (sudah terpasang; cek dengan `crontab -l` dan `php artisan schedule:list`).

**Hoki Harian (`hoki:kirim`):** email otomatis tiap pagi ke pelanggan yang `subscription_status='active'`, email **terverifikasi**, **dan** masa langganan (`ends_at`) belum berakhir (tidak bergantung pada sinkronisasi status per jam). Isi (3 angka + 2 warna + mantra) dihitung deterministik dari weton, id pengguna, dan **tanggal WIB** (angka berganti tengah malam WIB; tanggal di subjek/isi email juga WIB — email 06.00 WIB sebelumnya salah menampilkan tanggal kemarin karena UTC) — **tanpa AI**, jadi tidak ada biaya Gemini; biaya hanya 1 email (Rp5) per pelanggan per hari. Dikirim satu per satu secara sinkron; **kegagalan per pengguna dicatat** `Log::warning('Hoki harian gagal dikirim')` dan ringkasan `Log::info('Hoki harian selesai', terkirim/gagal/total)` di `laravel.log` (output konsol cron dibuang, jadi log inilah yang dipantau; penyebab gagal paling umum: saldo email SumoPod habis). Untuk skala besar (ratusan pelanggan) pertimbangkan memindahkan pengiriman ke queue.

Perintah artisan kustom: `hoki:kirim`, `subscription:sync`, `mail:uji {email}` (kirim email uji + tampilkan konfigurasi SMTP aktif; berguna setelah mengganti `MAIL_*`).

> **Jebakan config cache:** `bootstrap/cache/config.php` menyimpan konfigurasi produksi. Setelah mengubah `.env`, jalankan `php artisan config:cache`. Pastikan folder `storage/framework/views` dan `bootstrap/cache` dapat ditulis grup `www-data` (`chgrp -R www-data` + `chmod -R g+w`), kalau tidak halaman bisa 500 saat Blade dikompilasi ulang.

---

## 10. Testing

```bash
php artisan test       # atau: composer test
```

50 tes: `KalkulasiEngineTest`, `RepairJsonTest` (unit), `AdminLoginTest`, `SubscriptionPaymentTest`, `AffiliateTest`, `ProfilStatusTest`, `SumopodPaymentTest` (checkout QRIS, webhook: signature, replay, idempotensi, nominal, afiliasi), serta tes contoh.

- **Database tes terisolasi.** Tes yang butuh DB memakai trait [tests/Concerns/UsesPostgresTestSchema.php](tests/Concerns/UsesPostgresTestSchema.php): skema PostgreSQL terpisah **`rumuslangit_test`** di server DB yang sama (dibuat dan dimigrasi otomatis), tiap tes dibungkus transaksi yang di-rollback. Skema produksi `public` tidak disentuh, dan ada pengaman yang menggagalkan tes bila skema aktif bukan `rumuslangit_test`. (Migration memakai SQL khusus Postgres sehingga sqlite tidak bisa dipakai.)
- **Cache produksi diabaikan.** [tests/TestCase.php](tests/TestCase.php) mengarahkan `APP_CONFIG_CACHE` dan `APP_ROUTES_CACHE` ke file yang tidak ada, jadi `bootstrap/cache/*.php` (config/route produksi) tidak memengaruhi tes dan `php artisan test` aman dijalankan langsung.

---

## 11. Keamanan

### Status patch (10 Nov 2026)
- **PHP2Shell (CVE-2026-109618 / CVE-2026-109619, RCE tanpa autentikasi):** paket Ubuntu `php8.3 8.3.6-0ubuntu0.24.04.13` memuat patch `CVE-2026-109618_109619.patch` (Ubuntu mem-backport; nomor versi tetap 8.3.6). php-fpm berjalan sejak 8 Nov, setelah paket ter-patch. Hardening tambahan: `session.upload_progress.enabled=0` di `/etc/php/8.3/fpm/conf.d/99-hardening.ini` (fitur ini tidak dipakai aplikasi). Pencarian web shell / file PHP mencurigakan: tidak ditemukan.
- **CVE-2026-115560** (RCE lanjutan yang dilaporkan belum punya patch pada saat pencarian): belum ada patch/mitigasi terverifikasi — pantau advisory PHP dan `apt` (`unattended-upgrades`).
- **Dependensi Composer:** `laravel/framework` 13.12 → 13.40 (termasuk advisory *critical* TokenGuard), Symfony, Guzzle, dompdf, league/commonmark, flysystem diperbarui. `composer audit` → bersih.

### Temuan yang sudah diperbaiki (10 Nov 2026)
1. **Login admin:** perbandingan kredensial kini constant-time (`hash_equals`), mendukung `ADMIN_PASSWORD` plaintext atau hash bcrypt (`$2y$...`), menolak kredensial kosong, regenerasi session saat login, dan `POST /admin/login` dibatasi **5 percobaan/menit** (`throttle:5,1`).
2. **Rekening bank** dikeluarkan dari kode dan `.env` (fitur transfer manual dihapus). **Catatan:** nomor lama tetap ada di riwayat git (commit sebelumnya) — menghapusnya butuh menulis ulang riwayat, belum dilakukan.
3. **API key Gemini** dikirim lewat header `x-goog-api-key`, bukan query string.
4. **Tes tidak lagi menyentuh data produksi** (skema `rumuslangit_test`, lihat bagian 10).
5. Kode legacy WhatsApp (`otp:test`, `waha:status`, config `whatsapp/fonnte/waha/duitku`) dihapus.

### Yang perlu diketahui
- Session pengguna hanya `user_id` — pemegang session mewarisi akses; OTP email adalah satu-satunya verifikasi kepemilikan.
- Variabel `.env` legacy (`FONNTE_*`, `WAHA_*`, `WHATSAPP_PROVIDER`, `DUITKU_*`) sudah tidak dibaca kode dan boleh dihapus dari `.env`.

---

## 12. Kondisi Kode yang Perlu Diketahui

- Model `PdfDownload` + paket dompdf sudah ada tanpa route unduh PDF.
- `payment.expire_hours` belum dipakai oleh job apa pun (order pending tidak kedaluwarsa otomatis).
- PRD menyebut harga lama (Rp19.000 / Rp15.000 per bulan) dan Laravel 11; harga sebenarnya di bagian 3.
- Setelah mengubah kode di server produksi: `config:cache`, `route:cache`, `view:cache`, `sudo systemctl reload php8.3-fpm`, dan **`php artisan horizon:terminate`** (worker queue menyimpan kode lama di memori sampai di-restart; supervisor menjalankannya ulang otomatis).

---

## 13. Riwayat Perubahan Terbaru

> Aturan kerja: setiap perubahan **wajib** dicatat di dokumen ini dan di-push ke GitHub (`rakaipikatan/rumuslangit`, branch `main`).

| Commit | Isi |
|---|---|
| `82b3712` | `rakit.py`: berkas suara rusak/kosong (sisa percobaan 503) dideteksi dan dibuat ulang; kecepatan bicara bawaan +20% (`--kecepatan N`) dan batas percepatan otomatis 1,4× karena suara Indonesia otomatis hanya ±1,6 kata/detik (naskah ±100 kata melebihi durasi adegan S4, S5, S8 pada kecepatan normal). Uji laptop pemilik: perakitan visual berhasil, kegagalan hanya dari `v3.mp3` rusak |
| `8c556eb` | `rakit.py`: pembuatan suara mencoba ulang hingga 5× dengan jeda dan jeda antar-adegan (layanan suara gratis kadang menjawab 503), dan bila tetap gagal video tetap dirakit tanpa suara adegan itu. Temuan uji di laptop pemilik (Windows, Python 3.14 mandiri uv): pemasangan lewat `venv` (uv menolak pip global), perakitan adegan dan end card berhasil; suara S1–S2 jadi, S3 kena 503 |
| `627c8fc` | `rakit.bat` membaca lokasi Python dari `jalur_python.txt` (untuk Python yang tidak ada di PATH; pemilik memakai Python 3.14 mandiri di `D:\Program Files\Python\…`), dan berhenti dengan pesan jelas bila pemasangan komponen gagal |
| `beb7b82` | **`rakit.py --hemat`** (prioritas CPU terendah, 1 thread, preset `veryfast`) untuk bila terpaksa dijalankan di VPS; petunjuk alternatif VPS di `promosi/produksi/PETUNJUK.md`. Kondisi VPS saat dicek: RAM 1,9 GB (sisa ±700 MB), swap terpakai ±770 MB → tidak disarankan untuk pengolahan video |
| `57d855c` | **Perakit video otomatis** (`promosi/rakit.py` + `rakit.bat`, FFmpeg lewat `imageio-ffmpeg` + Pillow; berjalan di laptop Windows, **bukan di server** karena server hanya 2 core/1,9 GB dan dipakai bersama aplikasi lain): menyusun klip mentah `produksi/NN/s1..s8` menjadi video 4:5 1080×1350 30 fps (potong/tahan frame, teks layar, logo kecil S2–S7, label "Contoh tampilan" S5–S6, end card dengan logo + tagline + URL + disclaimer, suara, musik dengan ducking). **Voice-over bahasa Indonesia otomatis** `--suara` (suara perempuan `id-ID-GadisNeural` / laki-laki `id-ID-ArdiNeural` lewat pustaka `edge-tts`, per adegan, otomatis dipercepat maks. 1,35× bila melebihi durasi adegan; berkas `v*.mp3` bisa diganti rekaman sendiri). Data adegan `promosi/data/*.json` dihasilkan dari sumber prompt yang sama. **Belum teruji ujung-ke-ujung** (uji dengan FFmpeg di server ditolak pengaman); hanya sintaks dan pengurai durasi yang diperiksa — uji pertama di laptop dengan `rakit.bat 00 --pratinjau --izinkan-kosong`. Catatan: `edge-tts` memakai layanan suara daring Microsoft Edge secara tidak resmi (abu-abu untuk penggunaan komersial) — alternatif aman: suara bawaan CapCut atau rekaman sendiri |
| `caab725` | **Paket video promosi** (`promosi/`): 13 prompt video 58 detik (satu per laporan: Profil Karakter gratis + 12 fitur), **rasio 4:5 / 1080×1350** (standar feed Instagram, aman dipotong 1:1), tiap berkas berisi tokoh & kasus, shot list 8 adegan (hook → masalah → titik balik → cara pakai → laporan → langkah nyata → transformasi → CTA), voice-over Indonesia, teks layar, caption, negative prompt, dan **kunci konsistensi karakter** (deskripsi baku tokoh, prompt character sheet, gambar referensi + seed sama, blok kunci disalin persis). Logo aplikasi dipotong untuk menghapus tagline berbahasa Inggris (`promosi/aset/logo-rumus-langit.png`; `public/images/logo.png` tidak diubah). Isi tidak menyebut teknologi/pihak ketiga dan tidak menjanjikan hasil pasti |
| `b92ed5d` | **Bug verifikasi email:** bila kode OTP lolos saat sesi belum punya akun (mis. calon pengguna memverifikasi dulu, baru mengisi data lahir), email tidak pernah menempel ke akun sehingga status "belum terverifikasi", tanpa email, dan tidak bisa login dengan kode (OTP kedua ditolak karena "batas trial harian"). Kini email yang lolos OTP disimpan di sesi dan dipasang ke akun saat pendaftaran selesai (kecuali sudah dipakai akun terverifikasi lain); +2 tes (total 232). Akun #124 diperbaiki manual (bukti: catatan verifikasi OTP 15:20 WIB); akun duplikat #121 (nama sama, tanpa email) dibiarkan. Email ketiga ditambahkan ke harga uji Rp1.000 (`PAYMENT_TEST_EMAIL`, berlaku sampai 16:31 WIB) |
| `6502771` | **Login admin tanpa pembatas percobaan** (sebelumnya 5/menit per IP dan membuat pemilik terkena 429): permintaan pemilik karena admin hanya satu orang. Konsekuensi: kata sandi admin bisa ditebak tanpa batas — **wajib password panjang & unik** (ganti password lama yang pernah tampil di percakapan); tes diganti memastikan tidak ada 429 (total 230) |
| `7eed9a1` | **Koreksi teks payout afiliator:** sebelumnya tertulis komisi "dibayar/ditransfer otomatis" — tidak akurat. Yang otomatis hanya pemrosesan (pembuatan payout tgl 25); **transfer uang tetap dilakukan admin sekali sebulan (bulk dari CSV)** segera setelahnya. Teks di /afiliasi, T&C, dashboard, panduan afiliator, dan FAQ chat diperbaiki (ID/EN). Transfer otomatis penuh butuh penyedia disbursement (belum dibangun); yang sudah diuji hanya logika payout, bukan transfer nyata |
| `bc0f78b` | **Nama penyedia tidak ditampilkan ke pelanggan:** teks publik tidak lagi menyebut Gemini/Groq/SumoPod/"AI" (landing, Kebijakan Privasi/Syarat, FAQ & aturan chat bantuan; "Profil Karakter AI" → "Profil Karakter"); Privasi memakai pernyataan generik "penyedia layanan teknologi/pembayaran/email pihak ketiga" (tetap ada pengungkapan pemrosesan oleh pihak ketiga). Chat bantuan diinstruksikan tidak menyebut nama penyedia. Panel admin tidak diubah. +1 tes (total 230) |
| `60db502` | **Perbaikan bug privasi (nama orang lain muncul di laporan):** kunci cache hasil AI (`GeminiService`) hanya memuat data lahir + jawaban, tanpa profil, sehingga dua orang berdata lahir sama berbagi hasil (laporan akun #119 memuat nama akun lama yang sudah dihapus). Kini profil (nama, gender, lokasi, dst.) ikut kunci; +1 tes (total 229). Satu laporan profil salah (id 19, gratis) dihapus agar dibuat ulang; tidak ada laporan lain yang terdampak. Catatan: cache lama tidak terbaca lagi (kunci berubah) dan kedaluwarsa sendiri ≤24 jam |
| `3dd247d` | **Halaman legal:** Kebijakan Privasi, Syarat Layanan, Kebijakan Refund (tanpa refund) + tautan footer, ID/EN; chat bantuan sebagai satu-satunya kontak; +2 tes (total 228) |
| `d83762c` | **Dashboard afiliator + payout otomatis + panduan + chat bantuan:** afiliator masuk dengan kode email dan punya dashboard sendiri (pendaftar, pelanggan membayar, komisi, saldo, riwayat payout); payout dibuat otomatis tiap tgl 25 bila saldo siap ≥ Rp100.000 (admin cukup unduh CSV, transfer massal, klik tandai); panduan afiliator + banner panduan pelanggan; **chat bantuan otomatis** (AI → FAQ) tanpa kontak admin, dengan log di admin; T&C diperbarui; 3 tabel/kolom baru (**perlu `migrate`**); +14 tes (total 226) |
| `1b52249` | **Jam lahir isian bebas HH:MM** (bukan dropdown) di form pendaftaran/trial dan data pasangan (Jodoh): contoh `07:30`, boleh `.` sebagai pemisah, keterangan "*Tidak harus diisi jika lupa" (ID/EN); kosong = dianggap 00; yang dipakai perhitungan hanya jam (menit hanya dicek formatnya); helper `App\Support\JamLahir`; +3 tes (total 212) |
| `48cf4fe` | **Harga uji untuk beberapa email** (`PAYMENT_TEST_EMAIL` dipisah koma; tetap wajib terverifikasi dan berlaku sampai `PAYMENT_TEST_UNTIL`); email penguji kedua ditambahkan; +2 tes (total 209) |
| `1ebf61d` | **Tes AI cadangan jujur:** menguji jalur laporan nyata (JSON valid + ruang penalaran); balasan kosong tidak lagi dinyatakan "berfungsi"; +2 tes (total 207) |
| `c9ad313` | **AI cadangan aktif di produksi (Groq, `openai/gpt-oss-120b`):** uji ujung-ke-ujung berhasil (Gemini dilewati karena kuota habis → laporan Tafsir Mimpi dari Groq 5,2 dtk, JSON valid); ruang token untuk model penalaran; +1 tes (total 205). Konfigurasi `.env`: `XAI_BASE_URL` (Groq), `XAI_MODEL=openai/gpt-oss-120b` |
| `f1b50eb` | **AI cadangan: deteksi kunci Groq vs xAI Grok** — kunci `gsk_` (Groq) ditolak xAI; tombol uji kini menjelaskan ketidakcocokan kunci/alamat tanpa mengirim kunci, label UI dibuat generik ("AI cadangan"); +2 tes (total 204) |
| `795d5d8` | **Peralihan AI otomatis Gemini → Grok (cadangan):** `GrokService` (API kompatibel OpenAI), orkestrasi di `GeminiService` (alih saat gagal, penanda jeda kuota harian, galat Gemini diteruskan bila keduanya gagal), `ai_reports.provider`, status "AI Grok (cadangan)" + tombol **Tes Grok** (menampilkan daftar model) di panel Sistem; +13 tes (total 202) |
| `30ce66d` | **Gemini kuota habis (paket gratis 20/hari):** diagnosis; penanganan kuota diperbaiki (baca waktu tunggu resmi, pesan jelas di panel, pekerjaan laporan menunggu sampai 8 jam dan selesai sendiri, halaman tunggu jujur setelah 3 menit); checklist: aktifkan billing Gemini (teratas); +5 tes (total 189) |
| `34f35cf` | **Perbaikan alur pemilihan paket:** halaman `/langganan` (bulanan & tahunan berdampingan) menjadi tujuan tombol di katalog + tautan ganti paket di halaman bayar langganan (sebelumnya paket tahunan tidak terlihat dari katalog); tes selalu memulai dengan harga uji dimatikan agar `.env` produksi tidak bocor ke tes; +5 tes (total 184) |
| `1450f69` | **Persiapan tes pembayaran Live 3 produk:** pengaman `PAYMENT_TEST_UNTIL` (harga uji mati otomatis) + peringatan "HARGA UJI AKTIF" di panel Sistem; harga uji diaktifkan 6 jam untuk akun pemilik (Rp1.000 untuk satuan/bulanan/tahunan; pengguna lain tetap normal); +2 tes (total 179) |
| `9a7593a` | **Backup database otomatis** (`db:backup` harian 02:00 WIB, verifikasi isi, rotasi 14 hari + 6 bulan, izin aman, status di panel) dan **`db:uji-pulih`** (uji pulih aman ke tabel sementara); klien `postgresql-client-16` dipasang; +10 tes (total 177) |
| `4181277` | **Pencatatan lengkap hari ini:** bagian 15 (ringkasan pembaruan 10–11 Nov per tema + log kronologis dari git, 34 commit, berpenanda agar bisa dibuat ulang) dan aturan "setiap perubahan wajib dicatat"; checklist peluncuran (bagian 14) |
| `4cb3a54` | **Bukti uji tiga alur pembayaran** (satuan, bulanan, tahunan) uji-ujung-ke-ujung (`AlurPembayaranLengkapTest`, 4 tes / 122 pemeriksaan) + **checklist peluncuran & operasional** (bagian 14); temuan: perpanjangan langganan saat masih aktif diblokir, tidak ada pengingat kedaluwarsa, tidak ada halaman kebijakan/kontak (total 209 tes) |
| `10db7fb` | **Riwayat email:** semua email keluar dicatat (jenis, penerima, pengguna, status, biaya); halaman admin **Email** (ringkasan, per jenis/hari/pengguna, email uji terpisah, rekonsiliasi dengan SumoPod, ekspor CSV) + kartu email di detail pengguna; email uji tidak menempel ke pengguna; migration `email_logs`; +14 tes (total 163) |
| `1271562` | **Panel admin 1–3:** (1) Status layanan (email, Gemini, QRIS, webhook, Hoki, cron, Horizon, konfigurasi, mode SumoPod) + tombol tes email/Gemini; (2) detail pengguna + beri jatah/langganan gratis (order Rp0 `komplimen`); (3) kirim ulang bukti bayar + tabel order yang benar. Perbaikan: komisi afiliator tidak lagi terhapus bersama pengguna, dashboard memakai hari WIB + pendapatan per periode. 3 migration (`service_events`, `orders.catatan`, FK komisi `SET NULL`); +17 tes (total 149) |
| `0b89b89` | **Harga langganan naik:** bulanan Rp49.000 → **Rp59.000**, tahunan Rp399.000 → **Rp499.000** (hemat 30%); tes tidak lagi mengunci angka lama (komisi dan nominal mengikuti konfigurasi) |
| `2e74fde` | **Perbaikan email Hoki Harian:** "Krem &amp; Oranye" (kode HTML tampil mentah akibat escape ganda) dan tanggal email yang memakai UTC (tampil tanggal kemarin pada pengiriman 06.00 WIB); angka hoki kini berdasar tanggal WIB; +3 tes (total 132) |
| `6628866` | **Hoki Harian:** kegagalan kirim kini dicatat ke log (sebelumnya hilang), penerima hanya pelanggan yang masa langganannya belum berakhir; tes (jadwal, penerima, log kegagalan); dokumentasi cron/penjadwal (total 209 tes) |
| `4c511bb` | **Pembersihan `.env`** (24 entri tak relevan dihapus: legacy WhatsApp/WAHA/Duitku, AWS, `CACHE_DRIVER`, `VITE_APP_NAME`, `GEMINI_MAX_OUTPUT_TOKENS`, harga uji) dan konfigurasi `gemini.max_tokens` yang tak terpakai |
| `8d0bee4` | **Halaman Panduan Pengguna** `/panduan` (setelah login); teks jatah diperjelas ("3 jatah laporan" + tautan "Apa itu jatah?"); aturan hasil diselaraskan dengan keputusan pemilik: **hari sama = hasil sama, hari berikutnya = laporan baru (tanpa memotong jatah), jatah hanya untuk jawaban berbeda**, berlaku untuk semua fitur (`PAYMENT_FITUR_HARIAN=semua` default); +5 tes (total 126) |
| `2231b85` | **Fitur 3, 10, 11 juga memakai data tersimpan** (pembaruan harian jadi opsi `PAYMENT_FITUR_HARIAN`, default kosong); halaman hasil kini menampilkan tanggal pembuatan laporan (sebelumnya salah menampilkan tanggal hari ini); catatan QRIS untuk pembeli luar negeri di halaman bayar; +2 tes (total 121) |
| `193ba86` | **Jawaban sama = pakai data tersimpan (tanpa AI)** untuk semua fitur kecuali yang bergantung tanggal (3, 10, 11) yang diperbarui tiap hari WIB; prompt fitur bergantung tanggal kini memuat tanggal hari ini; +4 tes (total 119) |
| `fdd0e2c` | **Jatah hanya untuk jawaban berbeda:** jawaban sama (hari lain/bahasa lain) membuat laporan baru tanpa memotong jatah; percobaan gagal-lalu-diulang tidak memotong dua kali; teks form/paywall disesuaikan; +3 tes (total 115) |
| `1534d32` | **Aturan hari yang sama/besok:** laporan identik hanya dipakai ulang bila dibuat hari ini (WIB); besoknya dibuat baru. Cache Gemini per hari kalender WIB (bukan 24 jam bergulir). Batas harian kini berganti tengah malam WIB (sebelumnya 07.00 WIB karena UTC). Helper `HariWib`; +4 tes (total 112) |
| `523d10f` | **Skema harga baru:** satuan Rp29.000 = 3 kali pembuatan (hasil tersimpan selamanya) per pembelian; langganan tahunan Rp350.000 → **Rp399.000**; batas wajar harian (langganan 20/hari, fitur gratis 5/hari); halaman "laporan terakhir"; teks harga/akses diperbarui (tidak ada lagi "akses selamanya" / "12 laporan" yang menyesatkan); kolom `ai_reports.consumed_quota`; +10 tes (total 108) |
| `900b7d9` | **Pengingat cek folder Spam/Junk** di semua email dan pesan layar terkait pengiriman email (OTP, login kode, bukti bayar, Hoki Harian, persetujuan afiliasi) + 3 tes (total 98) |
| `118db64` | **Email bukti pembayaran otomatis** setelah order lunas (queue, sekali per order, HTML+teks) + 5 tes (total 95) |
| `b50ffd9` | **Aturan komisi afiliasi:** batas 12 bulan sejak pembayaran pertama pelanggan, tanpa komisi untuk pembelian sendiri, masa tahan 14 hari sebelum bisa dibayar; **Syarat & Ketentuan** ditampilkan di `/afiliasi` dan dikirim di email persetujuan; +4 tes (total 90) |
| `a4673ee` | **Halaman pendaftaran afiliator** `/afiliasi` + persetujuan di admin + email link referral otomatis; migration `affiliate_applications`; +11 tes (total 86) |
| `7b7bfd4` | **Menu Pengaturan akun** untuk membuat/mengubah password (⚙ di navbar); +4 tes (total 75) |
| `24aa214` | **Login dengan kode email** (tanpa password) di `/login`, dengan pembatasan percobaan; login password dilipat. Admin → Users menampilkan "N laporan dibeli" (status `free` = bukan pelanggan langganan; pembelian satuan tidak mengubah status). +6 tes (total 71) |
| `ca24ee5` | **Transfer bank manual dihapus** (halaman bayar, controller, config rekening, teks bahasa, env `PAYMENT_BCA/CIMB`); QRIS jadi satu-satunya metode. Konfirmasi admin dipertahankan sebagai cadangan; rekening untuk **payout komisi afiliator** tidak terkait dan tetap. Tes disesuaikan (total 65) |
| `2b3b60b` | **Perbaikan alur pasca-bayar QRIS:** return URL per-payment ke halaman `payment.selesai` (tunggu konfirmasi → otomatis ke kuesioner), katalog & upsell menandai fitur yang sudah dibeli, pesan sukses; +8 tes (total 63) |
| `23f8049` | **Bugfix:** Tafsir Mimpi (freemium, Rp0) salah menampilkan paywall "Bayar Sekarang" dan membuat order QRIS; kini gratis untuk semua. Fitur Rp0 tak bisa dibeli eceran, harga uji tak berlaku untuk fitur gratis; +3 tes (total 55) |
| `6d66fd1` | Email OTP kini punya versi teks polos (mengurangi risiko spam) + tes; harga uji dinaikkan ke Rp1.000 (minimum SumoPod). DNS email: SPF, DKIM (`trx_ke._domainkey`), verifikasi SumoPod terpasang dan terverifikasi; MAIL_* dipindah ke SMTP SumoPod (`smtp.sumopod.com:465`, smtps). Disarankan menambah DMARC (`_dmarc` TXT `v=DMARC1; p=none;`) |
| `e178033` | Perintah `mail:uji` untuk memeriksa pengiriman email. Temuan: domain belum punya SPF/DKIM/DMARC sehingga OTP via Brevo tidak sampai; rencana pindah ke SMTP SumoPod (`smtp.sumopod.com:465`, `MAIL_SCHEME=smtps`) |
| (10 Nov 2026) | Kredensial admin diganti (di `.env`, bukan di repo): username baru, password acak 20 karakter disimpan sebagai hash bcrypt (`ADMIN_PASSWORD='$2y$...'`, tanda kutip tunggal wajib). Cadangan `.env` lama di `/home/ubuntu/backup/` |
| `128f84d` | **Pembersihan data:** semua user + data turunan (laporan AI, kuesioner, profil lahir, langganan, order) dihapus atas permintaan pemilik, dengan cadangan JSON di server (`/home/ubuntu/backup/`, di luar repo). Perbaikan: katalog dengan sesi usang (user sudah dihapus) kini diarahkan ke awal, bukan error; +1 tes (total 51) |
| `6ee9559` | Harga uji sementara per-email (`PAYMENT_TEST_PRICE`/`PAYMENT_TEST_EMAIL`) untuk tes transaksi Live bernominal kecil; +2 tes (total 50) |
| `e203f75` | Integrasi **QRIS SumoPod Managed Payment**: service klien API, webhook ber-signature Svix, `OrderSettlementService` bersama, pilihan metode di halaman pembayaran, migration kolom payment, 14 tes baru. **Belum aktif di produksi** sampai migration dijalankan dan `config:cache`/`route:cache` di-refresh (lihat bagian 9) |
| `080df47` | **Bugfix produksi:** profil karakter menggantung di "Sedang menganalisis..." — Gemini mengembalikan JSON dengan trailing comma sehingga data tersimpan tapi tak terbaca. Perbaikan: `repairJson` + validasi + self-healing + tes regresi. Plus: hardening login admin (throttle), rekening ke `.env`, tes pada skema DB terpisah, hapus kode WhatsApp legacy, API key Gemini via header |
| `0a837b8`, `7e8789e` | Patch keamanan: update dependensi Composer (laravel/framework 13.40, Symfony, Guzzle, dompdf, commonmark), hardening PHP2Shell, dokumentasi ini |
| `619f858` | Program afiliasi (referral, komisi, payout manual) |
| `5329d05` | Harga baru: laporan Rp29rb, langganan Rp49rb/bulan, tahunan Rp350rb |
| `077e5f8` | Fix crash langganan bulanan di paywall & aktivasi |
| `8554ac3` | Fix title-case singkatan DKI |
| `6283fcb` | Initial commit |

---

## 14. Checklist peluncuran & operasional (per 11 Nov 2026)

Tandai `[x]` bila selesai. **Hasil verifikasi hari ini:** ketiga alur pembayaran (satuan, bulanan, tahunan) lulus uji-ujung-ke-ujung (halaman bayar → QRIS → menunggu → webhook → akses → bukti bayar → komisi → webhook ganda tak menggandakan → masa berlaku); server: disk 65%, 0 migration tertunda, 0 antrean gagal, `APP_DEBUG=false`, SSL Let's Encrypt diperpanjang otomatis (`certbot.timer`; berakhir 27 Des 2026), SPF/DKIM/DMARC terpasang, `composer audit` bersih.

### A. Wajib sebelum promosi / iklan / mengajak afiliator
- [ ] **Aktifkan AI cadangan:** isi `XAI_API_KEY` di `.env` (langsung di server, jangan di chat); kunci `gsk_` = Groq → set juga `XAI_BASE_URL=https://api.groq.com/openai/v1`; kunci `xai-` = xAI Grok (alamat bawaan) → `php artisan config:cache` → tekan **Tes AI cadangan** (Admin → Sistem) → isi `XAI_MODEL` dari daftar yang tampil → `config:cache` → tekan tombol lagi. Periksa syarat penggunaan data & batas paket gratis xAI; cantumkan xAI di Kebijakan Privasi. Cadangan melengkapi, **bukan** menggantikan billing Gemini.
- [ ] **🔴 Aktifkan billing Gemini API** (Google AI Studio → API keys → proyek → *Set up billing*; prabayar, minimal sekitar US$10 menurut dokumentasi Google, saldo harus positif agar permintaan dilayani). Saat ini paket gratis: **20 permintaan/hari** habis → laporan tak bisa dibuat. Langganan Google AI Plus/Pro **tidak** menambah kuota API. Setelah aktif, pastikan Admin → Sistem → "Tes Gemini" berhasil dan pasang peringatan tagihan.
- [ ] **Halaman hukum**: Kebijakan Privasi (data lahir + email dikumpulkan), Syarat Layanan, Kebijakan Refund/Pembatalan; tautan di footer dan halaman bayar. *(Belum ada.)*
- [ ] **Kanal dukungan** (email/WhatsApp) ditampilkan di panduan, FAQ, dan footer; pasang **Reply-To** email bukti bayar ke kotak masuk nyata (pengirim `noreply@` tidak punya kotak masuk, padahal email menyuruh "balas email ini"). *(Belum ada.)*
- [ ] **Perpanjangan & upgrade langganan**: pelanggan yang masih aktif saat ini **diblokir** membayar lagi (`PaymentController::konfirmasi` mengalihkan), jadi tidak bisa memperpanjang sebelum habis atau upgrade bulanan → tahunan; masa aktif sudah mendukung penyambungan (`aktifkanLangganan`). Izinkan pembelian saat aktif.
- [ ] **Pengingat langganan hampir habis** (email H-3 dan H-1) — saat ini tidak ada; pelanggan baru tahu setelah akses hilang.
- [ ] **Tes pembayaran Live ulang untuk 3 produk** setelah semua perubahan: aktifkan sementara `PAYMENT_TEST_PRICE=1000` + `PAYMENT_TEST_EMAIL` (hapus `#`, `config:cache`), uji satuan/bulanan/tahunan sampai akses terbuka + bukti bayar masuk, lalu komentari lagi.
- [ ] **Top up kredit email SumoPod** (Rp5/email; sebelumnya 60 email) dan catat sisa kredit sebagai titik awal rekonsiliasi (Admin → Email).
- [x] **Backup database** harian 02:00 WIB + verifikasi + rotasi + `db:uji-pulih` (11 Nov 2026, bagian 7b). **Sisa:** salinan di **luar server** (Object Storage SumoPod) dan konfirmasi backup otomatis di layanan database SumoPod.
- [ ] **Pemantau uptime eksternal** untuk `https://rumuslangit.biz.id/health` dengan notifikasi (mis. UptimeRobot).
- [ ] **Peringatan tagihan Gemini** di Google Cloud (batas bulanan) agar biaya AI tak melonjak tanpa terlihat.

### B. Minggu pertama
- [ ] **Alur refund**: kebijakan dulu, lalu fitur "tandai refund" (cabut akses/langganan + batalkan komisi + status order). *(Belum dibangun; saat ini manual.)*
- [ ] **Uji afiliasi nyata**: daftar `/afiliasi` → setujui di admin → email persetujuan masuk → buka link `?ref=` → beli → komisi tercatat → (14 hari) siap dibayar.
- [ ] **Cek tampilan HP**: beranda, hasil, katalog, paywall, bayar, panduan, `/afiliasi`, admin.
- [ ] **Deliverability**: kirim OTP ke Gmail, Yahoo, Outlook; "Tampilkan asli" harus SPF/DKIM/DMARC = PASS; tandai "Bukan spam" bila masuk Spam.
- [ ] **Rotasi log** (`LOG_STACK=daily` atau logrotate) — `laravel.log` saat ini satu berkas tanpa rotasi.
- [ ] **Cabut token lama** yang tak dipakai (Fonnte, WAHA, Duitku) di dashboard masing-masing.
- [ ] **Ganti password admin** lalu simpan di password manager (password sekarang pernah tampil di percakapan).
- [ ] Putuskan **`CACHE_STORE`** (sekarang `database`, bukan Redis).
- [ ] Hapus berkas sensitif yang tak perlu di `/home/ubuntu/backup/` (cadangan `.env`, ekspor pengguna JSON).

### C. Pemantauan rutin
- **Harian:** Admin → Sistem → Status layanan semua "normal"; antrean gagal = 0; cron berdetak.
- **Mingguan:** sisa kredit email SumoPod; Admin → Email → rekonsiliasi; tagihan Gemini; ruang disk; pesan dukungan.
- **Tanggal 25:** bayar komisi afiliasi (Admin → Afiliator → hanya komisi "Siap dibayar"), tandai lunas.
- **Bulanan:** ekspor CSV email/order untuk pembukuan; `composer audit`; `apt` security update PHP; evaluasi harga/margin.
- **Tahunan:** perpanjang domain (berakhir 31 Mei 2027); SSL otomatis (cek `certbot certificates` sekitar 27 Nov 2026).

### D. Setelah ada transaksi nyata
- [ ] Ekspor CSV order & daftar payout afiliator; pendapatan per produk; catatan aksi admin; 2FA/IP-allowlist untuk admin; evaluasi harga Rp59.000/Rp499.000 dengan data konversi; evaluasi biaya AI per laporan.

---

## 15. Catatan pembaruan 10–11 November 2026

> **Aturan:** setiap perubahan **wajib** dicatat di dokumen ini (tabel riwayat bagian 13, bagian teknis terkait, dan log di bawah). Log kronologis bagian 15.2 dibuat dari git: perbarui dengan menjalankan skrip pembuat log (atau tambahkan baris baru di bawah penanda) setiap selesai bekerja.

### 15.1 Ringkasan per tema
- **Keamanan & server.** Pemeriksaan isu PHP2Shell (CVE-2026-109618/109619): paket Ubuntu `8.3.6-0ubuntu0.24.04.13` sudah memuat patch, `session.upload_progress` dimatikan; dependensi Composer diperbarui (Laravel 13.12 → 13.40, Symfony, Guzzle, dompdf, commonmark; `composer audit` bersih); login admin: perbandingan constant-time, mendukung hash bcrypt, throttle 5/menit, kredensial admin diganti; rekening bank dikeluarkan dari kode ke `.env` lalu dibuang saat transfer manual dihapus; tes memakai skema database terpisah (`rumuslangit_test`), bukan data produksi.
- **Perbaikan bug produksi.** Profil karakter menggantung di "Sedang menganalisis…" (JSON Gemini dengan koma berlebih — kini diperbaiki, divalidasi, dan bisa memulihkan diri); Tafsir Mimpi (gratis) salah menampilkan paywall dan membuat order Rp0; email Hoki Harian menampilkan `&amp;` mentah dan tanggal UTC (tampil kemarin); komisi afiliator ikut terhapus saat pengguna dihapus; dashboard memakai hari UTC; tanggal di halaman hasil memakai hari ini, bukan tanggal pembuatan laporan; katalog dengan sesi usang tidak lagi error; kegagalan kirim Hoki tidak tercatat (kini tercatat).
- **Pembayaran.** Integrasi QRIS SumoPod Managed Payment (klien API, webhook bertanda tangan Svix, pelunasan idempoten bersama, halaman "menunggu → otomatis ke kuesioner", penanda "Sudah dibeli", batas minimum Rp1.000, host Sandbox vs Live); **transfer bank manual dihapus** (konfirmasi admin dipertahankan sebagai cadangan); harga uji sementara per-email (kini dikomentari); email **bukti pembayaran** otomatis; sandbox → **Live** (key dan secret Live aktif, uji nyata Rp1.000 berhasil).
- **Harga & akses.** Skema baru: satuan Rp29.000 = **3 jatah** (hasil tersimpan selamanya); langganan bulanan **Rp59.000** (dari Rp49.000) dan tahunan **Rp499.000** (dari Rp350.000 → Rp399.000 → Rp499.000), hemat 30%; jatah hanya terpakai untuk **jawaban berbeda**; hari yang sama = hasil sama, hari berikutnya = laporan baru tanpa memotong jatah; konteks tanggal untuk fitur 3/10/11; batas harian (pelanggan 20, fitur gratis 5); halaman **Panduan Pengguna** (ID/EN) dan teks jatah yang diperjelas.
- **Akun & email.** Login dengan **kode email** (tanpa password) + menu **Pengaturan** untuk membuat password; email OTP via **SMTP SumoPod** dengan SPF/DKIM/DMARC; pengingat cek folder Spam di semua email dan pesan layar; versi teks polos untuk email OTP; perintah `mail:uji`; pembersihan **`.env`** (24 entri tak relevan dihapus; kredensial lama disimpan terpisah).
- **Afiliasi.** Halaman pendaftaran publik `/afiliasi` + persetujuan admin + email link referral; aturan komisi (maks. 12 bulan sejak pembayaran pertama, tanpa komisi pembelian sendiri, tahan 14 hari) dan **Syarat & Ketentuan**.
- **Panel admin.** **Status layanan** (email, Gemini, QRIS, webhook, Hoki, cron, Horizon, konfigurasi, mode SumoPod, tombol tes), **detail pengguna** + beri jatah/langganan gratis (order Rp0 `komplimen`), kirim ulang bukti bayar, tabel order yang benar, dashboard **pendapatan per periode (WIB)**, **riwayat email** (per jenis/hari/pengguna, email uji tak menempel ke pengguna, rekonsiliasi dengan SumoPod, ekspor CSV).
- **Data & operasional.** Seluruh data pengguna uji dihapus atas permintaan pemilik (cadangan JSON di server, di luar repo); akun admin baru; `.env` dibersihkan; **checklist peluncuran** (bagian 14); bukti uji tiga alur pembayaran uji-ujung-ke-ujung (`AlurPembayaranLengkapTest`); jumlah tes naik dari 23 menjadi **167**.

### 15.2 Log kronologis (dari git, waktu WIB)
<!-- LOG-GIT:MULAI -->
| Commit | Waktu (WIB) | Perubahan |
|---|---|---|
| `0a837b8` | 10/11 21:59 | Patch keamanan dependensi (PHP2Shell hardening, Laravel 13.40, Symfony, Guzzle, dompdf) + dokumentasi teknis |
| `7e8789e` | 10/11 22:00 | Rapikan tabel riwayat perubahan di dokumentasi |
| `080df47` | 10/11 22:04 | Fix profil karakter menggantung (JSON Gemini trailing comma) + hardening keamanan |
| `e203f75` | 10/11 22:14 | Integrasi QRIS SumoPod Managed Payment (checkout, webhook Svix, settlement bersama) |
| `48e2866` | 10/11 22:29 | Dokumentasi: host Sandbox vs Live SumoPod Payment |
| `6ee9559` | 10/11 22:39 | Harga uji sementara khusus satu email untuk tes transaksi Live |
| `128f84d` | 10/11 22:51 | Katalog: tangani sesi usang (user sudah dihapus) + catat pembersihan data |
| `50d9784` | 10/11 22:53 | Catat penggantian kredensial admin di dokumentasi |
| `e178033` | 10/11 23:01 | Tambah perintah mail:uji untuk memeriksa pengiriman email |
| `6d66fd1` | 10/11 23:23 | Email OTP: tambah versi teks polos; catat batas minimum SumoPod Rp1.000 |
| `23f8049` | 10/11 23:25 | Fix: Tafsir Mimpi (freemium) gratis, tak lagi memunculkan paywall/order Rp0 |
| `2b3b60b` | 10/11 23:40 | Perbaiki alur setelah bayar QRIS: langsung ke kuesioner, tandai fitur sudah dibeli |
| `ca24ee5` | 10/11 23:48 | Hapus pembayaran transfer bank manual; QRIS jadi satu-satunya metode |
| `24aa214` | 10/11 23:54 | Login dengan kode email (tanpa password) + penanda pembelian di admin |
| `7b7bfd4` | 10/11 23:56 | Menu pengaturan akun: buat/ubah password |
| `a4673ee` | 11/11 00:01 | Halaman pendaftaran afiliator publik + persetujuan admin |
| `b50ffd9` | 11/11 00:12 | Aturan komisi afiliasi: batas 12 bulan, tanpa komisi pembelian sendiri, tahan 14 hari + Syarat & Ketentuan |
| `118db64` | 11/11 00:15 | Email bukti pembayaran otomatis setelah order lunas |
| `900b7d9` | 11/11 00:23 | Pengingat cek folder Spam/Junk di semua email dan pesan terkait |
| `523d10f` | 11/11 00:32 | Terapkan skema harga baru: satuan = 3 jatah pembuatan, tahunan Rp399.000, batas wajar harian |
| `1534d32` | 11/11 00:34 | Laporan identik dipakai ulang hanya di hari WIB yang sama; besok dibuat baru |
| `3499414` | 11/11 00:35 | Dokumentasi: biaya email Rp5 dan acuan margin per transaksi |
| `fdd0e2c` | 11/11 00:38 | Jatah hanya terpakai untuk jawaban yang berbeda |
| `193ba86` | 11/11 00:45 | Jawaban sama memakai laporan tersimpan tanpa AI; hanya fitur bergantung tanggal diperbarui harian |
| `2231b85` | 11/11 00:48 | Fitur 3/10/11 memakai data tersimpan (harian jadi opsi env); tanggal hasil = tanggal pembuatan; catatan QRIS luar negeri |
| `8d0bee4` | 11/11 00:55 | Halaman Panduan Pengguna; teks jatah diperjelas; aturan hasil harian untuk semua fitur |
| `4c511bb` | 11/11 00:57 | Bersihkan .env: hapus setelan tak relevan; hapus gemini.max_tokens yang tak terpakai |
| `cf45857` | 11/11 00:59 | Dokumentasi: harga uji dikomentari (bukan dihapus); lokasi kredensial lama |
| `6628866` | 11/11 01:03 | Hoki Harian: catat kegagalan kirim, hanya pelanggan yang masa aktifnya belum berakhir; tes + dokumentasi |
| `2e74fde` | 11/11 01:05 | Perbaiki email Hoki Harian: ampersand ganda dan tanggal WIB |
| `0b89b89` | 11/11 01:10 | Harga langganan: bulanan Rp59.000, tahunan Rp499.000 |
| `1271562` | 11/11 01:18 | Panel admin: status layanan, detail pengguna + akses gratis, kirim ulang bukti, dashboard WIB |
| `10db7fb` | 11/11 10:06 | Riwayat email: catat semua email keluar, halaman admin Email, rekonsiliasi SumoPod, CSV |
| `4cb3a54` | 11/11 10:14 | Tes uji-ujung-ke-ujung 3 alur pembayaran + checklist peluncuran/operasional |
| `4181277` | 11/11 10:20 | Dokumentasi: catatan pembaruan 10-11 Nov (ringkasan per tema + log git kronologis) |
| `9a7593a` | 11/11 10:29 | Backup database otomatis harian + verifikasi + uji pulih aman |
| `1450f69` | 11/11 10:31 | Pengaman waktu harga uji + peringatan panel Sistem; persiapan tes pembayaran Live |
| `34f35cf` | 11/11 10:51 | Halaman pilih paket langganan (bulanan & tahunan) + tautan ganti paket |
| `30ce66d` | 11/11 11:08 | Gemini: tangani kuota habis (waktu tunggu resmi, pesan jelas, pekerjaan menunggu 8 jam) + halaman tunggu jujur |
| `795d5d8` | 11/11 11:23 | Peralihan AI otomatis Gemini -> Grok (cadangan) + status panel + tombol Tes Grok |
| `f1b50eb` | 11/11 11:28 | AI cadangan: deteksi kunci Groq vs xAI, label generik, pesan uji yang jelas |
| `c9ad313` | 11/11 11:32 | AI cadangan: ruang token untuk model penalaran + reasoning_effort opsional; aktif di produksi (Groq gpt-oss-120b) |
| `1ebf61d` | 11/11 11:35 | Tes AI cadangan menguji jalur laporan nyata; balasan kosong bukan keberhasilan |
| `48cf4fe` | 11/11 13:01 | Harga uji untuk beberapa email (dipisah koma) |
| `1b52249` | 11/11 13:18 | Jam lahir isian bebas HH:MM (boleh kosong) menggantikan dropdown |
| `d83762c` | 11/11 14:28 | Dashboard afiliator, payout otomatis, panduan afiliator, chat bantuan otomatis |
| `3dd247d` | 11/11 14:46 | Halaman legal: privasi, syarat, refund (tanpa kontak email; chat sebagai saluran) |
| `60db502` | 11/11 14:51 | Perbaiki cache AI: profil ikut kunci agar nama orang lain tidak bocor |
| `bc0f78b` | 11/11 14:53 | Sembunyikan nama penyedia teknologi dari teks yang dilihat pelanggan |
| `7eed9a1` | 11/11 14:57 | Koreksi teks payout afiliator: pemrosesan otomatis, transfer oleh admin |
| `6502771` | 11/11 15:27 | Hapus pembatas percobaan login admin (admin hanya satu orang) |
| `b92ed5d` | 11/11 15:33 | Perbaiki email yang lolos OTP sebelum akun dibuat agar menempel ke akun |
| `caab725` | 11/11 16:53 | Paket video promosi: 13 prompt 4:5 dengan kunci konsistensi karakter + logo tanpa tagline |
| `57d855c` | 11/11 17:04 | Perakit video otomatis (rakit.py) + voice-over bahasa Indonesia otomatis; data adegan JSON |
| `beb7b82` | 11/11 17:08 | rakit.py: mode --hemat untuk server kecil; petunjuk alternatif VPS |
| `627c8fc` | 11/11 17:09 | rakit.bat: dukung lokasi Python kustom (jalur_python.txt) |
| `8c556eb` | 11/11 18:31 | rakit.py: coba ulang pembuatan suara (503) dan lanjut tanpa suara bila gagal |
| `82b3712` | 11/11 18:33 | rakit.py: pulihkan suara rusak, kecepatan bicara +20%, batas percepatan 1,4x |
<!-- LOG-GIT:AKHIR -->
