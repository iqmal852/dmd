<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * plan/phases/phase-09-hardening-release.md M9.4 — the other half of
 * `batu:backup`. Restores into a named database connection rather than
 * always the app's default one, so the documented drill (M9.4, "an
 * untested backup is not a backup") targets a scratch database and never
 * the live one by accident. Restoring into the default connection still
 * works — it is just gated behind `--force`, the same pattern Laravel
 * itself uses for `migrate --force` in production.
 */
class RestoreDatabase extends Command
{
    protected $signature = 'batu:restore
        {backup : Filename of the backup on the configured backup disk (e.g. backup-2026-09-08-030000.sql.gz)}
        {--connection= : Database connection to restore into; defaults to the app default}
        {--force : Required to restore into the app default connection}';

    protected $description = 'Restore a gzipped pg_dump backup into a database connection';

    public function handle(): int
    {
        $connectionName = $this->option('connection') ?: config('database.default');
        $config = config("database.connections.{$connectionName}");

        if (! is_array($config) || ($config['driver'] ?? null) !== 'pgsql') {
            $this->error("batu:restore only supports the pgsql driver; connection [{$connectionName}] uses [".($config['driver'] ?? 'unknown').'].');

            return self::FAILURE;
        }

        if ($connectionName === config('database.default') && ! $this->option('force')) {
            $this->error("Restoring into the default connection [{$connectionName}] requires --force. Restore into a scratch connection with --connection= to verify a backup safely.");

            return self::FAILURE;
        }

        $disk = Storage::disk((string) config('backup.disk'));
        $backupFile = (string) $this->argument('backup');
        $storagePath = trim((string) config('backup.path'), '/').'/'.$backupFile;

        if (! $disk->exists($storagePath)) {
            $this->error("Backup [{$storagePath}] not found on disk [".config('backup.disk').'].');

            return self::FAILURE;
        }

        $this->info("Restoring [{$storagePath}] into [{$config['database']}] on connection [{$connectionName}] …");

        $compressed = $disk->get($storagePath);

        $gunzipProcess = new Process(['gunzip', '-c']);
        $gunzipProcess->setTimeout(600);
        $gunzipProcess->setInput($compressed);
        $gunzipProcess->run();

        if (! $gunzipProcess->isSuccessful()) {
            throw new RuntimeException("gunzip failed: {$gunzipProcess->getErrorOutput()}");
        }

        $psqlProcess = new Process([
            'psql',
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--username='.$config['username'],
            '--dbname='.$config['database'],
            '--set=ON_ERROR_STOP=on',
            '--quiet',
        ], env: ['PGPASSWORD' => $config['password']]);
        $psqlProcess->setTimeout(600);
        $psqlProcess->setInput($gunzipProcess->getOutput());
        $psqlProcess->run();

        if (! $psqlProcess->isSuccessful()) {
            $this->error("psql failed: {$psqlProcess->getErrorOutput()}");

            return self::FAILURE;
        }

        $this->info('Restore complete.');

        return self::SUCCESS;
    }
}
