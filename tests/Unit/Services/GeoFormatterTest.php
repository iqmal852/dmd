<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\GeoFormatter;
use Tests\TestCase;

/**
 * Every assertion here is a literal string transcribed from Picture1.png.
 * This test is the contract between the data model and the screens — see
 * plan/phases/phase-01-data-model.md M1.6.
 */
class GeoFormatterTest extends TestCase
{
    private GeoFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = new GeoFormatter;
    }

    public function test_latitude_matches_the_poster(): void
    {
        $this->assertSame('4.27412582 °', $this->formatter->latitude('4.27412582'));
    }

    public function test_longitude_matches_the_poster(): void
    {
        $this->assertSame('103.43658211 °', $this->formatter->longitude('103.43658211'));
    }

    public function test_metres_matches_the_poster(): void
    {
        $this->assertSame('128.346 m', $this->formatter->metres('128.346'));
        $this->assertSame('112.436 m', $this->formatter->metres('112.436'));
    }

    public function test_metres_grouped_matches_the_poster(): void
    {
        $this->assertSame('428,765.212 m', $this->formatter->metresGrouped('428765.212'));
        $this->assertSame('472,318.678 m', $this->formatter->metresGrouped('472318.678'));
    }

    public function test_tolerance_matches_the_poster(): void
    {
        $this->assertSame('≤ 10 mm', $this->formatter->tolerance('10.00'));
        $this->assertSame('≤ 15 mm', $this->formatter->tolerance('15.00'));
    }

    public function test_km_matches_the_poster(): void
    {
        $this->assertSame('KM 318.200', $this->formatter->km('318.200'));
    }

    public function test_installed_date_matches_the_poster(): void
    {
        $this->assertSame('15/03/2025', $this->formatter->installedDate('2025-03-15'));
    }

    public function test_installed_date_handles_null(): void
    {
        $this->assertNull($this->formatter->installedDate(null));
    }

    public function test_degrees_matches_the_poster_for_elevation_cutoff(): void
    {
        $this->assertSame('15 °', $this->formatter->degrees(15));
    }

    public function test_minutes_matches_the_poster(): void
    {
        $this->assertSame('120 min', $this->formatter->minutes(120));
    }

    public function test_plain_number_trims_trailing_zeros_for_pdop(): void
    {
        $this->assertSame('1.6', $this->formatter->plainNumber('1.60'));
        $this->assertSame('10', $this->formatter->plainNumber('10.00'));
    }
}
