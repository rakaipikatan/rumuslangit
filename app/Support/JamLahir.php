<?php

namespace App\Support;

/**
 * Jam lahir diisi bebas dengan format HH:MM (atau HH.MM). Perhitungan memakai jam (0-23);
 * menit hanya dicek formatnya. Kosong = tidak tahu → dianggap 0 (sama seperti sebelumnya).
 */
class JamLahir
{
    public const ATURAN = ['nullable', 'regex:/^([01]?\d|2[0-3])[:.][0-5]\d$/'];

    public static function keJam(?string $nilai): int
    {
        $nilai = trim((string) $nilai);
        if ($nilai === '' || ! preg_match('/^([01]?\d|2[0-3])[:.][0-5]\d$/', $nilai, $m)) {
            return 0;
        }

        return (int) $m[1];
    }
}
