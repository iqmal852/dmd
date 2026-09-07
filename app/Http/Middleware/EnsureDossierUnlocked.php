<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Station;
use App\Services\AccessGate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates every public dossier route. See plan/phases/phase-03-dossier-shell.md
 * M3.2 and plan/04-env-configuration.md §6 for the full behaviour matrix.
 */
class EnsureDossierUnlocked
{
    public function __construct(private readonly AccessGate $gate) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Station $station */
        $station = $request->route('station');

        if ($this->gate->isMisconfigured($station)) {
            Log::error('Dossier access mode is "password" but no password is resolvable.', [
                'station_public_id' => $station->public_id,
            ]);

            abort(503, 'This dossier is temporarily unavailable. Please contact the site operator.');
        }

        if (! $this->gate->isRequired($station)) {
            return $next($request);
        }

        if ($this->gate->isUnlocked($station)) {
            return $next($request);
        }

        return redirect()->route('dossier.unlock', [
            'station' => $station,
            'redirect' => $request->fullUrl(),
        ]);
    }
}
