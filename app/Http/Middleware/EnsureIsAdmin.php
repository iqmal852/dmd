<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the admin console. Requires `auth` to have already run.
 *
 * This application has exactly one user, so today this middleware only asserts
 * authentication. It exists as the seam for a role check if a second admin role
 * is ever introduced, so that day doesn't require touching every admin route.
 */
class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() !== null, 403);

        return $next($request);
    }
}
