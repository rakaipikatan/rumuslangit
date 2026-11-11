<?php

namespace Tests\Unit;

use App\Support\JamLahir;
use PHPUnit\Framework\TestCase;

class JamLahirTest extends TestCase
{
    public function test_format_valid_diubah_ke_jam(): void
    {
        $this->assertSame(7, JamLahir::keJam('07:30'));
        $this->assertSame(14, JamLahir::keJam('14.05'));
        $this->assertSame(0, JamLahir::keJam('0:00'));
        $this->assertSame(23, JamLahir::keJam('23:59'));
    }

    public function test_kosong_atau_tidak_valid_jadi_nol(): void
    {
        $this->assertSame(0, JamLahir::keJam(null));
        $this->assertSame(0, JamLahir::keJam(''));
        $this->assertSame(0, JamLahir::keJam('25:00'));
        $this->assertSame(0, JamLahir::keJam('abc'));
    }

    public function test_aturan_validasi_regex(): void
    {
        $regex = JamLahir::ATURAN[1];
        $pola = '/^([01]?\d|2[0-3])[:.][0-5]\d$/';
        $this->assertStringContainsString('regex:', $regex);
        foreach (['07:30', '7:30', '23:59', '00:00', '14.05'] as $ok) {
            $this->assertSame(1, preg_match($pola, $ok), $ok);
        }
        foreach (['24:00', '07:60', '730', '7', 'ab:cd', '07:3'] as $bad) {
            $this->assertSame(0, preg_match($pola, $bad), $bad);
        }
    }
}
