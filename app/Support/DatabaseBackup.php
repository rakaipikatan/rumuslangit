<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Backup database PostgreSQL (format kustom pg_dump, terkompresi) + verifikasi isi + rotasi.
 * Kata sandi database dilewatkan lewat environment proses (tidak pernah ditulis ke layar/log).
 */
class DatabaseBackup
{
    /** @return array{file: string, ukuran: int, tabel: int} */
    public function jalankan(): array
    {
        $dir = rtrim((string) config('backup.direktori'), '/');
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException("Tidak bisa membuat folder backup: {$dir}");
        }
        @chmod($dir, 0700);

        $c = config('database.connections.pgsql');
        $nama = 'rumuslangit-' . Carbon::now(HariWib::ZONA)->format('Ymd-His') . '.dump';
        $akhir = "{$dir}/{$nama}";
        $sementara = "{$akhir}.tmp";

        $hasil = Process::timeout(600)
            ->env(['PGPASSWORD' => (string) $c['password'], 'PGSSLMODE' => (string) ($c['sslmode'] ?? 'prefer')])
            ->run([
                'pg_dump', '-Fc', '--no-owner', '--no-privileges',
                '-n', (string) config('backup.skema'),
                '-h', (string) $c['host'], '-p', (string) $c['port'],
                '-U', (string) $c['username'], '-d', (string) $c['database'],
                '-f', $sementara,
            ]);

        if (!$hasil->successful()) {
            @unlink($sementara);
            throw new RuntimeException('pg_dump gagal: ' . trim(mb_substr($hasil->errorOutput() ?: $hasil->output(), 0, 300)));
        }

        if (!is_file($sementara) || filesize($sementara) < 1024) {
            @unlink($sementara);
            throw new RuntimeException('Berkas backup kosong atau terlalu kecil.');
        }

        $jumlahTabel = $this->verifikasi($sementara);

        chmod($sementara, 0600);
        rename($sementara, $akhir);

        return ['file' => $akhir, 'ukuran' => filesize($akhir), 'tabel' => $jumlahTabel];
    }

    /** Membaca daftar isi dump (tanpa menyentuh database) dan memastikan tabel inti ada. Mengembalikan jumlah tabel berisi data. */
    public function verifikasi(string $berkas): int
    {
        $daftar = Process::timeout(120)->run(['pg_restore', '--list', $berkas]);
        if (!$daftar->successful()) {
            @unlink($berkas);
            throw new RuntimeException('Backup tidak dapat dibaca (pg_restore --list gagal): ' . trim(mb_substr($daftar->errorOutput(), 0, 200)));
        }

        $isi = $daftar->output();
        $kurang = [];
        foreach ((array) config('backup.tabel_wajib') as $t) {
            if (!preg_match('/TABLE DATA\s+\S+\s+' . preg_quote($t, '/') . '\b/', $isi)) {
                $kurang[] = $t;
            }
        }
        if ($kurang) {
            @unlink($berkas);
            throw new RuntimeException('Backup tidak lengkap, tabel hilang: ' . implode(', ', $kurang));
        }

        return preg_match_all('/TABLE DATA/', $isi);
    }

    /**
     * Berkas yang harus DIHAPUS menurut rotasi: simpan semua backup dalam N hari terakhir,
     * lalu satu backup terbaru per bulan untuk M bulan terakhir; sisanya dibuang.
     *
     * @param  string[] $namaBerkas nama berkas bergaya rumuslangit-YYYYMMDD-HHMMSS.dump
     * @return string[]
     */
    public static function yangDihapus(array $namaBerkas, Carbon $sekarang, int $harian, int $bulanan): array
    {
        $info = [];
        foreach ($namaBerkas as $n) {
            if (preg_match('/^rumuslangit-(\d{8})-(\d{6})\.dump$/', $n, $m)) {
                $info[$n] = Carbon::createFromFormat('Ymd', $m[1], HariWib::ZONA)->startOfDay();
            }
        }
        arsort($info); // terbaru dulu

        $batasHarian = $sekarang->copy()->subDays($harian)->startOfDay();
        $batasBulanan = $sekarang->copy()->startOfMonth()->subMonths($bulanan);
        $bulanSudahAda = [];
        $hapus = [];

        foreach ($info as $nama => $tgl) {
            if ($tgl->gte($batasHarian)) {
                $bulanSudahAda[$tgl->format('Y-m')] = true; // backup harian terbaru di bulan itu sudah mewakili
                continue;
            }
            $bulan = $tgl->format('Y-m');
            if ($tgl->gte($batasBulanan) && !isset($bulanSudahAda[$bulan])) {
                $bulanSudahAda[$bulan] = true; // simpan yang terbaru pada bulan tsb
                continue;
            }
            $hapus[] = $nama;
        }

        return $hapus;
    }

    /** Berkas backup terbaru di folder backup (atau null). */
    public function terbaru(): ?string
    {
        $dir = rtrim((string) config('backup.direktori'), '/');
        $berkas = glob("{$dir}/rumuslangit-*.dump") ?: [];
        sort($berkas);

        return $berkas ? end($berkas) : null;
    }

    /**
     * Ambil kolom dan baris dari keluaran `pg_restore --data-only -t <tabel> -f -`.
     * @return array{0: string, 1: string[]} [daftar kolom "a, b, c", baris-baris COPY teks]
     */
    public static function ekstrakCopy(string $sql): array
    {
        if (!preg_match('/^COPY\s+\S+\s+\(([^)]*)\)\s+FROM stdin;\R/m', $sql, $m, PREG_OFFSET_CAPTURE)) {
            return ['', []];
        }

        $kolom = $m[1][0];
        $isi = substr($sql, $m[0][1] + strlen($m[0][0]));
        $baris = [];
        foreach (preg_split('/\R/', $isi) as $b) {
            if ($b === '\\.') break;
            $baris[] = $b;
        }

        return [$kolom, $baris];
    }

    /**
     * Muat baris COPY ke TABEL SEMENTARA seukuran tabel asli, lalu bandingkan dengan tabel hidup.
     * Harus dipanggil di dalam transaksi (tabel sementara dibuang bersama rollback).
     */
    public function muatDanBandingkan(string $tabel, string $kolom, array $baris, string $skema): array
    {
        $sementara = 'restore_' . $tabel;
        DB::statement("CREATE TEMP TABLE \"{$sementara}\" (LIKE \"{$skema}\".\"{$tabel}\" INCLUDING DEFAULTS)");

        if ($baris) {
            DB::connection()->getPdo()->pgsqlCopyFromArray($sementara, $baris, "\t", '\\\\N', $kolom); // E'\\\\N' di server = penanda NULL \\N
        }

        $ringkas = fn (string $dari) => DB::selectOne("select count(*) as n, coalesce(sum(hashtext(t::text)), 0) as cs from {$dari} t");
        $dimuat = $ringkas("\"{$sementara}\"");
        $hidup  = $ringkas("\"{$skema}\".\"{$tabel}\"");

        return [
            'tabel'     => $tabel,
            'baris_dump' => count($baris),
            'dimuat'    => (int) $dimuat->n,
            'hidup'     => (int) $hidup->n,
            // checksum hanya bermakna bila jumlah baris sama (data bisa berubah sejak backup dibuat)
            'cocok'     => (int) $dimuat->n === (int) $hidup->n ? ((string) $dimuat->cs === (string) $hidup->cs ? 'ya' : 'BEDA') : 'berubah',
            'berhasil'  => (int) $dimuat->n === count($baris),
        ];
    }

    /**
     * Uji pulih tanpa risiko: seluruh tabel dalam backup dimuat ke tabel sementara di dalam transaksi
     * yang SELALU dibatalkan; tabel hidup hanya dibaca.
     * @return array<int, array>
     */
    public function ujiPulih(?string $berkas = null): array
    {
        $berkas ??= $this->terbaru();
        if (!$berkas || !is_file($berkas)) {
            throw new RuntimeException('Belum ada berkas backup untuk diuji.');
        }

        $daftar = Process::timeout(120)->run(['pg_restore', '--list', $berkas]);
        if (!$daftar->successful()) {
            throw new RuntimeException('Backup tidak dapat dibaca: ' . trim(mb_substr($daftar->errorOutput(), 0, 200)));
        }

        preg_match_all('/TABLE DATA\s+(\S+)\s+(\S+)\s/', $daftar->output(), $m, PREG_SET_ORDER);
        $skema = (string) config('backup.skema');
        $hasil = [];

        DB::beginTransaction();
        try {
            foreach ($m as $baris) {
                $tabel = $baris[2];
                $keluaran = Process::timeout(120)->run(['pg_restore', '--data-only', '-t', $tabel, '-f', '-', $berkas]);
                if (!$keluaran->successful()) {
                    throw new RuntimeException("Gagal membaca tabel {$tabel} dari backup.");
                }
                [$kolom, $isi] = self::ekstrakCopy($keluaran->output());
                $hasil[] = $this->muatDanBandingkan($tabel, $kolom, $isi, $skema);
            }
        } finally {
            DB::rollBack(); // tidak ada yang pernah disimpan
        }

        return $hasil;
    }

    public function pangkas(): int
    {
        $dir = rtrim((string) config('backup.direktori'), '/');
        $berkas = array_map('basename', glob("{$dir}/rumuslangit-*.dump") ?: []);
        $hapus = self::yangDihapus($berkas, Carbon::now(HariWib::ZONA), (int) config('backup.harian_simpan'), (int) config('backup.bulanan_simpan'));

        foreach ($hapus as $n) {
            @unlink("{$dir}/{$n}");
        }

        return count($hapus);
    }
}
