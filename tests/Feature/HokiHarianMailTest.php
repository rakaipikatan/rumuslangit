<?php

namespace Tests\Feature;

use App\Console\Commands\KirimHokiHarian;
use App\Mail\HokiHarianMail;
use Illuminate\Support\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class HokiHarianMailTest extends TestCase
{
    private array $hoki = ['angka' => [7, 3, 9], 'warna' => ['Krem', 'Oranye'], 'mantra' => 'Semangat hari ini'];

    public function test_dua_warna_tampil_dengan_ampersand_bukan_kode_html_mentah(): void
    {
        $html = (new HokiHarianMail('Budi', $this->hoki))->render();

        $this->assertStringContainsString('Krem &amp; Oranye', $html);       // HTML benar → tampil "Krem & Oranye"
        $this->assertStringNotContainsString('&amp;amp;', $html);            // bukan terlepas ganda
    }

    public function test_tanggal_dan_subjek_memakai_wib_bukan_utc(): void
    {
        // 06:00 WIB tanggal 11 Nov = 23:00 UTC tanggal 10 Nov (saat penjadwal berjalan)
        $this->travelTo(Carbon::parse('2026-11-10 23:00:00', 'UTC'));
        $mail = new HokiHarianMail('Budi', $this->hoki);

        $this->assertStringContainsString('11 November 2026', $mail->envelope()->subject);
        $html = $mail->render();
        $this->assertStringContainsString('Rabu, 11 November 2026', $html);
        $this->assertStringNotContainsString('10 November 2026', $html);
    }

    private function hoki(string $waktuUtc): array
    {
        $this->travelTo(Carbon::parse($waktuUtc, 'UTC'));
        $cmd = new KirimHokiHarian();
        $m   = new ReflectionMethod($cmd, 'generateHoki');
        $m->setAccessible(true);

        return $m->invoke($cmd, ['weton' => ['neptu' => 12]], 42);
    }

    public function test_angka_hoki_berganti_tengah_malam_wib_bukan_utc(): void
    {
        $a = $this->hoki('2026-11-10 17:30:00'); // 11 Nov 00:30 WIB
        $b = $this->hoki('2026-11-11 10:00:00'); // 11 Nov 17:00 WIB — hari WIB yang sama
        $c = $this->hoki('2026-11-11 17:30:00'); // 12 Nov 00:30 WIB — hari WIB berikutnya

        $this->assertSame($a, $b, 'satu hari WIB = angka yang sama');
        $this->assertNotSame($a, $c, 'hari WIB berikutnya = angka baru');
    }
}
