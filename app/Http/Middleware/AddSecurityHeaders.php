<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied globally to every response. See
 * plan/phases/phase-09-hardening-release.md M9.1.
 *
 * `img-src` allows the two map tile hosts (Esri, OpenStreetMap — see
 * config/dossier.php's map.* defaults) plus `data:`/`blob:` for the
 * QR/panorama/lightbox previews built from in-memory image data.
 * `style-src` needs `unsafe-inline` for Tailwind's arbitrary-value
 * utilities and the few inline `<style>` blocks (the print plate page).
 * `geolocation=(self)` is kept deliberately, not tightened to `()` — a
 * later "where am I relative to this station" feature needs it, and the
 * map screen may use it now.
 */
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skipped only in `local`: Vite's dev-server HMR client is loaded
        // from a separate origin (e.g. http://localhost:5173), which a
        // same-origin `script-src` would block. Every built-asset
        // environment — `testing` (where the browser test suite actually
        // exercises this) and `production` — gets the real policy.
        if (! app()->environment('local')) {
            $csp = implode('; ', [
                "default-src 'self'",
                "img-src 'self' data: blob: https://server.arcgisonline.com https://*.tile.openstreetmap.org",
                "script-src 'self'",
                "style-src 'self' 'unsafe-inline'",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
            ]);

            $response->headers->set('Content-Security-Policy', $csp);
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(), microphone=()');
        $response->headers->set('X-Frame-Options', 'DENY');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
