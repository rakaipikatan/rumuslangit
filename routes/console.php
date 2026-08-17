<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Services\WhatsAppService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('otp:test {nomor : Nomor WhatsApp format 628xxxxxxxxxx} {--show-code : Tampilkan kode OTP yang tersimpan di cache}', function (WhatsAppService $whatsapp) {
    $nomor = preg_replace('/\D+/', '', $this->argument('nomor'));

    if (! preg_match('/^628[0-9]{8,12}$/', $nomor)) {
        $this->error('Nomor harus format Indonesia, contoh: 6281234567890');
        return 1;
    }

    try {
        $hash = $whatsapp->kirimOTP($nomor);
        $this->info('Request OTP sudah dikirim via provider: ' . $whatsapp->provider());
        $this->line("phone_hash: {$hash}");

        if ($this->option('show-code')) {
            $this->warn('DEBUG OTP: ' . Cache::get("otp:{$hash}"));
        }

        $this->line('Cek juga log: tail -f storage/logs/laravel.log');
        return 0;
    } catch (Throwable $e) {
        $this->error('Gagal kirim OTP: ' . $e->getMessage());
        return 1;
    }
})->purpose('Tes pengiriman OTP WhatsApp');

Artisan::command('waha:status', function () {
    $headers = [];

    if (config('services.waha.api_key')) {
        $headers['X-Api-Key'] = config('services.waha.api_key');
    }

    $baseUrl = rtrim(config('services.waha.base_url'), '/');
    $session = config('services.waha.session', 'default');

    $response = Http::timeout(10)
        ->withHeaders($headers)
        ->get("{$baseUrl}/api/sessions/{$session}");

    if ($response->failed()) {
        $this->error($response->body());
        return 1;
    }

    $data = $response->json();
    $this->info('WAHA session: ' . ($data['name'] ?? $session));
    $this->line('status: ' . ($data['status'] ?? 'unknown'));

    if (! empty($data['me'])) {
        $this->line('me: ' . json_encode($data['me']));
    }

    return 0;
})->purpose('Cek status session WAHA');
