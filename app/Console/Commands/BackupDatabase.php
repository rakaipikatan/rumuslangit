<?php

namespace App\Console\Commands;

use App\Support\DatabaseBackup;
use App\Support\LayananStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    protected $signature   = 'db:backup';
    protected $description = 'Backup database (pg_dump) ke folder backup, verifikasi isinya, dan rotasi salinan lama';

    public function handle(DatabaseBackup $backup): int
    {
        try {
            $r = $backup->jalankan();
            $dihapus = $backup->pangkas();
        } catch (\Throwable $e) {
            Log::error('Backup database gagal', ['error' => $e->getMessage()]);
            LayananStatus::gagal('backup', $e->getMessage());
            $this->error('BACKUP GAGAL: ' . $e->getMessage());
            return self::FAILURE;
        }

        $kb = number_format($r['ukuran'] / 1024, 0, ',', '.');
        LayananStatus::ok('backup', "{$kb} KB, {$r['tabel']} tabel");
        Log::info('Backup database selesai', ['file' => basename($r['file']), 'kb' => $kb, 'tabel' => $r['tabel'], 'dihapus' => $dihapus]);

        $this->info("Backup selesai: {$r['file']} ({$kb} KB, {$r['tabel']} tabel berisi data). Salinan lama dibuang: {$dihapus}.");
        return self::SUCCESS;
    }
}
