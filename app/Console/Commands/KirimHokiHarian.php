<?php

namespace App\Console\Commands;

use App\Mail\HokiHarianMail;
use App\Models\User;
use App\Services\KalkulasiEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class KirimHokiHarian extends Command
{
    protected $signature   = 'hoki:kirim';
    protected $description = 'Kirim angka & warna hoki harian ke semua subscriber aktif via Email';

    public function handle(): int
    {
        $subscribers = User::where('subscription_status', 'active')
            ->whereNotNull('email')
            ->whereNotNull('email_verified_at')
            ->get();

        $terkirim = 0;

        foreach ($subscribers as $user) {
            try {
                $kalkulasi = KalkulasiEngine::hitung(
                    $user->dob->toDateString(),
                    (int) $user->birth_hour
                );

                $hoki = $this->generateHoki($kalkulasi, $user->id);

                Mail::to($user->email)->send(new HokiHarianMail($user->name, $hoki));
                $terkirim++;
            } catch (\Throwable $e) {
                $this->warn("Gagal kirim ke user #{$user->id}: {$e->getMessage()}");
            }
        }

        $this->info("Hoki harian terkirim ke {$terkirim} subscriber.");
        return self::SUCCESS;
    }

    private function generateHoki(array $kalkulasi, int $userId): array
    {
        $seed   = $kalkulasi['weton']['neptu'] + $userId + (int) now()->format('Ymd');
        $warna  = ['Merah', 'Biru', 'Hijau', 'Kuning', 'Ungu', 'Putih', 'Hitam', 'Oranye', 'Tosca', 'Krem'];
        $mantra = [
            'Hari ini adalah kanvas terbaik untuk melukis impian Anda.',
            'Energi semesta mendukung setiap langkah positif Anda hari ini.',
            'Kesempatan besar menanti mereka yang tetap fokus dan bersyukur.',
            'Vibrasi tinggi Anda hari ini menarik kelimpahan yang Anda layak dapatkan.',
            'Alam semesta berkonspirasi untuk kebaikan Anda hari ini.',
        ];

        return [
            'angka'  => [($seed * 3) % 10, ($seed * 7) % 10, ($seed * 11) % 10],
            'warna'  => [$warna[$seed % 10], $warna[($seed * 3) % 10]],
            'mantra' => $mantra[$seed % 5],
        ];
    }
}
