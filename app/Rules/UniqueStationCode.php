<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Station;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * "LPT2-GCP-001" and "lpt2-gcp-001" must not both exist. The database
 * already enforces this via a functional unique index
 * (`stations_code_lower_unique`, see `02-data-model.md` §9), but the form
 * request checks it too so an admin sees a friendly validation message
 * instead of a raw database constraint violation — see
 * plan/phases/phase-08-admin-qr.md M8.3 Test Gate #4.
 */
final readonly class UniqueStationCode implements ValidationRule
{
    public function __construct(
        private ?int $ignoreStationId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Station::query()
            ->whereRaw('lower(code) = ?', [Str::lower((string) $value)])
            ->when($this->ignoreStationId, fn ($query, $id) => $query->whereKeyNot($id))
            ->exists();

        if ($exists) {
            $fail('A station with this code already exists.');
        }
    }
}
