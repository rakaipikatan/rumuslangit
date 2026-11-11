<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class EmailTerverifikasiSebelumDaftarTest extends TestCase
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

    private function data(): array
    {
        return ['name' => 'Pendaftar Baru', 'jenis_kelamin' => 'Perempuan', 'dob' => '1995-05-05', 'birth_hour' => '10:00',
            'province' => 'DKI Jakarta', 'kota' => 'Jakarta Selatan', 'kecamatan' => 'Kebayoran Baru', 'kelurahan' => 'Senayan',
            'anak_ke' => 1, 'jumlah_saudara' => 2, 'status_pernikahan' => 'Lajang'];
    }

    public function test_email_yang_lolos_otp_sebelum_akun_dibuat_menempel_saat_daftar(): void
    {
        $this->withSession(['otp_verified' => true, 'email_terverifikasi' => 'baru@example.test'])
            ->post(route('trial.proses'), $this->data())->assertRedirect(route('trial.hasil'));

        $u = User::where('name', 'Pendaftar Baru')->sole();
        $this->assertSame('baru@example.test', $u->email);
        $this->assertNotNull($u->email_verified_at);
    }

    public function test_email_yang_sudah_dipakai_akun_terverifikasi_lain_tidak_ditimpa(): void
    {
        User::create(['name' => 'Pemilik', 'email' => 'dipakai@example.test', 'dob' => '1990-01-01', 'birth_hour' => 1, 'email_verified_at' => now()]);

        $this->withSession(['otp_verified' => true, 'email_terverifikasi' => 'dipakai@example.test'])
            ->post(route('trial.proses'), $this->data())->assertRedirect(route('trial.hasil'));

        $this->assertNull(User::where('name', 'Pendaftar Baru')->sole()->email);
    }
}
