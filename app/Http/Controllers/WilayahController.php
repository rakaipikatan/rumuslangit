<?php

namespace App\Http\Controllers;

use App\Models\WilayahKecamatan;
use App\Models\WilayahKelurahan;
use App\Models\WilayahKota;
use App\Models\WilayahProvinsi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WilayahController extends Controller
{
    private const WILAYAH_BASE = 'https://wilayah.id/api/';

    public function provinsi(): JsonResponse
    {
        $data = WilayahProvinsi::orderBy('nama')->get(['id', 'nama']);
        return response()->json($data);
    }

    public function kota(Request $request): JsonResponse
    {
        $provinsiId = $request->integer('provinsi_id');
        $data = WilayahKota::where('provinsi_id', $provinsiId)
            ->orderBy('nama')
            ->get(['id', 'nama', 'tipe']);
        return response()->json($data);
    }

    public function kecamatan(Request $request): JsonResponse
    {
        $kotaId = $request->integer('kota_id');
        if (!$kotaId) return response()->json([]);

        // Lazy-load dari wilayah.id jika belum ada di DB
        if (WilayahKecamatan::where('kota_id', $kotaId)->doesntExist()) {
            $kota = WilayahKota::find($kotaId);
            if ($kota) {
                $this->seedKecamatan($kota->kode, $kotaId);
            }
        }

        $data = WilayahKecamatan::where('kota_id', $kotaId)
            ->orderBy('nama')
            ->get(['id', 'nama']);
        return response()->json($data);
    }

    public function kelurahan(Request $request): JsonResponse
    {
        $kecamatanId = $request->integer('kecamatan_id');
        if (!$kecamatanId) return response()->json([]);

        // Lazy-load dari wilayah.id jika belum ada di DB
        if (WilayahKelurahan::where('kecamatan_id', $kecamatanId)->doesntExist()) {
            $kec = WilayahKecamatan::find($kecamatanId);
            if ($kec) {
                $this->seedKelurahan($kec->kode, $kecamatanId);
            }
        }

        $data = WilayahKelurahan::where('kecamatan_id', $kecamatanId)
            ->orderBy('nama')
            ->get(['id', 'nama']);
        return response()->json($data);
    }

    private function seedKecamatan(string $kotaKode, int $kotaId): void
    {
        try {
            $res  = Http::timeout(15)->get(self::WILAYAH_BASE . "districts/{$kotaKode}.json");
            $list = $res->json()['data'] ?? [];
            $rows = [];
            foreach ($list as $d) {
                $rows[] = ['kode' => $d['code'], 'kota_id' => $kotaId, 'nama' => $this->titleCase($d['name'])];
            }
            if ($rows) {
                WilayahKecamatan::upsert($rows, ['kode'], ['kota_id', 'nama']);
            }
        } catch (\Throwable) {
            // Gagal fetch — kembalikan kosong, user bisa coba lagi
        }
    }

    private function seedKelurahan(string $kecKode, int $kecamatanId): void
    {
        try {
            $res  = Http::timeout(15)->get(self::WILAYAH_BASE . "villages/{$kecKode}.json");
            $list = $res->json()['data'] ?? [];
            $rows = [];
            foreach ($list as $v) {
                $rows[] = ['kode' => $v['code'], 'kecamatan_id' => $kecamatanId, 'nama' => $this->titleCase($v['name'])];
            }
            if ($rows) {
                WilayahKelurahan::upsert($rows, ['kode'], ['kecamatan_id', 'nama']);
            }
        } catch (\Throwable) {
            // Gagal fetch — kembalikan kosong
        }
    }

    private function titleCase(string $str): string
    {
        return mb_convert_case(mb_strtolower($str), MB_CASE_TITLE, 'UTF-8');
    }
}
