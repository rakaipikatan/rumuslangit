<?php

namespace App\Console\Commands;

use App\Support\DatabaseBackup;
use Illuminate\Console\Command;

class UjiPulihDatabase extends Command
{
    protected $signature   = 'db:uji-pulih {berkas? : Berkas backup (default: yang terbaru)}';
    protected $description = 'Uji pulih aman: muat isi backup ke tabel sementara (dibatalkan) dan bandingkan dengan database hidup';

    public function handle(DatabaseBackup $backup): int
    {
        try {
            $hasil = $backup->ujiPulih($this->argument('berkas'));
        } catch (\Throwable $e) {
            $this->error('UJI PULIH GAGAL: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->table(
            ['Tabel', 'Baris di backup', 'Berhasil dimuat', 'Baris di DB hidup', 'Checksum cocok'],
            array_map(fn ($r) => [$r['tabel'], $r['baris_dump'], $r['dimuat'], $r['hidup'], $r['cocok']], $hasil)
        );

        $gagal  = array_filter($hasil, fn ($r) => !$r['berhasil'] || $r['cocok'] === 'BEDA');
        $berubah = array_filter($hasil, fn ($r) => $r['cocok'] === 'berubah');

        if ($gagal) {
            $this->error('Ada tabel yang tidak pulih dengan benar: ' . implode(', ', array_column($gagal, 'tabel')));
            return self::FAILURE;
        }

        $this->info(count($hasil) . ' tabel berhasil dipulihkan ke tabel sementara dan dibatalkan; database hidup tidak diubah.'
            . ($berubah ? ' (' . count($berubah) . ' tabel berubah sejak backup dibuat — wajar bila ada aktivitas.)' : ''));

        return self::SUCCESS;
    }
}
