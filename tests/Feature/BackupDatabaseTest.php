<?php

namespace Tests\Feature;

use App\Models\ServiceEvent;
use App\Support\DatabaseBackup;
use App\Support\LayananStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Tests\Concerns\UsesPostgresTestSchema;
use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    use UsesPostgresTestSchema;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPostgresTestSchema();
        Cache::flush();
        $this->dir = storage_path('framework/testing/backup-' . uniqid());
        config(['backup.direktori' => $this->dir, 'backup.skema' => 'rumuslangit_test']);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) @unlink($f);
        @rmdir($this->dir);
        $this->tearDownPostgresTestSchema();
        parent::tearDown();
    }

    private function tocLengkap(): string
    {
        $baris = '';
        foreach (config('backup.tabel_wajib') as $i => $t) {
            $baris .= (200 + $i) . "; 0 " . (16000 + $i) . " TABLE DATA public {$t} dbuser\n";
        }

        return $baris;
    }

    /** Palsukan pg_dump (membuat berkas di -f) dan pg_restore --list. */
    private function palsuProses(bool $dumpBerhasil = true, ?string $toc = null): void
    {
        Process::fake(function ($proses) use ($dumpBerhasil, $toc) {
            $cmd = (array) $proses->command;
            if ($cmd[0] === 'pg_dump') {
                if (!$dumpBerhasil) return Process::result('', 'pg_dump: error: connection refused', 1);
                file_put_contents($cmd[array_search('-f', $cmd) + 1], str_repeat('x', 4096));
                return Process::result('');
            }
            if ($cmd[0] === 'pg_restore') return Process::result($toc ?? $this->tocLengkap());

            return Process::result('');
        });
    }

    public function test_backup_berhasil_menulis_berkas_aman_dan_mencatat_status_ok(): void
    {
        $this->palsuProses();

        $this->artisan('db:backup')->assertSuccessful();

        $berkas = glob($this->dir . '/rumuslangit-*.dump');
        $this->assertCount(1, $berkas);
        $this->assertSame('0600', substr(sprintf('%o', fileperms($berkas[0])), -4));
        $this->assertSame('0700', substr(sprintf('%o', fileperms($this->dir)), -4));
        $this->assertEmpty(glob($this->dir . '/*.tmp'), 'tidak boleh ada berkas sementara tersisa');
        $this->assertNotNull(LayananStatus::ringkas('backup')['terakhir_ok']);
    }

    public function test_kata_sandi_dilewatkan_lewat_environment_bukan_argumen(): void
    {
        $this->palsuProses();
        config(['database.connections.pgsql.password' => 'RAHASIA-UJI-123']);

        $this->artisan('db:backup')->assertSuccessful();

        Process::assertRan(function ($p) {
            $cmd = implode(' ', (array) $p->command);

            return str_starts_with($cmd, 'pg_dump') && !str_contains($cmd, 'RAHASIA-UJI-123')
                && ($p->environment['PGPASSWORD'] ?? null) === 'RAHASIA-UJI-123'
                && in_array('-Fc', (array) $p->command) && in_array(config('backup.skema'), (array) $p->command);
        });
    }

    public function test_backup_gagal_dilaporkan_tanpa_meninggalkan_berkas(): void
    {
        $this->palsuProses(dumpBerhasil: false);

        $this->artisan('db:backup')->assertFailed();

        $this->assertEmpty(glob($this->dir . '/*'));
        $r = LayananStatus::ringkas('backup');
        $this->assertTrue($r['perlu_cek']);
        $this->assertStringContainsString('connection refused', $r['gagal_terakhir']->pesan);
    }

    public function test_backup_yang_tidak_memuat_tabel_inti_ditolak_dan_dihapus(): void
    {
        $this->palsuProses(toc: "200; 0 16000 TABLE DATA public users dbuser\n"); // tabel lain hilang

        $this->artisan('db:backup')->assertFailed();

        $this->assertEmpty(glob($this->dir . '/*'), 'backup tidak lengkap tidak boleh disimpan');
        $this->assertStringContainsString('tabel hilang', ServiceEvent::where('layanan', 'backup')->latest('id')->value('pesan'));
    }

    public function test_status_backup_ditandai_macet_bila_lebih_dari_26_jam(): void
    {
        $this->travelTo(Carbon::parse('2026-11-11 03:00:00', 'UTC'));
        LayananStatus::ok('backup', 'uji');
        $this->assertFalse(LayananStatus::ringkas('backup')['perlu_cek']);

        $this->travelTo(Carbon::parse('2026-11-12 04:00:00', 'UTC')); // 25 jam
        $this->assertFalse(LayananStatus::ringkas('backup')['perlu_cek']);

        $this->travelTo(Carbon::parse('2026-11-12 06:00:00', 'UTC')); // 27 jam
        $r = LayananStatus::ringkas('backup');
        $this->assertTrue($r['perlu_cek']);
        $this->assertStringContainsString('macet', $r['catatan']);
    }

    public function test_rotasi_simpan_harian_lalu_satu_per_bulan_dan_buang_sisanya(): void
    {
        $sekarang = Carbon::parse('2026-11-11 10:00:00', 'Asia/Jakarta');
        $berkas = [];
        foreach (['20261111', '20261110', '20261028', '20261027', '20261020', '20261005', '20260930', '20260915', '20260520', '20260430'] as $t) {
            $berkas[] = "rumuslangit-{$t}-020000.dump";
        }
        $berkas[] = 'catatan-penting.txt'; // bukan berkas backup → tidak disentuh

        $hapus = DatabaseBackup::yangDihapus($berkas, $sekarang, 14, 6);

        $this->assertEqualsCanonicalizing([
            'rumuslangit-20261027-020000.dump', 'rumuslangit-20261020-020000.dump', 'rumuslangit-20261005-020000.dump',
            'rumuslangit-20260915-020000.dump', 'rumuslangit-20260430-020000.dump',
        ], $hapus);
        $this->assertNotContains('catatan-penting.txt', $hapus);
    }

    public function test_ekstrak_copy_membaca_kolom_dan_baris(): void
    {
        $sql = "--\n-- Data\n--\n\nCOPY public.service_events (id, layanan, ok, pesan, created_at) FROM stdin;\n"
            . "1\temail\tf\tboom\t2026-11-11 00:00:00\n2\tgemini\tt\t\\N\t2026-11-11 00:01:00\n\\.\n\n";

        [$kolom, $baris] = DatabaseBackup::ekstrakCopy($sql);

        $this->assertSame('id, layanan, ok, pesan, created_at', $kolom);
        $this->assertCount(2, $baris);
        $this->assertSame("2\tgemini\tt\t\\N\t2026-11-11 00:01:00", $baris[1]);
        $this->assertSame(['', []], DatabaseBackup::ekstrakCopy('-- tidak ada data'));
    }

    public function test_muat_dan_bandingkan_memulihkan_ke_tabel_sementara_tanpa_mengubah_tabel_asli(): void
    {
        ServiceEvent::create(['layanan' => 'email', 'ok' => false, 'pesan' => 'boom', 'created_at' => '2026-11-11 00:00:00']);
        ServiceEvent::create(['layanan' => 'gemini', 'ok' => true, 'pesan' => null, 'created_at' => '2026-11-11 00:01:00']);
        $ids = ServiceEvent::orderBy('id')->pluck('id');

        $baris = ["{$ids[0]}\temail\tf\tboom\t2026-11-11 00:00:00", "{$ids[1]}\tgemini\tt\t\\N\t2026-11-11 00:01:00"];

        $hasil = (new DatabaseBackup())->muatDanBandingkan('service_events', 'id, layanan, ok, pesan, created_at', $baris, 'rumuslangit_test');

        $this->assertTrue($hasil['berhasil']);
        $this->assertSame(2, $hasil['dimuat']);
        $this->assertSame(2, $hasil['hidup']);
        $this->assertSame('ya', $hasil['cocok'], 'checksum isi sama persis dengan tabel hidup (termasuk NULL)');
        $this->assertSame(2, ServiceEvent::count(), 'tabel asli tidak berubah');

        // isi berbeda terdeteksi
        DB::statement('DROP TABLE IF EXISTS restore_service_events');
        $beda = (new DatabaseBackup())->muatDanBandingkan('service_events', 'id, layanan, ok, pesan, created_at',
            ["{$ids[0]}\temail\tf\tDIUBAH\t2026-11-11 00:00:00", "{$ids[1]}\tgemini\tt\t\\N\t2026-11-11 00:01:00"], 'rumuslangit_test');
        $this->assertSame('BEDA', $beda['cocok']);
    }

    public function test_terbaru_memilih_berkas_dengan_tanggal_terbesar(): void
    {
        @mkdir($this->dir, 0700, true);
        foreach (['20261101-020000', '20261111-020000', '20261105-020000'] as $t) touch("{$this->dir}/rumuslangit-{$t}.dump");

        $this->assertSame("{$this->dir}/rumuslangit-20261111-020000.dump", (new DatabaseBackup())->terbaru());
    }

    public function test_backup_terjadwal_harian_0200_wib(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('db:backup')->assertSuccessful();
    }
}
