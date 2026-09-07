<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * The single place any survey number becomes a display string. Every value
 * shown on the public dossier passes through here — never through a
 * TypeScript formatter — because a parseFloat() in the browser would quietly
 * destroy the 8th decimal place Picture1.png shows. See
 * plan/02-data-model.md §3 and plan/01-architecture.md ADR-010.
 */
final class GeoFormatter
{
    /**
     * "4.27412582" → "4.27412582 °"
     */
    public function latitude(string $value): string
    {
        return $this->degrees($value);
    }

    /**
     * "103.43658211" → "103.43658211 °"
     */
    public function longitude(string $value): string
    {
        return $this->degrees($value);
    }

    private function degrees(string $value): string
    {
        return "{$value} °";
    }

    /**
     * "128.346" → "128.346 m"
     */
    public function metres(string $value): string
    {
        return "{$value} m";
    }

    /**
     * "428765.212" → "428,765.212 m"
     */
    public function metresGrouped(string $value): string
    {
        return $this->groupThousands($value).' m';
    }

    private function groupThousands(string $value): string
    {
        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '-');

        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, null);

        $grouped = number_format((float) $whole, 0, '', ',');

        $result = $fraction !== null ? "{$grouped}.{$fraction}" : $grouped;

        return $negative ? "-{$result}" : $result;
    }

    /**
     * "10.00" → "≤ 10 mm" (a tolerance, not a raw measurement — see
     * plan/02-data-model.md §4).
     */
    public function tolerance(string $value): string
    {
        return '≤ '.$this->trimTrailingZeros($value).' mm';
    }

    private function trimTrailingZeros(string $value): string
    {
        if (! str_contains($value, '.')) {
            return $value;
        }

        return rtrim(rtrim($value, '0'), '.');
    }

    /**
     * "318.200" → "KM 318.200"
     */
    public function km(string $value): string
    {
        return "KM {$value}";
    }

    /**
     * A date (or date-like string) → "15/03/2025"
     */
    public function installedDate(CarbonInterface|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value)->format('d/m/Y');
    }
}
