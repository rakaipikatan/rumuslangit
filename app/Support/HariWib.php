<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * "Hari" bagi pengguna = hari kalender WIB (Asia/Jakarta), walau aplikasi & database memakai UTC.
 * Dipakai untuk: pemakaian ulang laporan di hari yang sama, cache Gemini per hari, dan batas harian.
 */
class HariWib
{
    public const ZONA = 'Asia/Jakarta';

    /** Awal hari ini (00:00 WIB) dinyatakan dalam zona waktu aplikasi — siap dipakai membandingkan kolom DB. */
    public static function awal(): Carbon
    {
        return Carbon::now(self::ZONA)->startOfDay()->setTimezone(config('app.timezone'));
    }

    /** Tanggal hari ini dalam WIB (Y-m-d). */
    public static function tanggal(): string
    {
        return Carbon::now(self::ZONA)->toDateString();
    }
}
