<?php

namespace App\Services;

class KalkulasiEngine
{
    private static array $hariJawa = [
        'Sunday'    => ['nama' => 'Minggu', 'neptu' => 5],
        'Monday'    => ['nama' => 'Senin',  'neptu' => 4],
        'Tuesday'   => ['nama' => 'Selasa', 'neptu' => 3],
        'Wednesday' => ['nama' => 'Rabu',   'neptu' => 7],
        'Thursday'  => ['nama' => 'Kamis',  'neptu' => 8],
        'Friday'    => ['nama' => 'Jumat',  'neptu' => 6],
        'Saturday'  => ['nama' => 'Sabtu',  'neptu' => 9],
    ];

    private static array $pasaranJawa = [
        ['nama' => 'Legi',   'neptu' => 5],
        ['nama' => 'Pahing', 'neptu' => 9],
        ['nama' => 'Pon',    'neptu' => 7],
        ['nama' => 'Wage',   'neptu' => 4],
        ['nama' => 'Kliwon', 'neptu' => 8],
    ];

    private static array $shioList = [
        'Tikus', 'Kerbau', 'Macan', 'Kelinci', 'Naga', 'Ular',
        'Kuda', 'Kambing', 'Monyet', 'Ayam', 'Anjing', 'Babi',
    ];

    private static array $zodiak = [
        ['nama' => 'Capricorn',  'dari' => '01-01', 'sampai' => '01-19'],
        ['nama' => 'Aquarius',   'dari' => '01-20', 'sampai' => '02-18'],
        ['nama' => 'Pisces',     'dari' => '02-19', 'sampai' => '03-20'],
        ['nama' => 'Aries',      'dari' => '03-21', 'sampai' => '04-19'],
        ['nama' => 'Taurus',     'dari' => '04-20', 'sampai' => '05-20'],
        ['nama' => 'Gemini',     'dari' => '05-21', 'sampai' => '06-20'],
        ['nama' => 'Cancer',     'dari' => '06-21', 'sampai' => '07-22'],
        ['nama' => 'Leo',        'dari' => '07-23', 'sampai' => '08-22'],
        ['nama' => 'Virgo',      'dari' => '08-23', 'sampai' => '09-22'],
        ['nama' => 'Libra',      'dari' => '09-23', 'sampai' => '10-22'],
        ['nama' => 'Scorpio',    'dari' => '10-23', 'sampai' => '11-21'],
        ['nama' => 'Sagitarius', 'dari' => '11-22', 'sampai' => '12-21'],
        ['nama' => 'Capricorn',  'dari' => '12-22', 'sampai' => '12-31'],
    ];

    public static function hitung(string $dob, int $birthHour): array
    {
        $date    = new \DateTime($dob);
        $tahun   = (int) $date->format('Y');
        $bulan   = (int) $date->format('m');
        $tanggal = (int) $date->format('d');

        return [
            'weton'  => self::hitungWeton($date),
            'shio'   => self::hitungShio($tahun, $bulan),
            'zodiak' => self::hitungZodiak($bulan, $tanggal),
            'elemen' => self::hitungElemen($tahun),
            'radar'  => self::hitungRadar($date, $birthHour),
        ];
    }

    private static function hitungWeton(\DateTime $date): array
    {
        $hariInggris = $date->format('l');
        $neptHari    = self::$hariJawa[$hariInggris]['neptu'];
        $namaHari    = self::$hariJawa[$hariInggris]['nama'];

        $epoch   = new \DateTime('1900-01-01');
        $diff    = (int) $epoch->diff($date)->days;
        $pasaran = self::$pasaranJawa[($diff + 1) % 5];

        return [
            'hari'    => $namaHari,
            'pasaran' => $pasaran['nama'],
            'weton'   => "$namaHari {$pasaran['nama']}",
            'neptu'   => $neptHari + $pasaran['neptu'],
        ];
    }

    private static function hitungShio(int $tahun, int $bulan): array
    {
        $tahunShio = ($bulan < 2) ? $tahun - 1 : $tahun;
        $index     = ($tahunShio - 1900) % 12;
        if ($index < 0) $index += 12;

        return [
            'nama'  => self::$shioList[$index],
            'tahun' => $tahunShio,
        ];
    }

    private static function hitungZodiak(int $bulan, int $tanggal): string
    {
        $key = sprintf('%02d-%02d', $bulan, $tanggal);
        foreach (self::$zodiak as $z) {
            if ($key >= $z['dari'] && $key <= $z['sampai']) {
                return $z['nama'];
            }
        }
        return 'Capricorn';
    }

    private static function hitungElemen(int $tahun): string
    {
        $elemenMap = [
            0 => 'Logam', 1 => 'Logam',
            2 => 'Air',   3 => 'Air',
            4 => 'Kayu',  5 => 'Kayu',
            6 => 'Api',   7 => 'Api',
            8 => 'Tanah', 9 => 'Tanah',
        ];
        return $elemenMap[$tahun % 10] ?? 'Tanah';
    }

    private static function hitungRadar(\DateTime $date, int $birthHour): array
    {
        $seed = ((int)$date->format('N') * 13)
              + ((int)$date->format('m') * 7)
              + ((int)$date->format('Y') % 100)
              + ($birthHour * 3);

        return [
            'karir'     => ($seed * 3)  % 41 + 60,
            'asmara'    => ($seed * 7)  % 36 + 55,
            'cuan'      => ($seed * 11) % 38 + 52,
            'kesehatan' => ($seed * 5)  % 33 + 58,
            'spiritual' => ($seed * 9)  % 40 + 50,
        ];
    }
}
