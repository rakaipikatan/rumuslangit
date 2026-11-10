<?php

namespace Tests\Feature;

use App\Mail\HokiHarianMail;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class HokiHarianTest extends TestCase
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

    private function user(array $o = []): User
    {
        return User::create(array_merge([
            'name' => 'Pelanggan', 'email' => strtolower(Str::random(8)) . '@example.test', 'dob' => '1990-01-01',
            'birth_hour' => 8, 'email_verified_at' => now(), 'subscription_status' => 'free',
        ], $o));
    }

    private function langganan(User $u, $berakhir): void
    {
        Subscription::create(['user_id' => $u->id, 'plan' => 'monthly', 'starts_at' => now()->subDays(5), 'ends_at' => $berakhir, 'auto_renew' => false]);
        $u->update(['subscription_status' => 'active']);
    }

    public function test_hoki_harian_hanya_dikirim_ke_pelanggan_aktif_terverifikasi(): void
    {
        Mail::fake();

        $aktif = $this->user();               $this->langganan($aktif, now()->addDays(10));
        $kadaluarsa = $this->user();          $this->langganan($kadaluarsa, now()->subHour()); // status belum disinkronkan
        $gratis = $this->user();
        $belumVerif = $this->user(['email_verified_at' => null]); $this->langganan($belumVerif, now()->addDays(10));

        $this->artisan('hoki:kirim')->assertSuccessful();

        Mail::assertSent(HokiHarianMail::class, 1);
        Mail::assertSent(HokiHarianMail::class, fn ($m) => $m->hasTo($aktif->email));
        Mail::assertNotSent(HokiHarianMail::class, fn ($m) => $m->hasTo($kadaluarsa->email) || $m->hasTo($gratis->email) || $m->hasTo($belumVerif->email));
    }

    public function test_kegagalan_kirim_dicatat_ke_log_dan_tidak_menghentikan_yang_lain(): void
    {
        Log::spy();
        $u1 = $this->user(); $this->langganan($u1, now()->addDays(3));
        $u2 = $this->user(); $this->langganan($u2, now()->addDays(3));

        Mail::shouldReceive('to')->twice()->andThrow(new \Exception('SMTP: saldo habis'));

        $this->artisan('hoki:kirim')->assertSuccessful();

        Log::shouldHaveReceived('warning')->twice();
        Log::shouldHaveReceived('info')->withArgs(fn ($pesan, $ctx = []) => $pesan === 'Hoki harian selesai' && ($ctx['gagal'] ?? null) === 2 && ($ctx['terkirim'] ?? null) === 0)->once();
    }

    public function test_jadwal_hoki_pukul_0600_wib_terdaftar(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('hoki:kirim')->assertSuccessful();
    }
}
