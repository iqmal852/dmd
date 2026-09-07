<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccessMode;
use App\Models\Station;
use Illuminate\Support\Facades\Hash;

/**
 * Implements the access-mode behaviour matrix from
 * plan/04-env-configuration.md §6. A station's own `access_password`
 * always overrides the global `.env` setting; a misconfigured deployment
 * (password mode, no password resolvable) must fail closed, never open.
 */
final class AccessGate
{
    public function isRequired(Station $station): bool
    {
        return $station->access_password !== null
            || config('dossier.access_mode') === AccessMode::Password;
    }

    /**
     * True when password mode is configured but no password is resolvable
     * for this station — a deployment misconfiguration that must never
     * silently degrade to serving the dossier openly.
     */
    public function isMisconfigured(Station $station): bool
    {
        return config('dossier.access_mode') === AccessMode::Password
            && $station->access_password === null
            && blank(config('dossier.access_password'));
    }

    public function check(Station $station, string $candidate): bool
    {
        $hash = $station->access_password;

        return $hash !== null
            ? Hash::check($candidate, $hash)
            : hash_equals((string) config('dossier.access_password'), $candidate);
    }

    public function unlock(Station $station): void
    {
        session()->put(
            "dossier_unlocked.{$station->public_id}",
            now()->addMinutes((int) config('dossier.unlock_ttl'))->timestamp,
        );
    }

    public function isUnlocked(Station $station): bool
    {
        $expiresAt = session("dossier_unlocked.{$station->public_id}");

        return is_int($expiresAt) && $expiresAt > now()->timestamp;
    }
}
