<?php

namespace Database\Seeders;

use App\Models\WilayahKota;
use App\Models\WilayahProvinsi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Seed data provinsi & kota Indonesia dari wilayah.id.
 * Kecamatan dan kelurahan di-load secara lazy oleh WilayahController.
 *
 * Jalankan: php artisan db:seed --class=WilayahSeeder
 * Estimasi: ~1-2 menit (34+514 API calls).
 */
class WilayahSeeder extends Seeder
{
    private const BASE = 'https://wilayah.id/api/';

    public function run(): void
    {
        $this->command->info('Mengunduh data wilayah Indonesia dari wilayah.id...');
        DB::statement('TRUNCATE wilayah_kelurahan, wilayah_kecamatan, wilayah_kota, wilayah_provinsi CASCADE');

        // ── 1. Provinsi ──────────────────────────────────────────────────────
        $this->command->info('  ▸ Provinsi...');
        $provinces = $this->fetch('provinces.json')['data'] ?? [];

        $provinsiMap = []; // kode → id
        foreach ($provinces as $p) {
            $row = WilayahProvinsi::create([
                'kode' => $p['code'],
                'nama' => $this->titleCase($p['name']),
            ]);
            $provinsiMap[$p['code']] = $row->id;
        }
        $this->command->info('    → ' . count($provinces) . ' provinsi disimpan.');

        // ── 2. Kota/Kabupaten per Provinsi ───────────────────────────────────
        $this->command->info('  ▸ Kota/Kabupaten (' . count($provinces) . ' provinsi)...');
        $totalKota = 0;

        foreach ($provinces as $p) {
            $regencies = $this->fetch("regencies/{$p['code']}.json")['data'] ?? [];
            $provinsiId = $provinsiMap[$p['code']] ?? null;
            if (!$provinsiId || !$regencies) continue;

            $rows = [];
            foreach ($regencies as $r) {
                $rawName = $r['name'];
                $tipe    = str_starts_with(strtoupper($rawName), 'KOTA') ? 'Kota' : 'Kabupaten';
                $nama    = preg_replace('/^(Kabupaten|Kota)\s+/i', '', $this->titleCase($rawName));
                $rows[]  = ['kode' => $r['code'], 'provinsi_id' => $provinsiId, 'nama' => $nama, 'tipe' => $tipe];
            }
            if ($rows) {
                DB::table('wilayah_kota')->insert($rows);
                $totalKota += count($rows);
            }
        }
        $this->command->info("    → {$totalKota} kota/kabupaten disimpan.");
        $this->command->info('✓ Seed provinsi & kota selesai!');
        $this->command->info('  Kecamatan & kelurahan akan di-load otomatis saat pertama kali dipilih user.');
    }

    private function fetch(string $path): array
    {
        $url      = self::BASE . $path;
        $response = Http::timeout(30)->get($url);
        if (!$response->successful()) return [];
        return $response->json() ?? [];
    }

    private function titleCase(string $str): string
    {
        return mb_convert_case(mb_strtolower($str), MB_CASE_TITLE, 'UTF-8');
    }
}
