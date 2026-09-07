<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pest's browser-testing plugin serves the app from a dynamic host/port
 * per test run and reuses the same booted application across every
 * simulated request in that run (see vendor/pestphp/pest-plugin-browser's
 * LaravelHttpServer, which rewrites the app's base URL at runtime).
 * config/filesystems.php's `public` disk URL is a plain string baked from
 * the APP_URL environment variable once when that config file is first
 * loaded, and Laravel's FilesystemManager then caches the resolved disk
 * adapter for the life of the application instance — so both the stale
 * URL and the cached disk survive that later override, and every
 * medialibrary-generated URL (thumbnails, previews, the panorama image)
 * silently points at the wrong host for the rest of the test run. This
 * updates the config *and* purges the cached disk so it re-resolves with
 * the correct URL, but only under the `testing` environment — dev and
 * production keep the static, correctly-configured value derived from
 * that environment variable, and never pay this extra purge.
 */
class SyncPublicDiskUrlForTesting
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing')) {
            config(['filesystems.disks.public.url' => rtrim($request->getSchemeAndHttpHost(), '/').'/storage']);
            Storage::purge('public');
        }

        return $next($request);
    }
}
