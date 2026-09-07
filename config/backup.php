<?php

declare(strict_types=1);

/**
 * plan/phases/phase-09-hardening-release.md M9.4 — deliberately not a
 * package (spatie/laravel-backup et al. would need approval per
 * CLAUDE.md's dependency rule): a thin wrapper around `pg_dump`/`psql`
 * that writes through the existing Storage facade, so it works against
 * whichever disk is configured (local for dev, an off-site disk like S3
 * in production — see docs/DEPLOYMENT.md) without a new dependency.
 */
return [
    'disk' => env('BACKUP_DISK', 'local'),
    'path' => env('BACKUP_PATH', 'backups'),
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 30),
];
