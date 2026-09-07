<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every dossier page is reachable by anyone holding the link, but a survey
 * monument's coordinates should never turn up in a search result — see
 * plan/phases/phase-03-dossier-shell.md M3.1.
 */
class AddNoindexHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
