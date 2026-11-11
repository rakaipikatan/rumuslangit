<?php

namespace Tests\Feature;

use App\Exceptions\GeminiRateLimitException;
use App\Jobs\GenerateAIReport;
use App\Models\AiReport;
use App\Models\ServiceEvent;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\KalkulasiEngine;
use App\Support\LayananStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

/** Ketahanan terhadap kuota Gemini habis (mis. paket gratis 20 permintaan/hari). */
class GeminiKuotaTest extends TestCase
{
    use UsesPostgresTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPostgresTestSchema();
        Cache::flush();
        config(['services.gemini.key' => 'k', 'services.gemini.model' => 'gemini-2.5-flash-lite']);
    }

    protected function tearDown(): void
    {
        $this->tearDownPostgresTestSchema();
        parent::tearDown();
    }

    private function panggil(): void
    {
        $profil = ['name' => 'Budi', 'jenis_kelamin' => 'Laki-laki', 'dob' => '1990-01-01', 'birth_hour' => 8, 'province' => 'DKI JAKARTA',
            'kota' => 'Jakarta', 'kecamatan' => 'A', 'kelurahan' => 'B', 'agama' => null, 'anak_ke' => 1, 'jumlah_saudara' => 1, 'status_pernikahan' => 'Lajang'];
        $jawab = [];
        foreach (range(1, 10) as $i) $jawab["q{$i}"] = 1;

        app(GeminiService::class)->generate(1, KalkulasiEngine::hitung('1990-01-01', 8), $jawab, $profil);
    }

    private function respons429Harian(): array
    {
        return ['error' => [
            'code' => 429, 'status' => 'RESOURCE_EXHAUSTED',
            'message' => 'You exceeded your current quota, please check your plan and billing details.',
            'details' => [
                ['@type' => 'type.googleapis.com/google.rpc.QuotaFailure', 'violations' => [[
                    'quotaMetric' => 'generativelanguage.googleapis.com/generate_content_free_tier_requests',
                    'quotaId' => 'GenerateRequestsPerDayPerProjectPerModel-FreeTier',
                    'quotaDimensions' => ['location' => 'global', 'model' => 'gemini-2.5-flash-lite'],
                    'quotaValue' => '20',
                ]]],
                ['@type' => 'type.googleapis.com/google.rpc.RetryInfo', 'retryDelay' => '14137s'],
            ],
        ]];
    }

    public function test_kuota_harian_paket_gratis_dibaca_waktu_tunggunya_dan_dijelaskan_di_panel(): void
    {
        Http::fake(['*' => Http::response($this->respons429Harian(), 429)]);

        try {
            $this->panggil();
            $this->fail('seharusnya melempar GeminiRateLimitException');
        } catch (GeminiRateLimitException $e) {
            $this->assertSame(14137, $e->retryAfterSeconds, 'mengikuti waktu tunggu resmi Google, bukan 60 detik');
        }

        $pesan = ServiceEvent::where('layanan', 'gemini')->latest('id')->value('pesan');
        $this->assertStringContainsString('PAKET GRATIS', $pesan);
        $this->assertStringContainsString('batas 20/hari', $pesan);
        $this->assertStringContainsString('gemini-2.5-flash-lite', $pesan);
        $this->assertStringContainsString('3.9 jam', $pesan);
        $this->assertStringContainsString('aktifkan billing', $pesan);
        $this->assertTrue(LayananStatus::ringkas('gemini')['perlu_cek']);
    }

    public function test_batas_per_menit_memakai_waktu_dari_pesan_dan_tidak_disebut_kuota_harian(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 429, 'message' => 'Resource exhausted. Please retry in 12.4s.']], 429)]);

        try {
            $this->panggil();
            $this->fail('seharusnya melempar GeminiRateLimitException');
        } catch (GeminiRateLimitException $e) {
            $this->assertSame(13, $e->retryAfterSeconds);
        }

        $pesan = ServiceEvent::where('layanan', 'gemini')->latest('id')->value('pesan');
        $this->assertStringContainsString('Rate limit 429', $pesan);
        $this->assertStringNotContainsString('harian', $pesan);
    }

    public function test_pekerjaan_laporan_menunggu_sampai_8_jam_bukan_gagal_setelah_10_percobaan(): void
    {
        $this->assertFalse(property_exists(GenerateAIReport::class, 'tries'), 'jumlah percobaan tetap dihapus; pakai batas waktu');

        $job = new GenerateAIReport(1, 1, [], [], []);
        $this->assertEqualsWithDelta(now()->addHours(8)->timestamp, $job->retryUntil()->getTimestamp(), 5);
    }

    public function test_pekerjaan_dilepas_ke_antrean_mengikuti_google_dengan_batas_1_jam(): void
    {
        $gemini = \Mockery::mock(GeminiService::class);
        $gemini->shouldReceive('generate')->andThrow(new GeminiRateLimitException(14137));

        $job = (new GenerateAIReport(1, 1, [], [], []))->withFakeQueueInteractions();
        $job->handle($gemini);

        $tunda = $job->job->releaseDelay;
        $this->assertGreaterThanOrEqual(3600, $tunda);
        $this->assertLessThanOrEqual(3615, $tunda, 'maksimal 1 jam sekali tunda (+ jitter), bukan 4 jam sekaligus');

        // waktu tunggu pendek tetap dihormati (minimal 30 dtk)
        $gemini2 = \Mockery::mock(GeminiService::class);
        $gemini2->shouldReceive('generate')->andThrow(new GeminiRateLimitException(5));
        $job2 = (new GenerateAIReport(1, 1, [], [], []))->withFakeQueueInteractions();
        $job2->handle($gemini2);
        $this->assertGreaterThanOrEqual(30, $job2->job->releaseDelay);
        $this->assertLessThanOrEqual(45, $job2->job->releaseDelay);
    }

    public function test_halaman_tunggu_memuat_pesan_jujur_bila_lama(): void
    {
        $u = User::create(['name' => 'Penunggu', 'email' => strtolower(Str::random(8)) . '@example.test', 'dob' => '1990-01-01', 'email_verified_at' => now()]);
        $r = AiReport::create(['user_id' => $u->id, 'feature_id' => 3, 'locale' => 'id', 'data_hash' => Str::random(20), 'cached_at' => now()]);

        $this->withSession(['user_id' => $u->id, 'report_id_3' => $r->id])->get(route('laporan.status', 3))
            ->assertOk()->assertSee('Layanan AI sedang padat')->assertSee('tidak hilang')->assertSee('Kembali ke katalog');
    }
}
