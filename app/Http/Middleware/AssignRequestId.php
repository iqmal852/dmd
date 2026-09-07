<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * plan/phases/phase-09-hardening-release.md M9.4 — every log line written
 * during a request carries that request's ID, so a single failure can be
 * traced across every log entry it produced. `Context` is merged into every
 * channel automatically (Laravel pushes a ContextLogProcessor onto each
 * one), so nothing downstream needs to read this explicitly.
 *
 * Reuses an inbound `X-Request-Id` when the edge/proxy already assigned
 * one, so a trace stays consistent end to end instead of being replaced at
 * every hop.
 */
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id') ?: (string) Str::uuid();

        Context::add('request_id', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
