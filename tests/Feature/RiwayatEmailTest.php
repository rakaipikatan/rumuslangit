<?php

namespace Tests\Feature;

use App\Mail\BuktiBayarMail;
use App\Mail\HokiHarianMail;
use App\Mail\OtpMail;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use App\Support\EmailLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class RiwayatEmailTest extends TestCase
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

    private function catat(array $o = []): EmailLog
    {
        return EmailLog::create(array_merge(['email' => 'x@example.test', 'jenis' => 'otp', 'status' => 'terkirim', 'biaya' => 5, 'created_at' => now()], $o));
    }

    public function test_email_terkirim_dicatat_dengan_jenis_pengguna_dan_biaya(): void
    {
        $u = $this->user();

        Mail::to(strtoupper($u->email))->send(new OtpMail('123456'));

        $log = EmailLog::firstOrFail();
        $this->assertSame('otp', $log->jenis);
        $this->assertSame('terkirim', $log->status);
        $this->assertSame($u->id, $log->user_id, 'dikaitkan ke pengguna lewat email (tanpa membedakan huruf besar-kecil)');
        $this->assertSame($u->email, $log->email);
        $this->assertSame(5, $log->biaya);
    }

    public function test_setiap_jenis_email_aplikasi_tercatat_dengan_jenis_yang_benar(): void
    {
        $u = $this->user();

        Mail::to($u->email)->send(new OtpMail('111111', 'login_kode'));
        Mail::to($u->email)->send(new HokiHarianMail('Budi', ['angka' => [1, 2, 3], 'warna' => ['Merah', 'Biru'], 'mantra' => 'x']));
        $order = Order::create(['user_id' => $u->id, 'feature_id' => 3, 'amount' => 29000, 'payment_method' => 'qris',
            'gateway_order_id' => 'RL-' . Str::random(8), 'status' => 'settlement', 'settled_at' => now()]);
        Mail::to($u->email)->send(new BuktiBayarMail($order));
        Mail::raw('tanpa penanda', fn ($m) => $m->to($u->email)->subject('x'));

        $this->assertEqualsCanonicalizing(['login_kode', 'hoki_harian', 'bukti_bayar', 'lainnya'], EmailLog::pluck('jenis')->all());
    }

    public function test_email_uji_tidak_menempel_ke_pengguna_walau_alamatnya_milik_pengguna(): void
    {
        $u = $this->user();

        $this->admin()->post(route('admin.system.tes-email'), ['email' => $u->email])->assertSessionHas('success');
        $this->artisan('mail:uji', ['email' => $u->email])->assertSuccessful();

        $logs = EmailLog::orderBy('id')->get();
        $this->assertSame(['tes_admin', 'tes_cli'], $logs->pluck('jenis')->all());
        $this->assertTrue($logs->every(fn ($l) => $l->user_id === null), 'email uji tidak boleh menempel ke pengguna');
        $this->assertSame($u->email, $logs->first()->email);
    }

    public function test_kegagalan_kirim_kode_login_dan_hoki_dicatat_sebagai_gagal_tanpa_biaya(): void
    {
        $u = $this->user();
        Mail::shouldReceive('to')->andThrow(new \Exception('SMTP: saldo habis'));

        $this->postJson(route('auth.kode.kirim'), ['email' => $u->email])->assertOk();

        $log = EmailLog::firstOrFail();
        $this->assertSame('login_kode', $log->jenis);
        $this->assertSame('gagal', $log->status);
        $this->assertSame(0, $log->biaya);
        $this->assertStringContainsString('saldo habis', $log->pesan_error);
        $this->assertSame($u->email, $log->email);
        $this->assertSame($u->id, $log->user_id, 'kegagalan kirim ke pengguna tetap dikaitkan ke pengguna itu');
    }

    public function test_hoki_harian_mencatat_satu_baris_per_pelanggan(): void
    {
        foreach ([1, 2] as $i) {
            $u = $this->user(['subscription_status' => 'active']);
            Subscription::create(['user_id' => $u->id, 'plan' => 'monthly', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(5), 'auto_renew' => false]);
        }

        $this->artisan('hoki:kirim')->assertSuccessful();

        $this->assertSame(2, EmailLog::where('jenis', 'hoki_harian')->where('status', 'terkirim')->whereNotNull('user_id')->count());
    }

    public function test_login_kode_tercatat_lewat_alur_nyata(): void
    {
        $u = $this->user();

        $this->postJson(route('auth.kode.kirim'), ['email' => $u->email])->assertOk();

        $log = EmailLog::where('jenis', 'login_kode')->firstOrFail();
        $this->assertSame($u->id, $log->user_id);
    }

    public function test_halaman_email_menampilkan_ringkasan_per_jenis_pengguna_dan_uji(): void
    {
        $a = $this->user(['name' => 'Andi Pemakai']);
        $this->catat(['user_id' => $a->id, 'email' => $a->email, 'jenis' => 'hoki_harian']);
        $this->catat(['user_id' => $a->id, 'email' => $a->email, 'jenis' => 'hoki_harian']);
        $this->catat(['user_id' => $a->id, 'email' => $a->email, 'jenis' => 'otp']);
        $this->catat(['user_id' => null, 'email' => 'uji@example.test', 'jenis' => 'tes_admin']);
        $this->catat(['user_id' => null, 'email' => 'asing@example.test', 'jenis' => 'otp', 'status' => 'gagal', 'biaya' => 0, 'pesan_error' => 'boom SMTP']);

        $this->admin()->get(route('admin.email'))->assertOk()
            ->assertViewHas('ringkasan', fn ($r) => $r['terkirim'] === 4 && $r['gagal'] === 1 && $r['biaya'] === 20 && $r['total'] === 5)
            ->assertViewHas('tanpaPengguna', fn ($t) => $t['uji'] === 1)
            ->assertSee('Andi Pemakai')->assertSee('Hoki Harian')->assertSee('Tes (panel admin)')->assertSee('boom SMTP')
            ->assertSee('Rekonsiliasi dengan SumoPod');
    }

    public function test_filter_jenis_status_dan_cari(): void
    {
        $a = $this->user(['name' => 'Cari Aku']);
        $this->catat(['user_id' => $a->id, 'email' => $a->email, 'jenis' => 'bukti_bayar']);
        $this->catat(['email' => 'lain@example.test', 'jenis' => 'otp']);
        $this->catat(['email' => 'lain@example.test', 'jenis' => 'otp', 'status' => 'gagal', 'biaya' => 0]);

        $this->admin()->get(route('admin.email', ['jenis' => 'bukti_bayar']))->assertViewHas('ringkasan', fn ($r) => $r['total'] === 1);
        $this->admin()->get(route('admin.email', ['status' => 'gagal']))->assertViewHas('ringkasan', fn ($r) => $r['total'] === 1 && $r['gagal'] === 1);
        $this->admin()->get(route('admin.email', ['cari' => 'Cari Aku']))->assertViewHas('ringkasan', fn ($r) => $r['total'] === 1);
        $this->admin()->get(route('admin.email', ['cari' => 'lain@example']))->assertViewHas('ringkasan', fn ($r) => $r['total'] === 2);
    }

    public function test_periode_memakai_hari_wib_bukan_utc(): void
    {
        // 10 Nov 23:50 WIB (16:50 UTC) dan 11 Nov 00:10 WIB (17:10 UTC)
        $this->catat(['created_at' => Carbon::parse('2026-11-10 16:50:00', 'UTC')]);
        $this->catat(['created_at' => Carbon::parse('2026-11-10 17:10:00', 'UTC')]);

        $this->admin()->get(route('admin.email', ['dari' => '2026-11-10', 'sampai' => '2026-11-10']))
            ->assertViewHas('ringkasan', fn ($r) => $r['terkirim'] === 1);
        $this->admin()->get(route('admin.email', ['dari' => '2026-11-11', 'sampai' => '2026-11-11']))
            ->assertViewHas('ringkasan', fn ($r) => $r['terkirim'] === 1);
        $this->admin()->get(route('admin.email', ['dari' => '2026-11-10', 'sampai' => '2026-11-11']))
            ->assertViewHas('ringkasan', fn ($r) => $r['terkirim'] === 2);

        // rincian per hari dikelompokkan menurut hari WIB
        $this->admin()->get(route('admin.email', ['dari' => '2026-11-10', 'sampai' => '2026-11-11']))
            ->assertViewHas('perHari', fn ($h) => $h->count() === 2 && $h->every(fn ($x) => (int) $x->terkirim === 1));
    }

    public function test_rekonsiliasi_menghitung_selisih_dengan_angka_sumopod(): void
    {
        foreach (range(1, 10) as $i) $this->catat();

        $this->admin()->get(route('admin.email', ['sumopod' => 12]))->assertOk()
            ->assertViewHas('selisih', 2)->assertSee('+2')->assertSee('LEBIH BANYAK');
        $this->admin()->get(route('admin.email', ['sumopod' => 10]))->assertViewHas('selisih', 0);
        $this->admin()->get(route('admin.email', ['sumopod' => 7]))->assertViewHas('selisih', -3)->assertSee('LEBIH BANYAK');
        $this->admin()->get(route('admin.email'))->assertViewHas('selisih', null);
    }

    public function test_ekspor_csv_mengikuti_filter_dalam_waktu_wib(): void
    {
        $u = $this->user();
        $this->catat(['user_id' => $u->id, 'email' => $u->email, 'jenis' => 'bukti_bayar', 'created_at' => Carbon::parse('2026-11-10 17:30:00', 'UTC')]);
        $this->catat(['email' => 'lain@example.test', 'jenis' => 'otp', 'created_at' => Carbon::parse('2026-11-10 17:30:00', 'UTC')]);

        $res = $this->admin()->get(route('admin.email.csv', ['dari' => '2026-11-11', 'sampai' => '2026-11-11', 'jenis' => 'bukti_bayar']));
        $res->assertOk();
        $csv = $res->streamedContent();

        $this->assertStringContainsString('waktu_wib,jenis,penerima,pengguna_id,status,biaya_rp,pesan_error', $csv);
        $this->assertStringContainsString('"2026-11-11 00:30:00",bukti_bayar,' . $u->email . ',' . $u->id . ',terkirim,5', $csv);
        $this->assertStringNotContainsString('lain@example.test', $csv);
        $this->assertStringContainsString('attachment', (string) $res->headers->get('content-disposition'));
    }

    public function test_detail_pengguna_menampilkan_pemakaian_email_dan_riwayat_tetap_ada_bila_pengguna_dihapus(): void
    {
        $u = $this->user(['name' => 'Dipakai Banyak']);
        $this->catat(['user_id' => $u->id, 'email' => $u->email, 'jenis' => 'hoki_harian']);
        $this->catat(['user_id' => $u->id, 'email' => $u->email, 'jenis' => 'hoki_harian']);
        $this->catat(['user_id' => $u->id, 'email' => $u->email, 'jenis' => 'otp', 'status' => 'gagal', 'biaya' => 0, 'pesan_error' => 'timeout']);

        $this->admin()->get(route('admin.users.detail', $u->id))->assertOk()
            ->assertSee('Email yang dikirim ke pengguna ini')->assertSee('Hoki Harian 2')
            ->assertViewHas('emailStat', fn ($e) => $e['jumlah'] === 2 && $e['biaya'] === 10 && $e['gagal'] === 1);

        $this->admin()->delete(route('admin.users.hapus', $u->id))->assertRedirect();
        $this->assertSame(3, EmailLog::count(), 'riwayat email tetap ada untuk pembukuan');
        $this->assertSame(0, EmailLog::whereNotNull('user_id')->count());
        $this->admin()->get(route('admin.email'))->assertOk();
    }

    public function test_halaman_email_dilindungi_login_admin(): void
    {
        $this->get(route('admin.email'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.email.csv'))->assertRedirect(route('admin.login'));
    }

    public function test_pencatat_tidak_mengganggu_pengiriman_saat_tabel_bermasalah(): void
    {
        // Kegagalan menulis log ditelan: pemanggilan langsung dengan data tak valid tidak boleh melempar
        EmailLogger::catatGagal('otp', str_repeat('a', 400) . '@x.test', str_repeat('e', 1000));
        $this->assertTrue(true);
    }
}
