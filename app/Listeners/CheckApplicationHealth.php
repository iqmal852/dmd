<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * plan/phases/phase-09-hardening-release.md M9.4 — Laravel's `/up` route
 * only proves the framework booted; it dispatches DiagnosingHealth so
 * listeners can add real checks. Throwing here fails the request with a
 * 500 (see ApplicationBuilder::buildRoutingCallback), which is exactly
 * what an uptime monitor should page on.
 */
class CheckApplicationHealth
{
    public function handle(DiagnosingHealth $event): void
    {
        $this->checkDatabase();
        $this->checkStorageDisk();
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            throw new RuntimeException("Database connection failed: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * The `local` disk is where every station's photos, panoramas, and
     * as-built documents live (Station::registerMediaCollections) — a
     * write/read/delete round trip confirms it is actually mounted and
     * writable, not just configured.
     */
    private function checkStorageDisk(): void
    {
        $disk = Storage::disk('local');
        $probeFile = 'health-check-'.Str::random(12).'.txt';

        try {
            $disk->put($probeFile, 'ok');

            if ($disk->get($probeFile) !== 'ok') {
                throw new RuntimeException('Storage disk [local] round-trip returned unexpected content.');
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RuntimeException("Storage disk [local] is not writable: {$e->getMessage()}", previous: $e);
        } finally {
            $disk->delete($probeFile);
        }
    }
}
