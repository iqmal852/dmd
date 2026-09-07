<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DocumentDownloaded;
use App\Models\DownloadLog;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Writes the audit row asynchronously so a download is never delayed by a
 * database write. Never stores the raw IP — only an HMAC of it, keyed on
 * the app key so it isn't reversible without the app's own secret. See
 * plan/phases/phase-07-asbuilt-files.md M7.5.
 */
final class LogDocumentDownload implements ShouldQueue
{
    public function handle(DocumentDownloaded $event): void
    {
        if (! config('dossier.downloads.log_enabled')) {
            return;
        }

        DownloadLog::query()->create([
            'station_id' => $event->station->id,
            'media_id' => $event->media->id,
            'ip_hash' => hash_hmac('sha256', $event->ip, (string) config('app.key')),
            'user_agent' => mb_substr((string) $event->userAgent, 0, 255),
            'downloaded_at' => now(),
        ]);
    }
}
