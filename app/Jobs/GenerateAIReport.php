<?php

namespace App\Jobs;

use App\Models\AiReport;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\RateLimiter;

class GenerateAIReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $maxExceptions = 3;

    /**
     * Batas WAKTU, bukan jumlah percobaan: saat kuota harian Gemini habis, pekerjaan menunggu
     * (dilepas kembali ke antrean) sampai pulih, bukan gagal permanen setelah 10 percobaan singkat.
     * Kegagalan "nyata" tetap dibatasi $maxExceptions.
     */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(8);
    }

    public function backoff(): array
    {
        return [30, 60, 120, 180];
    }

    public function __construct(
        public int    $reportId,
        public int    $featureId,
        public array  $kalkulasi,
        public array  $answers,
        public array  $userProfile,
        public array  $tarotData = [],
        public string $locale = 'id',
    ) {}

    // Backward compat: old serialized jobs tidak punya $tarotData / $locale
    public function __wakeup(): void
    {
        if (!isset($this->tarotData)) {
            $this->tarotData = [];
        }
        if (!isset($this->locale)) {
            $this->locale = 'id';
        }
    }

    public function handle(GeminiService $gemini): void
    {
        // Throttle: maks 12 request/menit ke Gemini dari seluruh workers
        // Ini mencegah "synchronized retry storm" saat banyak jobs jalan bersamaan
        $executed = RateLimiter::attempt(
            key: 'gemini-api-calls',
            maxAttempts: 12,
            callback: function () use ($gemini, &$text) {
                try {
                    $text = $gemini->generate(
                        $this->featureId,
                        $this->kalkulasi,
                        $this->answers,
                        $this->userProfile,
                        $this->tarotData,
                        $this->locale,
                    );
                } catch (\App\Exceptions\GeminiRateLimitException $e) {
                    $text = null;
                    $delay = min(max($e->retryAfterSeconds, 30), 3600) + rand(0, 15); // ikuti Google, tunda maks. 1 jam sekali + jitter
                    \Log::info("Gemini rate-limited (429), release job #{$this->reportId} in {$delay}s");
                    $this->release($delay);
                }
            },
            decaySeconds: 60,
        );

        if (!$executed) {
            // Local rate limiter penuh — tunggu sebentar dengan jitter agar tidak semua retry serentak
            $delay = rand(20, 45);
            \Log::info("Gemini local throttle, release job #{$this->reportId} in {$delay}s");
            $this->release($delay);
            return;
        }

        // Jika $text null berarti sudah di-release karena rate limit
        if (!isset($text) || $text === null) {
            return;
        }

        AiReport::where('id', $this->reportId)->update([
            'response_text' => $text,
            'provider'      => $gemini->penyedia, // gemini | grok (cadangan)
            'cached_at'     => now(),
        ]);
    }
}
