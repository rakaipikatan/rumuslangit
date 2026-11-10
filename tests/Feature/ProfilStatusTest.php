<?php

namespace Tests\Feature;

use App\Models\AiReport;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class ProfilStatusTest extends TestCase
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

    private function buatUserDenganProfil(string $responseText): User
    {
        $user = User::create([
            'name' => 'Test ' . Str::random(5), 'email' => Str::random(8) . '@example.test',
            'password' => bcrypt('x'), 'dob' => '1990-01-01',
        ]);

        AiReport::create([
            'user_id' => $user->id, 'feature_id' => 0, 'locale' => 'id',
            'data_hash' => Str::random(20), 'response_text' => $responseText, 'cached_at' => now(),
        ]);

        return $user;
    }

    public function test_profil_dengan_trailing_comma_tetap_terbaca(): void
    {
        // Regresi: JSON rusak dari Gemini membuat halaman "Sedang menganalisis..." menggantung.
        $user = $this->buatUserDenganProfil("{\n \"tipe_nama\": \"Sang Penjaga\",\n \"komparasi\": [\n  {\"sistem\": \"Virgo\"},\n ]\n}");

        $this->withSession(['user_id' => $user->id])
            ->getJson(route('trial.profil.status'))
            ->assertOk()
            ->assertJson(['siap' => true, 'data' => ['tipe_nama' => 'Sang Penjaga']]);
    }

    public function test_profil_tak_terpulihkan_dianggap_belum_siap_dan_diminta_ulang(): void
    {
        $user = $this->buatUserDenganProfil('bukan json sama sekali {{{');

        $this->withSession(['user_id' => $user->id])
            ->getJson(route('trial.profil.status'))
            ->assertOk()
            ->assertJson(['siap' => false, 'ulang' => true]);
    }

    public function test_katalog_dengan_sesi_user_yang_sudah_dihapus_diarahkan_ke_awal(): void
    {
        $this->withSession(['user_id' => 99999999, 'otp_verified' => true])
            ->get(route('trial.katalog'))
            ->assertRedirect(route('trial.index'));
    }
}
