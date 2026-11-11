<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class PilihPaketLanggananTest extends TestCase
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

    private function user(): User
    {
        return User::create(['name' => 'Calon Pelanggan', 'email' => strtolower(Str::random(8)) . '@example.test',
            'password' => bcrypt('x'), 'dob' => '1990-01-01', 'email_verified_at' => now()]);
    }

    public function test_halaman_pilih_paket_menampilkan_bulanan_dan_tahunan_berdampingan(): void
    {
        $u = $this->user();

        $html = $this->withSession(['user_id' => $u->id, 'otp_verified' => true])->get(route('langganan'))->assertOk()->getContent();

        $this->assertStringContainsString('Pilih Paket Langganan', $html);
        $this->assertStringContainsString('Langganan Bulanan', $html);
        $this->assertStringContainsString('Langganan Tahunan', $html);
        $this->assertStringContainsString('Rp 59.000', $html);
        $this->assertStringContainsString('Rp 499.000', $html);
        $this->assertStringContainsString('Hemat 30%', $html);
        $this->assertStringContainsString('setara Rp 41.583/bulan', $html);
        $this->assertStringContainsString(route('payment.konfirmasi', 'langganan-bulanan'), $html);
        $this->assertStringContainsString(route('payment.konfirmasi', 'langganan-tahunan'), $html);
    }

    public function test_halaman_pilih_paket_dalam_bahasa_inggris(): void
    {
        $u = $this->user();

        $this->withSession(['user_id' => $u->id, 'locale' => 'en'])->get(route('langganan'))->assertOk()
            ->assertSee('Choose a Subscription Plan')->assertSee('Save 30%')->assertSee('equal to Rp 41.583/month');
    }

    public function test_tamu_dan_pelanggan_aktif_dialihkan(): void
    {
        $this->get(route('langganan'))->assertRedirect(route('trial.index'));

        $u = $this->user();
        Subscription::create(['user_id' => $u->id, 'plan' => 'monthly', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(10), 'auto_renew' => false]);
        $u->update(['subscription_status' => 'active']);

        $this->withSession(['user_id' => $u->id])->get(route('langganan'))->assertRedirect(route('trial.katalog'));
    }

    public function test_tombol_langganan_di_katalog_menuju_halaman_pilih_paket(): void
    {
        $u = $this->user();

        $html = $this->withSession(['user_id' => $u->id, 'otp_verified' => true])->get(route('trial.katalog'))->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('langganan') . '"', $html);
        $this->assertStringNotContainsString('href="' . route('payment.konfirmasi', 'langganan-bulanan') . '"', $html, 'tidak lagi langsung ke bulanan');
    }

    public function test_halaman_bayar_langganan_punya_tautan_ganti_paket(): void
    {
        $u = $this->user();
        $sesi = ['user_id' => $u->id];

        $this->withSession($sesi)->get(route('payment.konfirmasi', 'langganan-bulanan'))->assertOk()
            ->assertSee('Ganti ke paket Tahunan — Rp 499.000 (hemat 30%)')
            ->assertSee(route('payment.konfirmasi', 'langganan-tahunan'));

        $this->withSession($sesi)->get(route('payment.konfirmasi', 'langganan-tahunan'))->assertOk()
            ->assertSee('Ganti ke paket Bulanan — Rp 59.000')
            ->assertSee(route('payment.konfirmasi', 'langganan-bulanan'));

        // alias lama "langganan" = bulanan: tetap menawarkan tahunan
        $this->withSession($sesi)->get(route('payment.konfirmasi', 'langganan'))->assertOk()->assertSee('Ganti ke paket Tahunan');

        // halaman bayar fitur satuan tidak menampilkan tautan ganti paket
        $this->withSession($sesi)->get(route('payment.konfirmasi', '3'))->assertOk()->assertDontSee('Ganti ke paket');
    }
}
