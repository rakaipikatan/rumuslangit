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

/** Peralihan otomatis Gemini → Grok (cadangan) saat Gemini gagal. */
class GrokCadanganTest extends TestCase
{
    use UsesPostgresTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPostgresTestSchema();
        Cache::flush();
        config([
            'services.gemini.key' => 'g', 'services.gemini.model' => 'gemini-2.5-flash-lite',
            'services.xai.key' => 'x', 'services.xai.model' => 'grok-uji', 'services.xai.base_url' => 'https://api.x.ai/v1',
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownPostgresTestSchema();
        parent::tearDown();
    }

    private const JSON_OK = '{"judul":"Laporan dari Grok","skor":88}';

    private function grokOk(string $isi = self::JSON_OK): array
    {
        return ['choices' => [['message' => ['content' => $isi]]]];
    }

    private function gemini429Harian(): array
    {
        return ['error' => ['code' => 429, 'status' => 'RESOURCE_EXHAUSTED', 'message' => 'quota', 'details' => [
            ['@type' => 'type.googleapis.com/google.rpc.QuotaFailure', 'violations' => [[
                'quotaId' => 'GenerateRequestsPerDayPerProjectPerModel-FreeTier', 'quotaValue' => '20', 'quotaDimensions' => ['model' => 'gemini-2.5-flash-lite']]]],
            ['@type' => 'type.googleapis.com/google.rpc.RetryInfo', 'retryDelay' => '14137s'],
        ]]];
    }

    private function gemini200(string $isi = '{"judul":"dari Gemini"}'): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $isi]]]]]];
    }

    private function ai(int $fitur = 1, string $jawab = '1'): array
    {
        $profil = ['name' => 'Budi', 'jenis_kelamin' => 'Laki-laki', 'dob' => '1990-01-01', 'birth_hour' => 8, 'province' => 'DKI JAKARTA',
            'kota' => 'Jakarta', 'kecamatan' => 'A', 'kelurahan' => 'B', 'agama' => null, 'anak_ke' => 1, 'jumlah_saudara' => 1, 'status_pernikahan' => 'Lajang'];
        $j = [];
        foreach (range(1, 10) as $i) $j["q{$i}"] = (int) $jawab;
        $svc = app(GeminiService::class);
        $teks = $svc->generate($fitur, KalkulasiEngine::hitung('1990-01-01', 8), $j, $profil);

        return [$teks, $svc];
    }

    public function test_gemini_kuota_habis_dialihkan_otomatis_ke_grok_dalam_satu_permintaan(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->gemini429Harian(), 429),
            'api.x.ai/*' => Http::response($this->grokOk(), 200),
        ]);

        [$teks, $svc] = $this->ai();

        $this->assertSame(self::JSON_OK, $teks);
        $this->assertSame('grok', $svc->penyedia);
        $this->assertNotNull(LayananStatus::ringkas('grok')['terakhir_ok']);
        $this->assertSame(1, LayananStatus::ringkas('gemini')['gagal_24j'], 'kegagalan Gemini tetap tercatat di panel');
    }

    public function test_setelah_kuota_harian_habis_permintaan_berikutnya_langsung_ke_grok_tanpa_memanggil_gemini(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->gemini429Harian(), 429),
            'api.x.ai/*' => Http::response($this->grokOk(), 200),
        ]);

        $this->ai(1, '1');                       // memicu 429 harian → penanda jeda
        $this->assertTrue(Cache::has(GeminiService::KUNCI_JEDA));

        Http::fake(['api.x.ai/*' => Http::response($this->grokOk(), 200)]); // dari sini Gemini tak boleh dipanggil
        $this->ai(2, '0');

        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'generativelanguage'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.x.ai/v1/chat/completions'));
    }

    public function test_tanpa_cadangan_perilaku_lama_galat_gemini_diteruskan(): void
    {
        config(['services.xai.key' => null, 'services.xai.model' => null]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->gemini429Harian(), 429)]);

        $this->expectException(GeminiRateLimitException::class);
        $this->ai();
    }

    public function test_gemini_normal_tidak_menyentuh_grok(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->gemini200(), 200),
            'api.x.ai/*' => Http::response($this->grokOk(), 200),
        ]);

        [$teks, $svc] = $this->ai();

        $this->assertSame('{"judul":"dari Gemini"}', $teks);
        $this->assertSame('gemini', $svc->penyedia);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'api.x.ai'));
    }

    public function test_bila_grok_juga_gagal_galat_gemini_yang_diteruskan_agar_pekerjaan_menunggu(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->gemini429Harian(), 429),
            'api.x.ai/*' => Http::response(['error' => 'saldo habis'], 429),
        ]);

        try {
            $this->ai();
            $this->fail('seharusnya gagal');
        } catch (GeminiRateLimitException $e) {
            $this->assertSame(14137, $e->retryAfterSeconds);
        }

        $this->assertStringContainsString('HTTP 429', ServiceEvent::where('layanan', 'grok')->latest('id')->value('pesan'));
    }

    public function test_grok_json_tidak_valid_ditolak_dan_tidak_disimpan(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->gemini429Harian(), 429),
            'api.x.ai/*' => Http::response($this->grokOk('ini bukan json sama sekali'), 200),
        ]);

        try {
            $this->ai();
            $this->fail('seharusnya gagal');
        } catch (GeminiRateLimitException $e) {
            $this->assertTrue(true);
        }

        $this->assertStringContainsString('JSON tidak valid', ServiceEvent::where('layanan', 'grok')->latest('id')->value('pesan'));
    }

    public function test_grok_json_dengan_pagar_kode_dan_koma_berlebih_diperbaiki(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->gemini429Harian(), 429),
            'api.x.ai/*' => Http::response($this->grokOk("```json\n{\"a\": [1, 2, ],\n \"b\": \"x\"}\n```"), 200),
        ]);

        [$teks] = $this->ai();

        $this->assertSame(['a' => [1, 2], 'b' => 'x'], json_decode($teks, true));
    }

    public function test_grok_menolak_parameter_opsional_diulang_dengan_permintaan_minimal(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->gemini429Harian(), 429),
            'api.x.ai/*' => Http::sequence()->push(['error' => 'unsupported parameter'], 400)->push($this->grokOk(), 200),
        ]);

        [$teks, $svc] = $this->ai();

        $this->assertSame('grok', $svc->penyedia);
        $kirimGrok = Http::recorded(fn ($req) => str_contains($req->url(), 'api.x.ai'))->values();
        $this->assertCount(2, $kirimGrok);
        $this->assertArrayHasKey('response_format', $kirimGrok[0][0]->data());
        $this->assertArrayNotHasKey('response_format', $kirimGrok[1][0]->data());
        $this->assertSame('grok-uji', $kirimGrok[1][0]->data()['model']);
        $this->assertSame('Bearer x', $kirimGrok[0][0]->header('Authorization')[0]);
    }

    public function test_pekerjaan_laporan_selesai_lewat_grok_dan_mencatat_penyedia(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->gemini429Harian(), 429),
            'api.x.ai/*' => Http::response($this->grokOk(), 200),
        ]);
        $u = User::create(['name' => 'P', 'email' => strtolower(Str::random(8)) . '@example.test', 'dob' => '1990-01-01', 'email_verified_at' => now()]);
        $r = AiReport::create(['user_id' => $u->id, 'feature_id' => 1, 'locale' => 'id', 'data_hash' => Str::random(20), 'cached_at' => now()]);
        $jawab = [];
        foreach (range(1, 10) as $i) $jawab["q{$i}"] = 1;
        $profil = ['name' => 'Budi', 'jenis_kelamin' => 'Laki-laki', 'dob' => '1990-01-01', 'birth_hour' => 8, 'province' => 'DKI JAKARTA',
            'kota' => 'Jakarta', 'kecamatan' => 'A', 'kelurahan' => 'B', 'agama' => null, 'anak_ke' => 1, 'jumlah_saudara' => 1, 'status_pernikahan' => 'Lajang'];

        (new GenerateAIReport($r->id, 1, KalkulasiEngine::hitung('1990-01-01', 8), $jawab, $profil))->handle(app(GeminiService::class));

        $r->refresh();
        $this->assertSame(self::JSON_OK, $r->response_text);
        $this->assertSame('grok', $r->provider);
    }

    public function test_hasil_dari_cache_mempertahankan_penyedia_aslinya(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->gemini429Harian(), 429),
            'api.x.ai/*' => Http::response($this->grokOk(), 200),
        ]);

        $this->ai();                       // dihasilkan Grok
        [, $kedua] = $this->ai();          // jawaban sama → cache
        $this->assertSame('grok', $kedua->penyedia);
        Http::assertSentCount(2);          // 1x Gemini 429 + 1x Grok; kali kedua tanpa panggilan
    }

    public function test_tombol_tes_grok_menampilkan_model_tersedia_dan_hasil_uji(): void
    {
        Http::fake([
            'api.x.ai/v1/models' => Http::response(['data' => [['id' => 'grok-uji'], ['id' => 'grok-lain']]], 200),
            'api.x.ai/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => '{"status":"ok","pesan":"Layanan berjalan baik."}']]]], 200),
        ]);

        $this->withSession(['admin_logged_in' => true])->post(route('admin.system.tes-grok'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'AI cadangan berfungsi') && str_contains($m, 'JSON valid') && str_contains($m, 'Layanan berjalan baik') && str_contains($m, 'grok-lain'));
        $this->assertNotNull(LayananStatus::ringkas('grok')['terakhir_ok']);
    }

    public function test_tes_grok_tanpa_model_atau_model_salah_menampilkan_daftar_yang_tersedia(): void
    {
        Http::fake(['api.x.ai/v1/models' => Http::response(['data' => [['id' => 'grok-a'], ['id' => 'grok-b']]], 200)]);

        config(['services.xai.model' => null]);
        $this->withSession(['admin_logged_in' => true])->post(route('admin.system.tes-grok'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'XAI_MODEL belum diisi') && str_contains($m, 'grok-a, grok-b'));

        config(['services.xai.model' => 'tidak-ada']);
        $this->withSession(['admin_logged_in' => true])->post(route('admin.system.tes-grok'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'tidak ada di akun ini') && str_contains($m, 'grok-a'));

        config(['services.xai.key' => null]);
        $this->withSession(['admin_logged_in' => true])->post(route('admin.system.tes-grok'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'XAI_API_KEY'));
    }

    public function test_panel_sistem_menampilkan_status_cadangan_dan_tombol_dilindungi_admin(): void
    {
        $this->withSession(['admin_logged_in' => true])->get(route('admin.system'))
            ->assertOk()->assertSee('Cadangan AI:')->assertSee('AKTIF — model grok-uji')->assertSee('Tes AI cadangan');

        config(['services.xai.key' => null, 'services.xai.model' => null]);
        $this->withSession(['admin_logged_in' => true])->get(route('admin.system'))->assertSee('belum diaktifkan');

        $this->flushSession();
        $this->post(route('admin.system.tes-grok'))->assertRedirect(route('admin.login'));
    }

    public function test_kunci_groq_gsk_pada_alamat_xai_dijelaskan_tanpa_mengirim_kunci_ke_mana_pun(): void
    {
        Http::fake();
        config(['services.xai.key' => 'gsk_contohKunciGroq', 'services.xai.base_url' => 'https://api.x.ai/v1']);

        $this->withSession(['admin_logged_in' => true])->post(route('admin.system.tes-grok'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'GroqCloud') && str_contains($m, 'XAI_BASE_URL=https://api.groq.com/openai/v1') && str_contains($m, 'console.x.ai'));

        Http::assertNothingSent(); // kunci tidak dikirim ke alamat yang salah

        config(['services.xai.key' => 'xai-contoh', 'services.xai.base_url' => 'https://api.groq.com/openai/v1']);
        $this->withSession(['admin_logged_in' => true])->post(route('admin.system.tes-grok'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'kunci xAI') && str_contains($m, 'https://api.x.ai/v1'));
        Http::assertNothingSent();
    }

    public function test_alamat_groq_dengan_kunci_gsk_berfungsi_lewat_jalur_yang_sama(): void
    {
        config(['services.xai.key' => 'gsk_contoh', 'services.xai.base_url' => 'https://api.groq.com/openai/v1', 'services.xai.model' => 'llama-uji']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->gemini429Harian(), 429),
            'api.groq.com/openai/v1/chat/completions' => Http::response($this->grokOk(), 200),
        ]);

        [$teks, $svc] = $this->ai();

        $this->assertSame(self::JSON_OK, $teks);
        $this->assertSame('grok', $svc->penyedia);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.groq.com/openai/v1/chat/completions') && $r->header('Authorization')[0] === 'Bearer gsk_contoh');
    }

    public function test_batas_token_ditambah_ruang_penalaran_dan_reasoning_effort_opsional(): void
    {
        config(['services.xai.ruang_penalaran' => 2500, 'services.xai.reasoning_effort' => 'low']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->gemini429Harian(), 429),
            'api.x.ai/*' => Http::response($this->grokOk(), 200),
        ]);

        $this->ai(12, '1'); // fitur 12: batas keluaran Gemini 800 token

        $kirim = Http::recorded(fn ($r) => str_contains($r->url(), 'api.x.ai'))->values();
        $this->assertSame(800 + 2500, $kirim[0][0]->data()['max_completion_tokens']);
        $this->assertSame('low', $kirim[0][0]->data()['reasoning_effort']);

        // tanpa reasoning_effort → parameter tidak dikirim
        config(['services.xai.reasoning_effort' => null]);
        Cache::flush();
        Http::fake(['api.x.ai/*' => Http::response($this->grokOk(), 200)]);
        Cache::put(GeminiService::KUNCI_JEDA, true, 600);
        $this->ai(12, '0');
        $lagi = Http::recorded(fn ($r) => str_contains($r->url(), 'api.x.ai'))->values();
        $this->assertArrayNotHasKey('reasoning_effort', $lagi[0][0]->data());
    }

    public function test_tes_tidak_menyatakan_berfungsi_bila_balasan_kosong(): void
    {
        // Model penalaran yang kehabisan token mengembalikan isi kosong — itu BUKAN keberhasilan
        Http::fake([
            'api.x.ai/v1/models' => Http::response(['data' => [['id' => 'grok-uji']]], 200),
            'api.x.ai/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => '']]]], 200),
        ]);

        $this->withSession(['admin_logged_in' => true])->post(route('admin.system.tes-grok'))
            ->assertSessionMissing('success')
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'AI cadangan menolak panggilan uji') && str_contains($m, 'JSON'));
        $this->assertTrue(LayananStatus::ringkas('grok')['perlu_cek']);
    }

    public function test_panggilan_uji_memakai_ruang_penalaran_seperti_laporan_nyata(): void
    {
        config(['services.xai.ruang_penalaran' => 2500]);
        Http::fake([
            'api.x.ai/v1/models' => Http::response(['data' => [['id' => 'grok-uji']]], 200),
            'api.x.ai/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => '{"status":"ok","pesan":"siap"}']]]], 200),
        ]);

        $this->withSession(['admin_logged_in' => true])->post(route('admin.system.tes-grok'))->assertSessionHas('success');

        $kirim = Http::recorded(fn ($r) => str_contains($r->url(), 'chat/completions'))->values();
        $this->assertSame(80 + 2500, $kirim[0][0]->data()['max_completion_tokens'], 'bukan 20 token yang habis untuk berpikir');
        $this->assertSame('json_object', $kirim[0][0]->data()['response_format']['type']);
    }
}
