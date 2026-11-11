<?php

namespace App\Services;

use App\Support\LayananStatus;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Penyedia AI CADANGAN (xAI Grok, API kompatibel OpenAI). Dipakai otomatis oleh GeminiService
 * bila Gemini gagal (kuota habis, gangguan, kunci bermasalah). Aktif hanya bila XAI_API_KEY dan XAI_MODEL terisi.
 */
class GrokService
{
    public function tersedia(): bool
    {
        return filled(config('services.xai.key')) && filled(config('services.xai.model'));
    }

    private function klien(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout((int) config('services.xai.timeout', 120))->acceptJson()->withToken((string) config('services.xai.key'));
    }

    private function url(string $jalur): string
    {
        return rtrim((string) config('services.xai.base_url'), '/') . $jalur;
    }

    /**
     * Hasilkan teks JSON dengan prompt yang sama seperti Gemini.
     * @throws RuntimeException bila API gagal atau JSON tidak valid
     */
    public function generate(string $sistem, string $prompt, float $suhu, int $maxToken): string
    {
        $model = (string) config('services.xai.model');
        $pesan = [
            ['role' => 'system', 'content' => $sistem . "\n\nBalas HANYA dengan satu objek JSON yang valid, tanpa teks lain di luar JSON."],
            ['role' => 'user', 'content' => $prompt],
        ];

        $penuh = [
            'model' => $model, 'messages' => $pesan, 'temperature' => $suhu,
            // batas keluaran + ruang penalaran (model penalaran memakai token untuk berpikir sebelum menjawab)
            'max_completion_tokens' => $maxToken + (int) config('services.xai.ruang_penalaran', 2500),
            'response_format' => ['type' => 'json_object'],
        ];
        if (filled(config('services.xai.reasoning_effort'))) {
            $penuh['reasoning_effort'] = (string) config('services.xai.reasoning_effort');
        }

        $r = $this->klien()->post($this->url('/chat/completions'), $penuh);

        // Parameter opsional (temperature / response_format / max_completion_tokens) bisa ditolak model tertentu → ulangi minimal
        if (in_array($r->status(), [400, 422], true)) {
            $r = $this->klien()->post($this->url('/chat/completions'), ['model' => $model, 'messages' => $pesan]);
        }

        if ($r->failed()) {
            $ket = 'HTTP ' . $r->status() . ': ' . substr($r->body(), 0, 200);
            LayananStatus::gagal('grok', $ket);
            throw new RuntimeException('Grok API gagal: ' . $ket);
        }

        $teks = (string) $r->json('choices.0.message.content', '');
        $teks = preg_replace('/^```(?:json)?\s*/m', '', $teks);
        $teks = trim(preg_replace('/\s*```$/m', '', $teks));
        $teks = GeminiService::repairJson($teks);

        if (json_decode($teks, true) === null) {
            LayananStatus::gagal('grok', 'JSON tidak valid dari Grok: ' . json_last_error_msg());
            throw new RuntimeException('Grok mengembalikan JSON yang tidak valid: ' . json_last_error_msg());
        }

        LayananStatus::ok('grok');

        return $teks;
    }

    /** Daftar ID model yang tersedia untuk kunci ini (gratis, tanpa memakai token). */
    public function daftarModel(): array
    {
        $r = $this->klien()->get($this->url('/models'));
        if ($r->failed()) {
            throw new RuntimeException('HTTP ' . $r->status() . ': ' . substr($r->body(), 0, 200));
        }

        return collect($r->json('data', []))->pluck('id')->filter()->sort()->values()->all();
    }

    /** Panggilan uji kecil → [balasan, milidetik]. */
    public function uji(): array
    {
        $model = (string) config('services.xai.model');
        $pesan = [['role' => 'user', 'content' => 'Balas satu kata: OK']];
        $mulai = microtime(true);

        $r = $this->klien()->post($this->url('/chat/completions'), ['model' => $model, 'messages' => $pesan, 'max_completion_tokens' => 20]);
        if (in_array($r->status(), [400, 422], true)) {
            $r = $this->klien()->post($this->url('/chat/completions'), ['model' => $model, 'messages' => $pesan]);
        }

        if ($r->failed()) {
            LayananStatus::gagal('grok', 'Tes admin: HTTP ' . $r->status());
            throw new RuntimeException('HTTP ' . $r->status() . ': ' . substr($r->body(), 0, 200));
        }

        LayananStatus::ok('grok', 'tes admin');

        return [trim((string) $r->json('choices.0.message.content', '')), (int) round((microtime(true) - $mulai) * 1000)];
    }
}
