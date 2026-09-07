<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Station;
use App\Models\User;

/**
 * Every write to a Station goes through this policy even though there is
 * only one user. See plan/phases/phase-08-admin-qr.md M8.1: this is where
 * a second role (read-only editor, regional admin, ...) would attach
 * without having to retrofit authorisation checks across every controller
 * after the fact.
 */
class StationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Station $station): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Station $station): bool
    {
        return true;
    }

    public function delete(User $user, Station $station): bool
    {
        return true;
    }
}
