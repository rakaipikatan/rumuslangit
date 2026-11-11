<?php

namespace Tests\Feature;

use App\Mail\BuktiBayarMail;
use App\Mail\HokiHarianMail;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

/**
 * Bukti uji-ujung-ke-ujung untuk TIGA alur pembayaran: laporan satuan, langganan bulanan, langganan tahunan.
 * Tiap alur: halaman bayar → buat QRIS → menunggu → webhook lunas → arah setelah bayar → akses yang diperoleh
 * → bukti bayar → komisi afiliasi → webhook ganda tidak menggandakan → katalog → masa berlaku.
 */
class AlurPembayaranLengkapTest extends TestCase
{
    use UsesPostgresTestSchema;

    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPostgresTestSchema();
        Cache::flush();

        $this->secret = 'whsec_' . base64_encode(random_bytes(24));
        config([
            'services.sumopod_pay.api_key'        => 'k',
            'services.sumopod_pay.webhook_secret' => $this->secret,
            'services.sumopod_pay.base_url'       => 'https://api-pay.sumopod.test',
        ]);

        Http::fake(['*' => fn () => Http::response([
            'payment_id' => (string) Str::uuid(), 'payment_link_url' => 'https://pay.sumopod.test/pay/x',
            'expires_at' => now()->addDay()->toIso8601String(),
        ])]);
    }

    protected function tearDown(): void
    {
        $this->tearDownPostgresTestSchema();
        parent::tearDown();
    }

    private function webhook(array $payload)
    {
        $body = json_encode($payload);
        $id = 'msg_' . Str::random(10);
        $ts = (string) time();
        $sig = base64_encode(hash_hmac('sha256', "{$id}.{$ts}.{$body}", base64_decode(str_replace('whsec_', '', $this->secret)), true));

        return $this->call('POST', route('webhook.sumopod'), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_SVIX_ID' => $id, 'HTTP_SVIX_TIMESTAMP' => $ts, 'HTTP_SVIX_SIGNATURE' => 'v1,' . $sig,
        ], $body);
    }

    /** Pelanggan baru yang dibawa afiliator (email afiliator berbeda). */
    private function pelanggan(): array
    {
        $aff = Affiliate::create(['name' => 'Afiliator', 'email' => 'aff-' . strtolower(Str::random(6)) . '@example.test',
            'referral_code' => 'T' . strtoupper(Str::random(6)), 'komisi_persen' => 20, 'status' => 'active']);
        $u = User::create([
            'name' => 'Pembeli', 'email' => strtolower(Str::random(8)) . '@example.test', 'password' => bcrypt('x'),
            'dob' => '1990-01-01', 'birth_hour' => 8, 'email_verified_at' => now(), 'referred_by_affiliate_id' => $aff->id,
        ]);

        return [$u, $aff];
    }

    /**
     * Jalankan satu alur penuh sampai lunas dan kembalikan [user, order].
     * @return array{0: User, 1: Order, 2: Affiliate}
     */
    private function bayar(string $slug, int $harga, string $namaTampil): array
    {
        Mail::fake();
        [$u, $aff] = $this->pelanggan();
        $sesi = ['user_id' => $u->id];

        // 1) halaman bayar menampilkan produk & harga
        $this->withSession($sesi)->get(route('payment.konfirmasi', $slug))
            ->assertOk()->assertSee($namaTampil)->assertSee('Rp ' . number_format($harga, 0, ',', '.'));

        // 2) buat QRIS → diarahkan ke halaman bayar SumoPod; order pending berjumlah harga
        $this->withSession($sesi)->post(route('payment.proses', $slug), [])->assertRedirect('https://pay.sumopod.test/pay/x');
        $order = Order::where('user_id', $u->id)->firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame($harga, $order->amount);
        $this->assertSame('qris', $order->payment_method);
        $this->assertNotEmpty($order->payment_id);

        // 3) sebelum dibayar belum ada akses / email / komisi; halaman "menunggu" tampil
        $this->assertFalse($u->fresh()->isSubscriber());
        Mail::assertNothingQueued();
        $this->withSession($sesi)->get(route('payment.selesai', $order->gateway_order_id))->assertOk()->assertSee('dikonfirmasi');

        // 4) webhook lunas
        $event = ['event_type' => 'payment.completed', 'data' => ['payment_id' => $order->payment_id, 'order_id' => $order->gateway_order_id, 'amount' => $harga]];
        $this->webhook($event)->assertOk();
        $this->assertSame('settlement', $order->fresh()->status);

        // 5) bukti bayar diantrekan satu kali, komisi afiliator 20% tercatat
        Mail::assertQueued(BuktiBayarMail::class, 1);
        Mail::assertQueued(BuktiBayarMail::class, fn ($m) => $m->hasTo($u->email));
        $this->assertSame((int) round($harga * 0.2), (int) AffiliateCommission::where('order_id', $order->id)->value('komisi_amount'));

        // 6) webhook ganda: tidak menggandakan email maupun komisi
        $this->webhook($event)->assertOk();
        Mail::assertQueued(BuktiBayarMail::class, 1);
        $this->assertSame(1, AffiliateCommission::where('affiliate_id', $aff->id)->count());

        return [$u->fresh(), $order->fresh(), $aff];
    }

    public function test_alur_laporan_satuan(): void
    {
        [$u, $order] = $this->bayar('3', 29000, 'Hari Baik');
        $sesi = ['user_id' => $u->id];

        // setelah bayar → langsung ke kuesioner laporan yang dibeli
        $this->withSession($sesi)->get(route('payment.selesai', $order->gateway_order_id))->assertRedirect(route('laporan.form', 3));

        // akses: 3 jatah untuk fitur ITU saja; BUKAN pelanggan
        $this->assertFalse($u->isSubscriber());
        $this->assertTrue($u->hasAccessToFeature(3));
        $this->assertSame(3, $u->kuotaSisa(3));
        $this->assertFalse($u->hasAccessToFeature(4), 'fitur lain tetap berbayar');
        $this->assertSame('free', $u->subscription_status ?: 'free');
        $this->assertSame(0, $u->subscriptions()->count());

        // katalog menandai fitur yang dibeli; tanpa langganan → tombol bayar fitur lain tetap berharga
        $this->withSession($sesi)->get(route('trial.katalog'))->assertOk()->assertSee('Sudah dibeli');

        // membuka form → ada info jatah; membeli ulang saat jatah masih ada diarahkan ke form (tak perlu beli lagi)
        $this->withSession($sesi)->get(route('laporan.form', 3))->assertOk()->assertSee('Sisa jatah: 3 dari 3');
        $this->withSession($sesi)->get(route('payment.konfirmasi', '3'))->assertRedirect(route('laporan.form', 3));

        // Hoki Harian TIDAK dikirim ke pembeli satuan
        Mail::fake();
        $this->artisan('hoki:kirim')->assertSuccessful();
        Mail::assertNotSent(HokiHarianMail::class);

        // tidak ada masa berlaku untuk akses satuan
        $this->travelTo(now()->addYears(2));
        $this->assertTrue($u->fresh()->hasAccessToFeature(3));
    }

    public function test_alur_langganan_bulanan(): void
    {
        [$u, $order] = $this->bayar('langganan-bulanan', 59000, 'Langganan Bulanan');
        $sesi = ['user_id' => $u->id];

        // setelah bayar → katalog (semua laporan terbuka)
        $this->withSession($sesi)->get(route('payment.selesai', $order->gateway_order_id))->assertRedirect(route('trial.katalog'));

        $this->assertTrue($u->isSubscriber());
        $sub = $u->subscriptions()->latest('id')->first();
        $this->assertSame('monthly', $sub->plan);
        $this->assertEqualsWithDelta(now()->addMonth()->timestamp, $sub->ends_at->timestamp, 5);
        foreach ([1, 2, 3, 5, 9, 10, 11, 12] as $fid) {
            $this->assertTrue($u->hasAccessToFeature($fid), "fitur {$fid}");
        }
        $this->assertSame('active', $u->subscription_status);

        $this->withSession($sesi)->get(route('trial.katalog'))->assertOk()->assertSee('Bebas');

        // Hoki Harian terkirim ke pelanggan aktif
        Mail::fake();
        $this->artisan('hoki:kirim')->assertSuccessful();
        Mail::assertSent(HokiHarianMail::class, fn ($m) => $m->hasTo($u->email));

        // MASIH aktif → halaman bayar langganan dialihkan ("sudah punya langganan aktif")
        $this->withSession($sesi)->get(route('payment.konfirmasi', 'langganan-bulanan'))->assertRedirect(route('trial.katalog'));

        // masa berlaku habis → akses & Hoki berhenti (tanpa menunggu sinkronisasi per jam)
        $this->travelTo($sub->ends_at->copy()->addMinute());
        $habis = $u->fresh();
        $this->assertFalse($habis->isSubscriber());
        $this->assertFalse($habis->hasAccessToFeature(3));
        $this->assertTrue($habis->hasAccessToFeature(12), 'fitur gratis tetap terbuka');
        Mail::fake();
        $this->artisan('hoki:kirim')->assertSuccessful();
        Mail::assertNotSent(HokiHarianMail::class);

        // setelah habis, halaman bayar langganan terbuka lagi (perpanjangan)
        $this->withSession($sesi)->get(route('payment.konfirmasi', 'langganan-bulanan'))->assertOk()->assertSee('Rp 59.000');
    }

    public function test_alur_langganan_tahunan(): void
    {
        [$u, $order] = $this->bayar('langganan-tahunan', 499000, 'Langganan Tahunan');
        $sesi = ['user_id' => $u->id];

        $this->withSession($sesi)->get(route('payment.selesai', $order->gateway_order_id))->assertRedirect(route('trial.katalog'));

        $this->assertTrue($u->isSubscriber());
        $sub = $u->subscriptions()->latest('id')->first();
        $this->assertSame('yearly', $sub->plan);
        $this->assertEqualsWithDelta(now()->addMonths(12)->timestamp, $sub->ends_at->timestamp, 5);
        $this->assertTrue($u->hasAccessToFeature(11), 'fitur khusus langganan terbuka');

        // masih aktif di bulan ke-11, habis setelah 12 bulan
        $this->travelTo(now()->addMonths(11));
        $this->assertTrue($u->fresh()->isSubscriber());
        $this->travelTo($sub->ends_at->copy()->addMinute());
        $this->assertFalse($u->fresh()->isSubscriber());

        // bukti bayar memuat produk & masa aktif tahunan
        $mail = new BuktiBayarMail($order);
        $mail->assertSeeInHtml('Langganan Tahunan');
        $mail->assertSeeInHtml('Rp 499.000');
    }

    public function test_perbedaan_tiga_alur_dalam_satu_tabel(): void
    {
        // Produk berbeda pada penanda order, harga, dan hak akses — selebihnya alur pembayaran sama.
        $this->assertSame(-1, Order::SUBSCRIPTION_MONTHLY_FEATURE_ID);
        $this->assertSame(-2, Order::SUBSCRIPTION_YEARLY_FEATURE_ID);
        $this->assertSame(59000, (int) config('payment.subscription_monthly_price'));
        $this->assertSame(499000, (int) config('payment.subscription_yearly_price'));
        $this->assertSame(3, (int) config('payment.single_generations'));
    }
}
