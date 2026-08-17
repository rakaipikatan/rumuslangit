<?php

namespace Tests\Unit;

use App\Services\KalkulasiEngine;
use PHPUnit\Framework\TestCase;

class KalkulasiEngineTest extends TestCase
{
    // 17 Agustus 1945 (Hari Kemerdekaan RI) dikenal luas sebagai "Jumat Legi" —
    // dipakai sebagai titik referensi historis untuk memvalidasi epoch weton.
    public function test_weton_proklamasi_kemerdekaan(): void
    {
        $hasil = KalkulasiEngine::hitung('1945-08-17', 10);

        $this->assertSame('Jumat', $hasil['weton']['hari']);
        $this->assertSame('Legi', $hasil['weton']['pasaran']);
        $this->assertSame('Jumat Legi', $hasil['weton']['weton']);
        $this->assertSame(6 + 5, $hasil['weton']['neptu']); // neptu Jumat (6) + Legi (5)
    }

    public function test_weton_pasaran_berputar_5_hari_berurutan(): void
    {
        // Siklus pasaran harus konsisten 5-harian dari tanggal manapun.
        $hasil1 = KalkulasiEngine::hitung('2024-01-01', 0);
        $hasil2 = KalkulasiEngine::hitung('2024-01-06', 0);

        $this->assertSame($hasil1['weton']['pasaran'], $hasil2['weton']['pasaran']);
    }

    public function test_shio_tahun_2020_adalah_tikus(): void
    {
        $hasil = KalkulasiEngine::hitung('2020-06-15', 12);

        $this->assertSame('Tikus', $hasil['shio']['nama']);
        $this->assertSame(2020, $hasil['shio']['tahun']);
    }

    public function test_shio_bulan_januari_memakai_tahun_sebelumnya(): void
    {
        // Lahir Januari 2021 -> shio memakai tahun 2020 (Tikus), bukan 2021 (Kerbau).
        $hasil = KalkulasiEngine::hitung('2021-01-15', 8);

        $this->assertSame('Tikus', $hasil['shio']['nama']);
        $this->assertSame(2020, $hasil['shio']['tahun']);
    }

    public function test_zodiak_batas_tanggal_capricorn_ke_aquarius(): void
    {
        $this->assertSame('Capricorn', KalkulasiEngine::hitung('1990-01-19', 0)['zodiak']);
        $this->assertSame('Aquarius',  KalkulasiEngine::hitung('1990-01-20', 0)['zodiak']);
    }

    public function test_zodiak_batas_tanggal_sagitarius_ke_capricorn(): void
    {
        $this->assertSame('Sagitarius', KalkulasiEngine::hitung('1990-12-21', 0)['zodiak']);
        $this->assertSame('Capricorn',  KalkulasiEngine::hitung('1990-12-22', 0)['zodiak']);
    }

    public function test_elemen_mengikuti_pola_2_tahun_sesuai_batang_langit_tionghoa(): void
    {
        // Pola batang langit (heavenly stems): 0/1=Logam, 2/3=Air, 4/5=Kayu, 6/7=Api, 8/9=Tanah.
        $this->assertSame('Logam', KalkulasiEngine::hitung('2020-05-01', 0)['elemen']);
        $this->assertSame('Logam', KalkulasiEngine::hitung('2021-05-01', 0)['elemen']);
        $this->assertSame('Air',   KalkulasiEngine::hitung('2022-05-01', 0)['elemen']);
        $this->assertSame('Kayu',  KalkulasiEngine::hitung('2024-05-01', 0)['elemen']);
        $this->assertSame('Api',   KalkulasiEngine::hitung('2026-05-01', 0)['elemen']);
        $this->assertSame('Tanah', KalkulasiEngine::hitung('2028-05-01', 0)['elemen']);
    }

    public function test_radar_deterministik_untuk_input_yang_sama(): void
    {
        $a = KalkulasiEngine::hitung('1995-03-10', 14);
        $b = KalkulasiEngine::hitung('1995-03-10', 14);

        $this->assertSame($a['radar'], $b['radar']);
    }

    public function test_radar_skor_selalu_dalam_rentang_valid(): void
    {
        $hasil = KalkulasiEngine::hitung('1988-11-23', 21)['radar'];

        $this->assertGreaterThanOrEqual(60, $hasil['karir']);
        $this->assertLessThan(101, $hasil['karir']);

        $this->assertGreaterThanOrEqual(55, $hasil['asmara']);
        $this->assertLessThan(91, $hasil['asmara']);

        $this->assertGreaterThanOrEqual(52, $hasil['cuan']);
        $this->assertLessThan(90, $hasil['cuan']);

        $this->assertGreaterThanOrEqual(58, $hasil['kesehatan']);
        $this->assertLessThan(91, $hasil['kesehatan']);

        $this->assertGreaterThanOrEqual(50, $hasil['spiritual']);
        $this->assertLessThan(90, $hasil['spiritual']);
    }

    public function test_hitung_mengembalikan_semua_komponen(): void
    {
        $hasil = KalkulasiEngine::hitung('2000-02-29', 6); // tahun kabisat

        $this->assertArrayHasKey('weton', $hasil);
        $this->assertArrayHasKey('shio', $hasil);
        $this->assertArrayHasKey('zodiak', $hasil);
        $this->assertArrayHasKey('elemen', $hasil);
        $this->assertArrayHasKey('radar', $hasil);
    }
}
