<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateWithdrawal;
use App\Models\Order;
use App\Models\User;
use App\Services\AffiliatePayoutService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class AfiliatorDashboardTest extends TestCase
{
    use UsesPostgresTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPostgresTestSchema();
        config(['payment.affiliate_min_withdrawal' => 100000, 'payment.affiliate_hold_days' => 14]);
    }

    protected function tearDown(): void
    {
        $this->tearDownPostgresTestSchema();
        parent::tearDown();
    }

    private function afiliator(array $o = []): Affiliate
    {
        return Affiliate::create(array_merge([
            'name' => 'Budi Afiliator', 'email' => 'aff-' . strtolower(Str::random(6)) . '@example.test',
            'referral_code' => 'AFF' . strtoupper(Str::random(5)), 'komisi_persen' => 20, 'status' => 'active',
            'bank_nama' => 'BCA', 'bank_rekening' => '1234567890', 'bank_atas_nama' => 'Budi A',
        ], $o));
    }

    private function komisi(Affiliate $a, int $jumlah, int $umurHari = 30): AffiliateCommission
    {
        $u = User::create(['name' => 'Pembeli ' . Str::random(4), 'email' => Str::random(8) . '@example.test', 'dob' => '1990-01-01', 'birth_hour' => 1, 'email_verified_at' => now(), 'referred_by_affiliate_id' => $a->id]);
        $o = Order::create(['user_id' => $u->id, 'feature_id' => -1, 'amount' => $jumlah * 5, 'status' => 'settlement', 'payment_method' => 'qris', 'gateway_order_id' => 'RL-' . Str::random(8), 'settled_at' => now()]);

        $k = AffiliateCommission::create(['affiliate_id' => $a->id, 'order_id' => $o->id, 'user_id' => $u->id, 'order_amount' => $jumlah * 5, 'komisi_persen' => 20, 'komisi_amount' => $jumlah, 'status' => 'unpaid']);
        $k->forceFill(['created_at' => now()->subDays($umurHari)])->save();

        return $k;
    }

    public function test_masuk_dengan_kode_email_lalu_melihat_dashboard(): void
    {
        Mail::fake();
        $a = $this->afiliator();

        $this->post(route('afiliator.kode.kirim'), ['email' => strtoupper($a->email)])->assertRedirect(route('afiliator.masuk'));
        Mail::assertSent(OtpMail::class, 1);

        $kode = Cache::get('otp:' . hash('sha256', $a->email));
        $this->post(route('afiliator.kode.verifikasi'), ['kode_otp' => $kode])->assertRedirect(route('afiliator.dashboard'));

        $this->get(route('afiliator.dashboard'))->assertOk()->assertSee($a->referral_code)->assertSee('Budi Afiliator');
        $this->get(route('afiliator.panduan'))->assertOk()->assertSee('Rp100.000');
    }

    public function test_email_bukan_afiliator_atau_nonaktif_tidak_dikirimi_kode(): void
    {
        Mail::fake();
        $this->post(route('afiliator.kode.kirim'), ['email' => 'asing@example.test'])->assertRedirect();
        $nonaktif = $this->afiliator(['status' => 'inactive']);
        $this->post(route('afiliator.kode.kirim'), ['email' => $nonaktif->email])->assertRedirect();

        Mail::assertNothingSent();
    }

    public function test_dashboard_dan_panduan_butuh_login_afiliator(): void
    {
        $this->get(route('afiliator.dashboard'))->assertRedirect(route('afiliator.masuk'));
        $this->get(route('afiliator.panduan'))->assertRedirect(route('afiliator.masuk'));
    }

    public function test_dashboard_hanya_menampilkan_data_afiliator_sendiri_dan_menyamarkan_nama(): void
    {
        $a = $this->afiliator();
        $lain = $this->afiliator(['name' => 'Orang Lain']);
        $this->komisi($a, 11800);
        $this->komisi($lain, 99999);

        $html = $this->withSession(['affiliate_id' => $a->id])->get(route('afiliator.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('11.800', $html);
        $this->assertStringNotContainsString('99.999', $html);
        $this->assertStringContainsString('P***', $html);          // nama pembeli disamarkan
        $this->assertStringNotContainsString('Pembeli ', $html);
    }

    public function test_saldo_membedakan_ditahan_siap_diproses_dan_dibayar(): void
    {
        $a = $this->afiliator();
        $this->komisi($a, 40000, 3);            // ditahan
        $this->komisi($a, 60000, 20);           // siap
        $dibayar = $this->komisi($a, 5000, 40); $dibayar->update(['status' => 'paid', 'paid_at' => now()]);
        $diproses = $this->komisi($a, 7000, 40);
        $w = AffiliateWithdrawal::create(['affiliate_id' => $a->id, 'jumlah' => 7000, 'status' => 'pending']);
        $diproses->update(['withdrawal_id' => $w->id]);

        $s = AffiliatePayoutService::saldo($a);

        $this->assertEquals(['ditahan' => 40000, 'siap' => 60000, 'diproses' => 7000, 'dibayar' => 5000, 'total' => 112000], $s);
    }

    public function test_payout_otomatis_hanya_bila_siap_mencapai_minimum_dan_tidak_ganda(): void
    {
        $kurang = $this->afiliator();
        $this->komisi($kurang, 99999);
        $cukup = $this->afiliator();
        $this->komisi($cukup, 60000);
        $this->komisi($cukup, 50000);
        $this->komisi($cukup, 30000, 3);        // masih ditahan: tidak ikut

        $svc = app(AffiliatePayoutService::class);
        $this->assertSame(1, $svc->buatSemua());
        $this->assertSame(0, $svc->buatSemua());   // dijalankan lagi: tidak membuat payout ganda

        $w = AffiliateWithdrawal::where('affiliate_id', $cukup->id)->sole();
        $this->assertSame(110000, $w->jumlah);
        $this->assertSame('BCA', $w->bank_nama);
        $this->assertSame(2, $w->commissions()->count());
        $this->assertSame(0, AffiliateWithdrawal::where('affiliate_id', $kurang->id)->count());

        // Tanpa rekening → tidak dibuat payout
        $tanpaRek = $this->afiliator(['bank_rekening' => null]);
        $this->komisi($tanpaRek, 150000);
        $this->assertNull($svc->buatUntuk($tanpaRek));
    }

    public function test_perintah_artisan_dan_jadwal_bulanan_terdaftar(): void
    {
        $a = $this->afiliator();
        $this->komisi($a, 120000);

        $this->artisan('afiliasi:payout')->assertSuccessful();
        $this->assertSame(1, AffiliateWithdrawal::count());

        $jadwal = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command, 'afiliasi:payout'));
        $this->assertNotNull($jadwal);
        $this->assertSame('0 9 25 * *', $jadwal->expression);
    }

    public function test_admin_menandai_payout_dibayar_menutup_komisi(): void
    {
        $a = $this->afiliator();
        $this->komisi($a, 120000);
        app(AffiliatePayoutService::class)->buatSemua();
        $w = AffiliateWithdrawal::sole();

        $admin = $this->withSession(['admin_logged_in' => true]);
        $admin->get(route('admin.payout'))->assertOk()->assertSee('Budi Afiliator');
        $this->assertStringContainsString('1234567890', $admin->get(route('admin.payout.csv'))->assertOk()->streamedContent());
        $admin->post(route('admin.payout.dibayar', $w->id))->assertRedirect();

        $this->assertSame('paid', $w->fresh()->status);
        $this->assertSame(0, $w->commissions()->where('status', 'unpaid')->count());
        $this->assertSame(120000, AffiliatePayoutService::saldo($a)['dibayar']);
    }
}
