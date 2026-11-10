<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.username' => 'admin-test', 'admin.password' => 'rahasia-test']);
    }

    public function test_login_admin_benar_berhasil(): void
    {
        $this->post(route('admin.login.post'), ['username' => 'admin-test', 'password' => 'rahasia-test'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(session('admin_logged_in'));
    }

    public function test_login_admin_dengan_hash_bcrypt_didukung(): void
    {
        config(['admin.password' => password_hash('rahasia-test', PASSWORD_BCRYPT)]);

        $this->post(route('admin.login.post'), ['username' => 'admin-test', 'password' => 'rahasia-test'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_password_salah_ditolak(): void
    {
        $this->post(route('admin.login.post'), ['username' => 'admin-test', 'password' => 'salah'])
            ->assertSessionHasErrors('username');
        $this->assertNull(session('admin_logged_in'));
    }

    public function test_kredensial_kosong_di_env_tidak_bisa_login(): void
    {
        config(['admin.username' => null, 'admin.password' => null]);

        $this->post(route('admin.login.post'), ['username' => '', 'password' => ''])
            ->assertSessionHasErrors();
    }

    public function test_percobaan_login_dibatasi_setelah_5_kali(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.post'), ['username' => 'admin-test', 'password' => 'salah']);
        }

        $this->post(route('admin.login.post'), ['username' => 'admin-test', 'password' => 'rahasia-test'])
            ->assertStatus(429);
    }
}
