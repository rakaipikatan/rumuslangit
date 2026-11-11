<?php

namespace App\Support;

use App\Models\EmailLog;
use App\Models\User;
use Symfony\Component\Mime\Email;

/**
 * Mencatat setiap email keluar ke tabel email_logs.
 * Jenis dibawa lewat header X-RL-Jenis yang dipasang tiap Mailable / email uji; tanpa header = "lainnya".
 * Email uji (jenis berawalan "tes") TIDAK dikaitkan ke pengguna mana pun.
 * Tidak boleh PERNAH mengganggu pengiriman: semua kegagalan pencatatan ditelan.
 */
class EmailLogger
{
    public const HEADER = 'X-RL-Jenis';

    /** Dipasang pada Mailable/raw: $m->getHeaders()->addTextHeader(...) */
    public static function tandai(Email $message, string $jenis): void
    {
        $message->getHeaders()->addTextHeader(self::HEADER, $jenis);
    }

    /** Dipanggil dari event MessageSent: satu baris per penerima. */
    public static function catatTerkirim(Email $message): void
    {
        try {
            $jenis = $message->getHeaders()->has(self::HEADER)
                ? trim($message->getHeaders()->get(self::HEADER)->getBodyAsString())
                : 'lainnya';

            foreach ($message->getTo() as $alamat) {
                self::tulis($jenis, strtolower($alamat->getAddress()), 'terkirim', null);
            }
        } catch (\Throwable $e) {
            // abaikan
        }
    }

    public static function catatGagal(string $jenis, ?string $email, string $pesan): void
    {
        try {
            self::tulis($jenis, $email ? strtolower($email) : null, 'gagal', $pesan);
        } catch (\Throwable $e) {
            // abaikan
        }
    }

    public static function biaya(): int
    {
        return (int) config('payment.biaya_email', 5);
    }

    private static function tulis(string $jenis, ?string $email, string $status, ?string $pesan): void
    {
        $userId = null;
        if ($email && !str_starts_with($jenis, 'tes')) {
            $userId = User::whereRaw('lower(email) = ?', [$email])->value('id');
        }

        EmailLog::create([
            'user_id'     => $userId,
            'email'       => $email,
            'jenis'       => $jenis,
            'status'      => $status,
            'pesan_error' => $pesan ? mb_substr($pesan, 0, 300) : null,
            // Hanya email yang benar-benar terkirim memakai kredit SumoPod
            'biaya'       => $status === 'terkirim' ? self::biaya() : 0,
            'created_at'  => now(),
        ]);
    }
}
