<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class LoginKodeTest extends TestCase
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

    private function userTerverifikasi(): User
    {
        return User::create([
            'name' => 'Tanpa Password', 'email' => 'kode-' . strtolower(Str::random(8)) . '@example.test',
            'dob' => '1990-01-01', 'birth_hour' => 5, 'email_verified_at' => now(),
        ]);
    }

    public function test_login_dengan_kode_email_tanpa_password(): void
    {
        Mail::fake();
        $user = $this->userTerverifikasi();

        $this->postJson(route('auth.kode.kirim'), ['email' => strtoupper($user->email)])->assertOk()->assertJson(['success' => true]);
        Mail::assertSent(OtpMail::class, 1);

        $kode = Cache::get('otp:' . hash('sha256', $user->email));
        $this->assertNotEmpty($kode);

        $this->postJson(route('auth.kode.verifikasi'), ['kode_otp' => $kode])
            ->assertOk()->assertJson(['success' => true, 'redirect' => route('trial.katalog')]);

        $this->assertSame($user->id, session('user_id'));
        $this->assertTrue(session('otp_verified'));
    }

    public function test_email_tak_terdaftar_dibalas_sama_dan_tidak_mengirim_email(): void
    {
        Mail::fake();

        $this->postJson(route('auth.kode.kirim'), ['email' => 'tidak-ada@example.test'])
            ->assertOk()->assertJson(['success' => true]);

        Mail::assertNothingSent();
    }

    public function test_user_belum_verifikasi_email_tidak_dikirimi_kode(): void
    {
        Mail::fake();
        $user = $this->userTerverifikasi();
        $user->update(['email_verified_at' => null]);

        $this->postJson(route('auth.kode.kirim'), ['email' => $user->email])->assertOk();

        Mail::assertNothingSent();
    }

    public function test_kode_salah_ditolak_dan_dibatalkan_setelah_5_kali(): void
    {
        Mail::fake();
        $user = $this->userTerverifikasi();
        $this->postJson(route('auth.kode.kirim'), ['email' => $user->email]);
        $benar = Cache::get('otp:' . hash('sha256', $user->email));
        $salah = $benar === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('auth.kode.verifikasi'), ['kode_otp' => $salah])->assertStatus(422);
        }

        // Kode benar pun sudah tidak berlaku setelah 5 kegagalan
        $this->postJson(route('auth.kode.verifikasi'), ['kode_otp' => $benar])->assertStatus(422);
        $this->assertNull(session('user_id'));
    }

    public function test_verifikasi_tanpa_meminta_kode_ditolak(): void
    {
        $this->postJson(route('auth.kode.verifikasi'), ['kode_otp' => '123456'])->assertStatus(422);
    }

    public function test_halaman_login_menampilkan_login_kode_email(): void
    {
        $this->get(route('auth.login.form'))->assertOk()->assertSee('Masuk dengan kode email');
    }

    public function test_pengguna_tanpa_password_bisa_membuat_password_lalu_login_dengannya(): void
    {
        $user = $this->userTerverifikasi();

        $this->withSession(['user_id' => $user->id])->get(route('auth.pengaturan'))
            ->assertOk()->assertSee('belum punya password');

        $this->withSession(['user_id' => $user->id])
            ->post(route('auth.password'), ['password' => 'PasswordBaru123', 'password_confirmation' => 'PasswordBaru123'])
            ->assertRedirect(route('auth.pengaturan'))->assertSessionHas('success');

        $this->flushSession();
        $this->post(route('auth.login'), ['email' => $user->email, 'password' => 'PasswordBaru123'])
            ->assertRedirect();
        $this->assertSame($user->id, session('user_id'));
    }

    public function test_ubah_password_mewajibkan_password_lama_yang_benar(): void
    {
        $user = $this->userTerverifikasi();
        $user->update(['password' => 'Lama12345']);

        $this->withSession(['user_id' => $user->id])
            ->post(route('auth.password'), ['current_password' => 'salah', 'password' => 'Baru123456', 'password_confirmation' => 'Baru123456'])
            ->assertSessionHasErrors('current_password');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Lama12345', $user->fresh()->password));

        $this->withSession(['user_id' => $user->id])
            ->post(route('auth.password'), ['current_password' => 'Lama12345', 'password' => 'Baru123456', 'password_confirmation' => 'Baru123456'])
            ->assertSessionHas('success');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Baru123456', $user->fresh()->password));
    }

    public function test_password_baru_harus_8_karakter_dan_terkonfirmasi(): void
    {
        $user = $this->userTerverifikasi();

        $this->withSession(['user_id' => $user->id])
            ->post(route('auth.password'), ['password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors('password');
        $this->withSession(['user_id' => $user->id])
            ->post(route('auth.password'), ['password' => 'PasswordPanjang1', 'password_confirmation' => 'beda'])
            ->assertSessionHasErrors('password');

        $this->assertNull($user->fresh()->password);
    }

    public function test_pengaturan_hanya_untuk_pengguna_login_dengan_email_terverifikasi(): void
    {
        $this->get(route('auth.pengaturan'))->assertRedirect(route('auth.login.form'));

        $belum = User::create(['name' => 'Belum', 'dob' => '1990-01-01']);
        $this->withSession(['user_id' => $belum->id])->get(route('auth.pengaturan'))->assertRedirect(route('auth.login.form'));
        $this->withSession(['user_id' => $belum->id])
            ->post(route('auth.password'), ['password' => 'PasswordBaru123', 'password_confirmation' => 'PasswordBaru123'])
            ->assertRedirect(route('auth.login.form'));
        $this->assertNull($belum->fresh()->password);
    }

    public function test_pesan_setelah_kode_dikirim_mengingatkan_folder_spam(): void
    {
        $this->postJson(route('auth.kode.kirim'), ['email' => 'siapa-saja@example.test'])
            ->assertOk()->assertJsonPath('message', fn ($m) => str_contains($m, 'folder Spam'));
    }
}
