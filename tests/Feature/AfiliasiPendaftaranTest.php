<?php

namespace Tests\Feature;

use App\Mail\AffiliateDisetujuiMail;
use App\Models\Affiliate;
use App\Models\AffiliateApplication;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class AfiliasiPendaftaranTest extends TestCase
{
    use UsesPostgresTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPostgresTestSchema();
    }

    protected function tearDown(): void
    {
        $this->tearDownPostgresTestSchema();
        parent::tearDown();
    }

    private function dataValid(array $o = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso', 'email' => 'budi-' . strtolower(Str::random(6)) . '@example.test',
            'phone' => '081234567890', 'promosi' => 'Instagram @budi',
            'bank_nama' => 'BCA', 'bank_rekening' => '1234567890', 'bank_atas_nama' => 'Budi Santoso',
            'setuju' => '1',
        ], $o);
    }

    private function admin()
    {
        return $this->withSession(['admin_logged_in' => true]);
    }

    public function test_halaman_publik_menampilkan_info_dan_formulir(): void
    {
        $this->get(route('afiliasi.index'))
            ->assertOk()->assertSee('Jadi Afiliator')->assertSee('20%')->assertSee('Kirim Pendaftaran');
    }

    public function test_pendaftaran_valid_tersimpan_sebagai_pending(): void
    {
        $d = $this->dataValid();

        $this->post(route('afiliasi.daftar'), $d)->assertRedirect(route('afiliasi.index'))->assertSessionHas('terkirim');

        $app = AffiliateApplication::where('email', $d['email'])->firstOrFail();
        $this->assertSame('pending', $app->status);
        $this->assertNull(Affiliate::where('email', $d['email'])->first(), 'tidak boleh langsung jadi afiliator');
    }

    public function test_validasi_dan_persetujuan_wajib(): void
    {
        $this->post(route('afiliasi.daftar'), $this->dataValid(['setuju' => null]))->assertSessionHasErrors('setuju');
        $this->post(route('afiliasi.daftar'), $this->dataValid(['email' => 'bukan-email']))->assertSessionHasErrors('email');
        $this->post(route('afiliasi.daftar'), $this->dataValid(['bank_rekening' => 'abc']))->assertSessionHasErrors('bank_rekening');
        $this->post(route('afiliasi.daftar'), $this->dataValid(['phone' => '<script>']))->assertSessionHasErrors('phone');

        $this->assertSame(0, AffiliateApplication::count());
    }

    public function test_honeypot_terisi_tidak_menyimpan_apa_pun(): void
    {
        $this->post(route('afiliasi.daftar'), $this->dataValid(['website' => 'http://spam.test']))
            ->assertRedirect(route('afiliasi.index'));

        $this->assertSame(0, AffiliateApplication::count());
    }

    public function test_email_yang_sudah_menunggu_atau_sudah_afiliator_ditolak(): void
    {
        $d = $this->dataValid();
        $this->post(route('afiliasi.daftar'), $d);
        $this->post(route('afiliasi.daftar'), $d)->assertSessionHasErrors('email');

        $lain = $this->dataValid();
        Affiliate::create(['name' => 'X', 'email' => strtoupper($lain['email']), 'referral_code' => 'X' . strtoupper(Str::random(5)), 'komisi_persen' => 20, 'status' => 'active']);
        $this->post(route('afiliasi.daftar'), $lain)->assertSessionHasErrors('email');

        $this->assertSame(1, AffiliateApplication::count());
    }

    public function test_admin_menyetujui_membuat_afiliator_aktif_kode_unik_dan_mengirim_email(): void
    {
        Mail::fake();
        $d = $this->dataValid(['name' => 'Budi Santoso']);
        $this->post(route('afiliasi.daftar'), $d);
        $app = AffiliateApplication::firstOrFail();
        $this->flushSession();

        $this->admin()->post(route('admin.affiliates.pendaftar.setuju', $app->id), ['komisi_persen' => 25])
            ->assertRedirect()->assertSessionHas('success');

        $aff = Affiliate::where('email', $d['email'])->firstOrFail();
        $this->assertSame('active', $aff->status);
        $this->assertSame(25, (int) $aff->komisi_persen);
        $this->assertMatchesRegularExpression('/^BUDI\d{2}$/', $aff->referral_code);
        $this->assertSame('BCA', $aff->bank_nama);
        $this->assertSame('approved', $app->fresh()->status);
        $this->assertSame($aff->id, $app->fresh()->affiliate_id);

        Mail::assertSent(AffiliateDisetujuiMail::class, fn ($m) => $m->hasTo($d['email']));
    }

    public function test_komisi_default_dipakai_bila_tidak_diisi_dan_persetujuan_tidak_bisa_diulang(): void
    {
        Mail::fake();
        $this->post(route('afiliasi.daftar'), $this->dataValid());
        $app = AffiliateApplication::firstOrFail();
        $this->flushSession();

        $this->admin()->post(route('admin.affiliates.pendaftar.setuju', $app->id));
        $this->assertSame(20, (int) Affiliate::firstOrFail()->komisi_persen);

        $this->admin()->post(route('admin.affiliates.pendaftar.setuju', $app->id))->assertNotFound();
        $this->assertSame(1, Affiliate::count());
    }

    public function test_admin_menolak_pendaftar(): void
    {
        $this->post(route('afiliasi.daftar'), $this->dataValid());
        $app = AffiliateApplication::firstOrFail();
        $this->flushSession();

        $this->admin()->post(route('admin.affiliates.pendaftar.tolak', $app->id))->assertRedirect();

        $this->assertSame('rejected', $app->fresh()->status);
        $this->assertSame(0, Affiliate::count());
    }

    public function test_tanpa_login_admin_tidak_bisa_menyetujui(): void
    {
        $this->post(route('afiliasi.daftar'), $this->dataValid());
        $app = AffiliateApplication::firstOrFail();
        $this->flushSession();

        $this->post(route('admin.affiliates.pendaftar.setuju', $app->id))->assertRedirect(route('admin.login'));
        $this->assertSame('pending', $app->fresh()->status);
    }

    public function test_halaman_admin_menampilkan_pendaftar_menunggu(): void
    {
        $this->post(route('afiliasi.daftar'), $this->dataValid(['name' => 'Siti Aminah']));
        $this->flushSession();

        $this->admin()->get(route('admin.affiliates'))
            ->assertOk()->assertSee('Pendaftar menunggu persetujuan')->assertSee('Siti Aminah');
    }

    public function test_footer_memuat_tautan_program_afiliasi(): void
    {
        $this->get(route('trial.index'))->assertSee(route('afiliasi.index'));
    }

    public function test_syarat_ketentuan_tampil_di_halaman_dengan_angka_dari_konfigurasi(): void
    {
        config(['payment.affiliate_commission_months' => 12, 'payment.affiliate_hold_days' => 14]);

        $this->get(route('afiliasi.index'))
            ->assertOk()
            ->assertSee('Syarat &amp; Ketentuan Program Afiliasi', false)
            ->assertSee('selama 12 bulan sejak pembayaran pertamanya')
            ->assertSee('ditahan 14 hari')
            ->assertSee('pembelian oleh afiliator sendiri');
    }

    public function test_email_persetujuan_memuat_syarat_ketentuan(): void
    {
        $aff = Affiliate::create(['name' => 'Budi', 'email' => 'b@example.test', 'referral_code' => 'BUDI42', 'komisi_persen' => 25, 'status' => 'active', 'bank_nama' => 'BCA']);
        $mail = new AffiliateDisetujuiMail($aff);

        $mail->assertSeeInHtml('Syarat &amp; Ketentuan Program Afiliasi', false);
        $mail->assertSeeInHtml('selama 12 bulan');
        $mail->assertSeeInText('ditahan 14 hari');
        $mail->assertSeeInText('Komisi sebesar 25%');
        $mail->assertSeeInText('BUDI42');
        $mail->assertSeeInText('folder Spam atau Junk');
    }
}
