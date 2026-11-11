<?php

namespace Tests\Feature;

use App\Services\GeminiService;
use App\Services\KalkulasiEngine;
use App\Support\HariWib;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiCachePerHariTest extends TestCase
{
    private function profil(): array
    {
        return ['name' => 'Budi', 'jenis_kelamin' => 'Laki-laki', 'dob' => '1990-01-01', 'birth_hour' => 8,
            'province' => 'DKI JAKARTA', 'kota' => 'Jakarta', 'kecamatan' => 'A', 'kelurahan' => 'B',
            'agama' => null, 'anak_ke' => 1, 'jumlah_saudara' => 1, 'status_pernikahan' => 'Lajang'];
    }

    private function jawaban(): array
    {
        $a = [];
        foreach (range(1, 10) as $i) $a["q{$i}"] = 1;

        return $a;
    }

    private function generate(int $fitur = 3): string
    {
        config(['services.gemini.key' => 'k']);

        return app(GeminiService::class)->generate($fitur, KalkulasiEngine::hitung('1990-01-01', 8), $this->jawaban(), $this->profil());
    }

    public function test_cache_gemini_per_hari_kalender_wib(): void
    {
        config(['payment.fitur_harian' => [3]]);
        Cache::flush();
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Teks laporan']]]]]], 200)]);

        // 10 Nov 23:00 WIB (16:00 UTC): dua pemanggilan di hari yang sama → hanya 1 permintaan ke Gemini
        $this->travelTo(Carbon::parse('2026-11-10 16:00:00', 'UTC'));
        $this->generate();
        $this->generate();
        Http::assertSentCount(1);

        // 11 Nov 01:00 WIB (18:00 UTC) — baru 2 jam kemudian tetapi sudah hari baru → permintaan baru
        $this->travelTo(Carbon::parse('2026-11-10 18:00:00', 'UTC'));
        $this->generate();
        Http::assertSentCount(2);
    }

    public function test_orang_berdata_lahir_sama_tidak_berbagi_cache_nama_tidak_bocor(): void
    {
        config(['services.gemini.key' => 'k']);
        Cache::flush();
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => '{"ok":true}']]]]]], 200)]);

        $kalk = KalkulasiEngine::hitung('1990-01-01', 8);
        $a = $this->profil();
        $b = array_merge($this->profil(), ['name' => 'Siti']);

        app(GeminiService::class)->generate(0, $kalk, [], $a);
        app(GeminiService::class)->generate(0, $kalk, [], $b);   // nama beda → harus panggilan baru, bukan hasil Budi
        Http::assertSentCount(2);

        app(GeminiService::class)->generate(0, $kalk, [], $a);   // orang yang sama → cache tetap bekerja
        Http::assertSentCount(2);
    }

    public function test_awal_hari_wib_dalam_zona_waktu_aplikasi(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 16:59:00', 'UTC')); // 23:59 WIB
        $this->assertSame('2026-11-09 17:00:00', HariWib::awal()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-10', HariWib::tanggal());

        $this->travelTo(Carbon::parse('2026-11-10 17:01:00', 'UTC')); // 00:01 WIB hari berikutnya
        $this->assertSame('2026-11-10 17:00:00', HariWib::awal()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-11', HariWib::tanggal());
    }

    public function test_fitur_non_harian_cache_tidak_bergantung_hari_dan_prompt_tanpa_tanggal(): void
    {
        config(['payment.fitur_harian' => []]);
        Cache::flush();
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Teks laporan']]]]]], 200)]);

        $this->travelTo(Carbon::parse('2026-11-10 03:00:00', 'UTC')); // 10 Nov 10:00 WIB
        $this->generate(1);
        $this->travelTo(Carbon::parse('2026-11-11 02:00:00', 'UTC')); // 11 Nov 09:00 WIB — hari WIB berikutnya, masih < 24 jam
        $this->generate(1);

        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => !str_contains(json_encode($r->data()), 'KONTEKS WAKTU'));
    }

    public function test_fitur_bergantung_tanggal_mengirim_tanggal_hari_ini_ke_ai(): void
    {
        config(['payment.fitur_harian' => [3]]);
        Cache::flush();
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Teks laporan']]]]]], 200)]);

        $this->travelTo(Carbon::parse('2026-11-10 17:30:00', 'UTC')); // 11 Nov 00:30 WIB
        $this->generate(3);

        Http::assertSent(function ($r) {
            $isi = json_encode($r->data(), JSON_UNESCAPED_UNICODE);

            return str_contains($isi, 'KONTEKS WAKTU') && str_contains($isi, '11 November 2026');
        });
    }
}
