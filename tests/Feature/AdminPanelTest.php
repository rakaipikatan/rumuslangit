<?php

namespace Tests\Feature;

use App\Mail\BuktiBayarMail;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use App\Support\LayananStatus;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use UsesPostgresTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPostgresTestSchema();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        $this->tearDownPostgresTestSchema();
        parent::tearDown();
    }

    private function admin()
    {
        return $this->withSession(['admin_logged_in' => true]);
    }

    private function user(array $o = []): User
    {
        return User::create(array_merge([
            'name' => 'Pengguna ' . Str::random(4), 'email' => strtolower(Str::random(8)) . '@example.test',
            'dob' => '1990-01-01', 'birth_hour' => 8, 'email_verified_at' => now(),
        ], $o));
    }

    private function order(User $u, array $o = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $u->id, 'feature_id' => 3, 'amount' => 29000, 'payment_method' => 'qris',
            'gateway_order_id' => 'RL-' . strtoupper(Str::random(12)), 'status' => 'settlement', 'settled_at' => now(),
        ], $o));
    }

    // ─────────────── Dashboard ───────────────

    public function test_dashboard_pendapatan_per_periode_wib_dan_tanpa_akses_gratis(): void
    {
        $this->travelTo(Carbon::parse('2026-11-11 03:00:00', 'UTC')); // 11 Nov 10:00 WIB
        $u = $this->user();
        $this->order($u, ['amount' => 59000, 'settled_at' => Carbon::parse('2026-11-10 20:00:00', 'UTC')]); // 11 Nov 03:00 WIB = hari ini
        $this->order($u, ['amount' => 29000, 'settled_at' => Carbon::parse('2026-11-10 10:00:00', 'UTC')]); // 10 Nov 17:00 WIB = kemarin
        $this->order($u, ['amount' => 49000, 'settled_at' => Carbon::parse('2026-08-01 10:00:00', 'UTC')]); // lama
        $this->order($u, ['amount' => 0, 'payment_method' => 'komplimen', 'gateway_order_id' => 'GRANT-X']);   // gratis
        $this->order($u, ['amount' => 99000, 'status' => 'pending', 'settled_at' => null]);                    // belum lunas

        $this->admin()->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['revenue_hari_ini'] === 59000
                && $s['order_hari_ini'] === 1
                && $s['revenue_7_hari'] === 88000
                && $s['revenue_30_hari'] === 88000
                && $s['revenue_settled'] === 137000
                && $s['total_orders'] === 4); // tanpa komplimen
    }

    public function test_dashboard_pelanggan_aktif_hanya_yang_masa_langganannya_belum_berakhir(): void
    {
        $aktif = $this->user(['subscription_status' => 'active']);
        Subscription::create(['user_id' => $aktif->id, 'plan' => 'monthly', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(5), 'auto_renew' => false]);
        $lewat = $this->user(['subscription_status' => 'active']);
        Subscription::create(['user_id' => $lewat->id, 'plan' => 'monthly', 'starts_at' => now()->subDays(40), 'ends_at' => now()->subDay(), 'auto_renew' => false]);

        $this->admin()->get(route('admin.dashboard'))
            ->assertViewHas('stats', fn ($s) => $s['subscribers_aktif'] === 1);
    }

    public function test_laporan_hari_ini_berganti_tengah_malam_wib(): void
    {
        $u = $this->user();
        $this->travelTo(Carbon::parse('2026-11-10 16:50:00', 'UTC')); // 23:50 WIB 10 Nov
        \App\Models\AiReport::create(['user_id' => $u->id, 'feature_id' => 3, 'locale' => 'id', 'data_hash' => Str::random(20), 'response_text' => 'x', 'cached_at' => now()]);

        $this->travelTo(Carbon::parse('2026-11-10 17:10:00', 'UTC')); // 00:10 WIB 11 Nov
        $this->admin()->get(route('admin.dashboard'))->assertViewHas('stats', fn ($s) => $s['reports_hari_ini'] === 0);

        $this->travelTo(Carbon::parse('2026-11-10 16:55:00', 'UTC'));
        $this->admin()->get(route('admin.dashboard'))->assertViewHas('stats', fn ($s) => $s['reports_hari_ini'] === 1);
    }

    // ─────────────── Status layanan ───────────────

    public function test_ringkasan_layanan_perlu_dicek_bila_kegagalan_lebih_baru_dari_keberhasilan(): void
    {
        $this->travelTo(Carbon::parse('2026-11-11 03:00:00', 'UTC'));
        LayananStatus::ok('email');
        $this->assertFalse(LayananStatus::ringkas('email')['perlu_cek']);

        $this->travelTo(Carbon::parse('2026-11-11 03:10:00', 'UTC'));
        LayananStatus::gagal('email', 'SMTP: saldo habis');
        $r = LayananStatus::ringkas('email');
        $this->assertTrue($r['perlu_cek']);
        $this->assertSame(1, $r['gagal_24j']);

        $this->travelTo(Carbon::parse('2026-11-11 03:20:00', 'UTC'));
        LayananStatus::ok('email'); // pulih
        $this->assertFalse(LayananStatus::ringkas('email')['perlu_cek']);

        $this->travelTo(Carbon::parse('2026-11-13 03:20:00', 'UTC')); // kegagalan lama keluar dari jendela 24 jam
        $this->assertSame(0, LayananStatus::ringkas('email')['gagal_24j']);
    }

    public function test_email_yang_terkirim_memperbarui_penanda_dan_antrean_email_gagal_tercatat(): void
    {
        $this->assertNull(LayananStatus::ringkas('email')['terakhir_ok']);

        \Illuminate\Support\Facades\Mail::raw('uji', fn ($m) => $m->to('a@example.test')->subject('uji')); // mailer array
        $this->assertNotNull(LayananStatus::ringkas('email')['terakhir_ok']);

        $this->assertTrue(\Illuminate\Support\Facades\Event::hasListeners(JobFailed::class), 'listener antrean gagal terdaftar');
        LayananStatus::dariJobGagal('Illuminate\Mail\SendQueuedMailable', new \Exception('Connection refused'));
        LayananStatus::dariJobGagal('App\Jobs\LainnyaSaja', new \Exception('abaikan')); // bukan email → tidak dicatat

        $r = LayananStatus::ringkas('email');
        $this->assertSame(1, $r['gagal_24j']);
        $this->assertStringContainsString('Connection refused', $r['gagal_terakhir']->pesan);
    }

    public function test_halaman_sistem_menampilkan_panel_status_dan_peringatan(): void
    {
        $this->admin()->get(route('admin.system'))->assertOk()
            ->assertSee('Status layanan')->assertSee('Penjadwal (cron)')->assertSee('tidak berdetak')->assertSee('Ada yang perlu dicek');

        LayananStatus::detak();
        LayananStatus::gagal('gemini', 'HTTP 403: key salah');

        $this->admin()->get(route('admin.system'))->assertOk()
            ->assertSee('hidup')->assertSee('HTTP 403: key salah')->assertSee('perlu dicek');
    }

    public function test_tes_email_admin_berhasil_dan_gagal_dilaporkan_apa_adanya(): void
    {
        $this->admin()->post(route('admin.system.tes-email'), ['email' => 'tujuan@example.test'])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertNotNull(LayananStatus::ringkas('email')['terakhir_ok']);

        Mail::shouldReceive('raw')->andThrow(new \Exception('SMTP: autentikasi gagal'));
        $this->admin()->post(route('admin.system.tes-email'), ['email' => 'tujuan@example.test'])
            ->assertSessionHas('error', fn ($e) => str_contains($e, 'GAGAL') && str_contains($e, 'autentikasi gagal'));
        $this->assertSame(1, LayananStatus::ringkas('email')['gagal_24j']);

        $this->admin()->post(route('admin.system.tes-email'), ['email' => 'bukan-email'])->assertSessionHasErrors('email');
    }

    public function test_tes_gemini_sukses_ditolak_dan_tanpa_kunci(): void
    {
        config(['services.gemini.key' => 'k', 'services.gemini.model' => 'gemini-test']);

        Http::fake(['*' => Http::sequence()
            ->push(['candidates' => []], 200)
            ->push(['error' => 'API key not valid'], 403)]);

        $this->admin()->post(route('admin.system.tes-gemini'))->assertSessionHas('success', fn ($m) => str_contains($m, 'berfungsi'));
        $this->assertNotNull(LayananStatus::ringkas('gemini')['terakhir_ok']);

        $this->admin()->post(route('admin.system.tes-gemini'))->assertSessionHas('error', fn ($m) => str_contains($m, '403'));
        $this->assertSame(1, LayananStatus::ringkas('gemini')['gagal_24j']);

        config(['services.gemini.key' => null]);
        $this->admin()->post(route('admin.system.tes-gemini'))->assertSessionHas('error', fn ($m) => str_contains($m, 'GEMINI_API_KEY'));
    }

    public function test_aksi_admin_baru_dilindungi_login_admin(): void
    {
        $u = $this->user();
        $o = $this->order($u);

        $this->get(route('admin.users.detail', $u->id))->assertRedirect(route('admin.login'));
        $this->post(route('admin.users.beri-jatah', $u->id), [])->assertRedirect(route('admin.login'));
        $this->post(route('admin.users.beri-langganan', $u->id), [])->assertRedirect(route('admin.login'));
        $this->post(route('admin.orders.kirim-bukti', $o->id))->assertRedirect(route('admin.login'));
        $this->post(route('admin.system.tes-email'), ['email' => 'a@example.test'])->assertRedirect(route('admin.login'));
        $this->post(route('admin.system.tes-gemini'))->assertRedirect(route('admin.login'));
    }

    // ─────────────── Detail pengguna & akses gratis ───────────────

    public function test_halaman_detail_pengguna_menampilkan_order_jatah_dan_laporan(): void
    {
        $u = $this->user(['name' => 'Budi Detail']);
        $this->order($u, ['feature_id' => 3, 'gateway_order_id' => 'RL-DETAIL123']);

        $this->admin()->get(route('admin.users.detail', $u->id))->assertOk()
            ->assertSee('Budi Detail')->assertSee('RL-DETAIL123')->assertSee('Hari Baik &amp; Momentum Usaha', false)
            ->assertSee('Jatah per laporan')->assertSee('Beri akses gratis')->assertSee('sisa 3');
    }

    public function test_beri_jatah_membuat_order_gratis_tanpa_pendapatan_komisi_atau_email(): void
    {
        Mail::fake();
        $aff = Affiliate::create(['name' => 'A', 'referral_code' => 'T' . strtoupper(Str::random(6)), 'komisi_persen' => 20, 'status' => 'active']);
        $u = $this->user(['referred_by_affiliate_id' => $aff->id]);

        $this->admin()->post(route('admin.users.beri-jatah', $u->id), ['feature_id' => 4, 'paket' => 2, 'catatan' => 'kompensasi keluhan'])
            ->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, '6 jatah'));

        $this->assertSame(6, $u->kuotaTotal(4));
        $this->assertTrue($u->hasAccessToFeature(4));
        $o = Order::where('user_id', $u->id)->where('feature_id', 4)->get();
        $this->assertCount(2, $o);
        $this->assertTrue($o->every(fn ($x) => $x->amount === 0 && $x->payment_method === 'komplimen' && $x->status === 'settlement' && $x->catatan === 'kompensasi keluhan'));
        $this->assertSame(0, AffiliateCommission::count());
        Mail::assertNothingQueued();
        Mail::assertNothingSent();

        $this->admin()->get(route('admin.dashboard'))->assertViewHas('stats', fn ($s) => $s['revenue_settled'] === 0 && $s['total_orders'] === 0);
    }

    public function test_beri_jatah_hanya_untuk_fitur_berbayar(): void
    {
        $u = $this->user();

        foreach ([11, 12, 99] as $fid) { // khusus langganan, gratis, tak dikenal
            $this->admin()->post(route('admin.users.beri-jatah', $u->id), ['feature_id' => $fid, 'paket' => 1])->assertSessionHasErrors('feature_id');
        }
        $this->admin()->post(route('admin.users.beri-jatah', $u->id), ['feature_id' => 3, 'paket' => 0])->assertSessionHasErrors('paket');
        $this->admin()->post(route('admin.users.beri-jatah', $u->id), ['feature_id' => 3, 'paket' => 11])->assertSessionHasErrors('paket');

        $this->assertSame(0, Order::where('user_id', $u->id)->count());
    }

    public function test_beri_langganan_gratis_mengaktifkan_dan_memperpanjang(): void
    {
        $u = $this->user();

        $this->admin()->post(route('admin.users.beri-langganan', $u->id), ['bulan' => 12, 'catatan' => 'hadiah'])->assertSessionHas('success');
        $u->refresh();
        $this->assertTrue($u->isSubscriber());
        $sub = $u->subscriptions()->latest('id')->first();
        $this->assertSame('yearly', $sub->plan);
        $this->assertEqualsWithDelta(now()->addMonths(12)->timestamp, $sub->ends_at->timestamp, 5);

        // perpanjangan: menyambung dari masa aktif yang ada
        $akhirLama = $sub->ends_at->copy();
        $this->admin()->post(route('admin.users.beri-langganan', $u->id), ['bulan' => 3])->assertSessionHas('success');
        $this->assertEqualsWithDelta($akhirLama->addMonths(3)->timestamp, $u->subscriptions()->latest('id')->first()->ends_at->timestamp, 5);

        $penanda = Order::where('user_id', $u->id)->where('payment_method', 'komplimen')->get();
        $this->assertCount(2, $penanda);
        $this->assertTrue($penanda->every(fn ($x) => $x->amount === 0));
        $this->admin()->post(route('admin.users.beri-langganan', $u->id), ['bulan' => 2])->assertSessionHasErrors('bulan');
    }

    // ─────────────── Orders ───────────────

    public function test_tabel_order_menampilkan_info_qris_dan_bukan_kolom_kode_unik(): void
    {
        $u = $this->user();
        $this->order($u, ['status' => 'pending', 'settled_at' => null, 'payment_id' => 'pay-abcdef-1234567890', 'payment_expires_at' => now()->addHours(5)]);
        $this->order($u, ['amount' => 0, 'payment_method' => 'komplimen', 'gateway_order_id' => 'GRANT-ZZ', 'catatan' => 'promo teman']);

        $html = $this->admin()->get(route('admin.orders'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Kode Unik', $html);
        $this->assertStringContainsString('Pembayaran', $html);
        $this->assertStringContainsString('QRIS', $html);
        $this->assertStringContainsString('pay-abcdef-1', $html);
        $this->assertStringContainsString('berlaku s/d', $html);
        $this->assertStringContainsString('GRATIS (admin)', $html);
        $this->assertStringContainsString('promo teman', $html);
        $this->assertStringContainsString('Hari Baik', $html);
    }

    public function test_kirim_ulang_bukti_bayar_hanya_untuk_order_berbayar_lunas(): void
    {
        Mail::fake();
        $u = $this->user();
        $lunas   = $this->order($u);
        $pending = $this->order($u, ['status' => 'pending', 'settled_at' => null]);
        $gratis  = $this->order($u, ['amount' => 0, 'payment_method' => 'komplimen']);

        $this->admin()->post(route('admin.orders.kirim-bukti', $lunas->id))->assertSessionHas('success');
        Mail::assertQueued(BuktiBayarMail::class, 1);
        Mail::assertQueued(BuktiBayarMail::class, fn ($m) => $m->hasTo($u->email) && $m->order->id === $lunas->id);

        $this->admin()->post(route('admin.orders.kirim-bukti', $pending->id))->assertSessionHas('error');
        $this->admin()->post(route('admin.orders.kirim-bukti', $gratis->id))->assertSessionHas('error');
        Mail::assertQueued(BuktiBayarMail::class, 1);

        $tanpaEmail = $this->user();
        $tanpaEmail->forceFill(['email' => null])->save();
        $o = $this->order($tanpaEmail);
        $this->admin()->post(route('admin.orders.kirim-bukti', $o->id))->assertSessionHas('error');
        Mail::assertQueued(BuktiBayarMail::class, 1);
    }

    public function test_pesan_error_admin_tampil_di_halaman(): void
    {
        $u = $this->user();
        $pending = $this->order($u, ['status' => 'pending', 'settled_at' => null]);

        $this->admin()->followingRedirects()->from(route('admin.orders'))
            ->post(route('admin.orders.kirim-bukti', $pending->id))
            ->assertOk()->assertSee('Bukti bayar hanya untuk order berbayar yang sudah lunas.');
    }

    // ─────────────── Hapus pengguna ───────────────

    public function test_hapus_pengguna_tidak_menghapus_riwayat_komisi_afiliator(): void
    {
        Mail::fake();
        $aff = Affiliate::create(['name' => 'A', 'referral_code' => 'T' . strtoupper(Str::random(6)), 'komisi_persen' => 20, 'status' => 'active']);
        $u = $this->user(['referred_by_affiliate_id' => $aff->id]);
        $o = $this->order($u, ['status' => 'pending', 'settled_at' => null]);
        $this->admin()->post(route('admin.orders.konfirmasi', $o->id));

        $komisi = AffiliateCommission::where('affiliate_id', $aff->id)->firstOrFail();
        $komisi->update(['status' => 'paid', 'paid_at' => now()]);
        $jumlah = $komisi->komisi_amount;

        $this->admin()->delete(route('admin.users.hapus', $u->id))->assertRedirect();

        $this->assertNull(User::find($u->id));
        $this->assertNull(Order::find($o->id));
        $sisa = AffiliateCommission::find($komisi->id);
        $this->assertNotNull($sisa, 'riwayat komisi harus tetap ada');
        $this->assertSame($jumlah, $sisa->komisi_amount);
        $this->assertSame('paid', $sisa->status);
        $this->assertNull($sisa->user_id);
        $this->assertNull($sisa->order_id);

        // halaman detail afiliator tetap bisa dibuka walau pengguna terkait sudah tidak ada
        $this->admin()->get(route('admin.affiliates.detail', $aff->id))->assertOk();
    }

    public function test_panel_sistem_memperingatkan_selama_harga_uji_aktif(): void
    {
        $this->admin()->get(route('admin.system'))->assertDontSee('HARGA UJI AKTIF');

        config(['payment.test_price' => 1000, 'payment.test_email' => 'penguji@example.test', 'payment.test_until' => now()->addHours(3)->toIso8601String()]);
        $this->admin()->get(route('admin.system'))->assertSee('HARGA UJI AKTIF')->assertSee('otomatis mati')->assertSee('Ada yang perlu dicek');

        config(['payment.test_until' => now()->subMinute()->toIso8601String()]); // sudah lewat
        $this->admin()->get(route('admin.system'))->assertDontSee('HARGA UJI AKTIF');
    }
}
