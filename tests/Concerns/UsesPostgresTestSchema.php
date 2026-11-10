<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Menjalankan tes pada skema PostgreSQL terpisah ("rumuslangit_test") di server DB yang sama,
 * sehingga data produksi (skema "public") TIDAK PERNAH disentuh. Migration project ini memakai
 * SQL khusus Postgres, jadi sqlite tidak bisa dipakai.
 *
 * Skema dibuat & dimigrasi otomatis (sekali per proses), lalu tiap tes dibungkus transaksi
 * yang di-rollback.
 */
trait UsesPostgresTestSchema
{
    private static bool $testSchemaReady = false;

    protected function setUpPostgresTestSchema(): void
    {
        // phpunit.xml meng-override DB_* (sqlite :memory:), jadi baca .env asli.
        $env    = \Dotenv\Dotenv::parse(file_get_contents(base_path('.env')));
        $schema = 'rumuslangit_test';

        config([
            'database.default'                     => 'pgsql',
            'database.connections.pgsql.host'      => $env['DB_HOST'],
            'database.connections.pgsql.port'      => $env['DB_PORT'],
            'database.connections.pgsql.database'  => $env['DB_DATABASE'],
            'database.connections.pgsql.username'  => $env['DB_USERNAME'],
            'database.connections.pgsql.password'  => $env['DB_PASSWORD'],
            'database.connections.pgsql.sslmode'   => $env['DB_SSLMODE'] ?? 'prefer',
            'database.connections.pgsql.search_path' => $schema,
        ]);
        DB::purge('pgsql');

        if (! self::$testSchemaReady) {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$schema}");
            Artisan::call('migrate', ['--force' => true, '--database' => 'pgsql']);
            self::$testSchemaReady = true;
        }

        // Pengaman: pastikan benar-benar bukan skema produksi.
        $aktif = DB::selectOne('select current_schema() as s')->s;
        if ($aktif !== $schema) {
            throw new \RuntimeException("Tes harus memakai skema {$schema}, bukan {$aktif}.");
        }

        DB::connection('pgsql')->beginTransaction();
    }

    protected function tearDownPostgresTestSchema(): void
    {
        DB::connection('pgsql')->rollBack();
    }
}
