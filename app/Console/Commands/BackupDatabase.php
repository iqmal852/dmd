<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * plan/phases/phase-09-hardening-release.md M9.4 — a daily `pg_dump`,
 * gzipped and pushed through the configured backup disk (see
 * config/backup.php). Pair with `batu:restore`, which this backup's own
 * test proves can read what this command writes. Schedule via
 * `schedule:run` (see docs/DEPLOYMENT.md); not scheduled here since a
 * disk needs to actually be off-site before this is a real backup, not
 * just a copy sitting next to what it protects against.
 */
class BackupDatabase extends Command
{
    protected $signature = 'batu:backup';

    protected $description = 'Dump the database, gzip it, and store it on the configured backup disk';

    public function handle(): int
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (! is_array($config) || ($config['driver'] ?? null) !== 'pgsql') {
            $this->error("batu:backup only supports the pgsql driver; connection [{$connection}] uses [".($config['driver'] ?? 'unknown').'].');

            return self::FAILURE;
        }

        $filename = 'backup-'.now()->format('Y-m-d-His').'.sql.gz';
        $disk = Storage::disk((string) config('backup.disk'));
        $storagePath = trim((string) config('backup.path'), '/').'/'.$filename;

        $this->info("Dumping [{$config['database']}] …");

        $dumpProcess = new Process([
            'pg_dump',
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--username='.$config['username'],
            '--format=plain',
            '--no-owner',
            '--no-privileges',
            $config['database'],
        ], env: ['PGPASSWORD' => $config['password']]);
        $dumpProcess->setTimeout(600);

        $gzipProcess = new Process(['gzip', '-c']);
        $gzipProcess->setTimeout(600);
        $gzipProcess->setInput($this->runAndGetOutput($dumpProcess, 'pg_dump'));

        $compressed = $this->runAndGetOutput($gzipProcess, 'gzip');

        $disk->put($storagePath, $compressed);

        $this->info("Backup stored at [{$storagePath}] on disk [".config('backup.disk').'].');

        $this->pruneOldBackups($disk);

        return self::SUCCESS;
    }

    private function runAndGetOutput(Process $process, string $label): string
    {
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException("{$label} failed: {$process->getErrorOutput()}");
        }

        return $process->getOutput();
    }

    private function pruneOldBackups(Filesystem $disk): void
    {
        $keepDays = (int) config('backup.keep_days');
        $cutoff = now()->subDays($keepDays)->getTimestamp();
        $path = trim((string) config('backup.path'), '/');

        foreach ($disk->files($path) as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
                $this->line("Pruned old backup [{$file}].");
            }
        }
    }
}
