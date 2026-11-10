<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class PanduanTest extends TestCase
{
    use UsesPostgresTestSchema;

    private int $uid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPostgresTestSchema();
        $this->uid = User::create(['name' => 'Pembaca', 'email' => strtolower(Str::random(8)) . '@example.test',
            'password' => bcrypt('x'), 'dob' => '1990-01-01'])->id;
    }

    protected function tearDown(): void
    {
        $this->tearDownPostgresTestSchema();
        parent::tearDown();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('panduan'))->assertRedirect(route('auth.login.form'));
    }

    public function test_pengguna_login_melihat_panduan_dengan_angka_dari_konfigurasi(): void
    {
        $html = $this->withSession(['user_id' => $this->uid])->get(route('panduan'))->assertOk()->getContent();

        $this->assertStringContainsString('Panduan Pengguna', $html);
        $this->assertStringContainsString('Apa itu jatah?', $html);
        $this->assertStringContainsString('Rp 29.000', $html);
        $this->assertStringContainsString('Rp 59.000', $html);
        $this->assertStringContainsString('Rp 499.000', $html);
        $this->assertStringContainsString('3 jatah', $html);
        $this->assertStringContainsString('20 laporan per hari', $html);
        $this->assertStringContainsString('id="jatah"', $html);
    }

    public function test_aturan_hari_dijelaskan_sesuai_perilaku_sebenarnya(): void
    {
        $teks = strip_tags($this->withSession(['user_id' => $this->uid])->get(route('panduan'))->getContent());

        $this->assertStringContainsString('di hari yang sama', $teks);
        $this->assertStringContainsString('jatah tetap tidak berkurang', $teks);
        $this->assertStringContainsString('00.00 WIB', $teks);
    }

    public function test_panduan_bahasa_inggris_dan_tautan_di_navbar(): void
    {
        $html = $this->withSession(['user_id' => $this->uid, 'locale' => 'en'])->get(route('panduan'))->assertOk()->getContent();

        $this->assertStringContainsString('User Guide', $html);
        $this->assertStringContainsString('What is a credit?', $html);
        $this->assertStringContainsString(route('panduan'), $html, 'tautan Panduan ada di navbar');
    }
}
