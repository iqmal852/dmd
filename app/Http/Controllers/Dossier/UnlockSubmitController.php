<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dossier\UnlockRequest;
use App\Models\Station;
use App\Services\AccessGate;
use Illuminate\Http\RedirectResponse;

/**
 * See plan/phases/phase-03-dossier-shell.md M3.3. Never distinguishes
 * "wrong password" from "this station uses a different password" —
 * always the same generic error.
 */
class UnlockSubmitController extends Controller
{
    public function __invoke(UnlockRequest $request, Station $station, AccessGate $gate): RedirectResponse
    {
        if (! $gate->check($station, $request->string('password')->toString())) {
            return back()->with('unlock_error', 'Incorrect password. Please try again.');
        }

        $request->session()->regenerate();

        $gate->unlock($station);

        return redirect($this->safeRedirectTarget($request, $station));
    }

    /**
     * Only ever redirects within this station's own dossier path — never to
     * an arbitrary URL a caller supplied, which would otherwise be an open
     * redirect (the `redirect` field round-trips through an unauthenticated
     * GET/POST pair, so it must not be trusted blindly).
     */
    private function safeRedirectTarget(UnlockRequest $request, Station $station): string
    {
        $dossierPath = sprintf('/%s/%s', config('dossier.route_prefix'), $station->public_id);
        $candidate = $request->string('redirect')->toString();

        if ($candidate === '') {
            return route('dossier.show', $station);
        }

        $path = (string) parse_url($candidate, PHP_URL_PATH);

        if (str_starts_with($path, $dossierPath)) {
            return $candidate;
        }

        return route('dossier.show', $station);
    }
}
