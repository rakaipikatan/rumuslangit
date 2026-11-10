<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class KirimEmailUji extends Command
{
    protected $signature   = 'mail:uji {email : Alamat tujuan}';
    protected $description = 'Kirim email uji untuk memeriksa konfigurasi SMTP dan pengiriman';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Alamat email tidak valid.');
            return self::FAILURE;
        }

        $smtp = config('mail.mailers.smtp');
        $this->line('Mailer : ' . config('mail.default'));
        $this->line('Host   : ' . ($smtp['host'] ?? '-') . ':' . ($smtp['port'] ?? '-') . ' (scheme: ' . ($smtp['scheme'] ?? 'auto') . ')');
        $this->line('Dari   : ' . config('mail.from.address'));
        $this->line('Tujuan : ' . $email);

        try {
            Mail::raw(
                "Ini email uji dari Rumus Langit ({$_SERVER['REQUEST_TIME']}).\nJika Anda menerima ini, pengiriman email berfungsi.",
                fn ($m) => $m->to($email)->subject('Uji Email Rumus Langit')
            );
        } catch (\Throwable $e) {
            $this->error('GAGAL dikirim: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Server SMTP menerima email. Cek kotak masuk (dan folder Spam) tujuan dalam 1-2 menit.');
        return self::SUCCESS;
    }
}
