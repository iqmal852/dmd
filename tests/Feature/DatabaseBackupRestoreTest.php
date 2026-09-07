<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;

/**
 * plan/phases/phase-09-hardening-release.md M9.4 — "an untested backup is
 * not a backup": this proves `batu:backup` and `batu:restore` round-trip
 * real data through real `pg_dump`/`psql` binaries, against a genuine
 * scratch database created and dropped for the test.
 *
 * Test rows are inserted through a *separate* connection
 * (`pgsql_autocommit`) rather than the model factory. RefreshDatabase
 * wraps the default connection's test body in an uncommitted transaction
 * — invisible to `pg_dump`, which opens its own PostgreSQL session and can
 * only see committed data. A second connection to the same database
 * commits immediately and is unaffected by that wrapping transaction.
 */
class DatabaseBackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    private const string SCRATCH_DATABASE = 'batu_test_restore_scratch';

    protected function setUp(): void
    {
        parent::setUp();

        $config = config('database.connections.pgsql');
        config(['database.connections.pgsql_autocommit' => $config]);
    }

    protected function tearDown(): void
    {
        DB::connection('pgsql_autocommit')->table('stations')->where('code', 'like', 'BACKUP-TEST-%')->delete();
        DB::purge('pgsql_autocommit');

        $this->dropScratchDatabase();

        parent::tearDown();
    }

    public function test_a_backup_restores_into_a_scratch_database_with_matching_data(): void
    {
        Storage::fake('local');
        config(['backup.disk' => 'local', 'backup.path' => 'backups']);

        $this->insertCommittedStations(3);
        $expectedCount = DB::connection('pgsql_autocommit')->table('stations')->count();

        Artisan::call('batu:backup');

        $files = Storage::disk('local')->files('backups');
        $this->assertCount(1, $files);
        $backupFilename = basename($files[0]);

        $this->createScratchDatabase();
        $this->registerScratchConnection();

        Artisan::call('batu:restore', [
            'backup' => $backupFilename,
            '--connection' => 'restore_scratch',
        ]);

        $restoredCount = DB::connection('restore_scratch')->table('stations')->count();
        $this->assertSame($expectedCount, $restoredCount);

        DB::purge('restore_scratch');
    }

    public function test_restoring_into_the_default_connection_without_force_is_refused(): void
    {
        Storage::fake('local');
        config(['backup.disk' => 'local', 'backup.path' => 'backups']);

        Artisan::call('batu:backup');
        $files = Storage::disk('local')->files('backups');
        $backupFilename = basename($files[0]);

        $exitCode = Artisan::call('batu:restore', ['backup' => $backupFilename]);

        $this->assertNotSame(0, $exitCode);
    }

    private function insertCommittedStations(int $count): void
    {
        $connection = DB::connection('pgsql_autocommit');

        for ($i = 0; $i < $count; $i++) {
            $connection->table('stations')->insert([
                'public_id' => (string) Str::ulid(),
                'code' => 'BACKUP-TEST-'.$i.'-'.Str::random(6),
                'highway' => 'LPT2',
                'km' => 300 + $i,
                'direction' => 'northbound',
                'monument_type' => 'Concrete Block',
                'status' => 'active',
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function createScratchDatabase(): void
    {
        $this->dropScratchDatabase();

        $pdo = $this->maintenancePdo();
        $pdo->exec('CREATE DATABASE '.self::SCRATCH_DATABASE);
    }

    private function dropScratchDatabase(): void
    {
        $pdo = $this->maintenancePdo();
        $pdo->exec('DROP DATABASE IF EXISTS '.self::SCRATCH_DATABASE.' WITH (FORCE)');
    }

    private function maintenancePdo(): PDO
    {
        $config = config('database.connections.pgsql');

        return new PDO(
            "pgsql:host={$config['host']};port={$config['port']};dbname=postgres",
            $config['username'],
            $config['password'],
        );
    }

    private function registerScratchConnection(): void
    {
        $config = config('database.connections.pgsql');
        $config['database'] = self::SCRATCH_DATABASE;

        config(['database.connections.restore_scratch' => $config]);
    }
}
