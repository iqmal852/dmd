<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Enums\Direction;
use App\Enums\StationStatus;
use App\Models\CoordinateSet;
use App\Models\Station;
use Database\Seeders\PlusGcpStationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The real PLUS North-South Expressway GCP dataset
 * (plan/GCP_GDM2000.xls, via database/seeders/data/plus_gcp_stations.csv)
 * — replaces DemoStationSeeder as this app's actual data. See
 * plan/STATUS.md's deviation log.
 */
class PlusGcpStationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_exactly_136_published_stations_with_unique_codes(): void
    {
        $this->seed(PlusGcpStationSeeder::class);

        $this->assertSame(136, Station::query()->count());
        $this->assertSame(136, Station::query()->distinct('code')->count('code'));
        $this->assertSame(136, Station::query()->distinct('public_id')->count('public_id'));
        $this->assertSame(136, Station::query()->where('is_published', true)->count());
    }

    public function test_every_station_has_a_coordinate_set_and_no_specification(): void
    {
        $this->seed(PlusGcpStationSeeder::class);

        $this->assertSame(136, CoordinateSet::query()->count());
        $this->assertSame(0, Station::query()->has('specification')->count());
    }

    public function test_the_first_station_matches_the_source_exactly(): void
    {
        $this->seed(PlusGcpStationSeeder::class);

        $station = Station::query()->where('gcp_reference', 'GP91')->with('coordinateSet')->firstOrFail();

        $this->assertSame('LPT2-GCP-001', $station->code);
        $this->assertSame('LPT2', $station->highway);
        $this->assertSame('N1', $station->section);
        $this->assertSame('KEDAH/PERLIS', $station->location);
        $this->assertSame('27.000', $station->km);
        $this->assertSame(Direction::Northbound, $station->direction);
        $this->assertSame('NB', $station->facility_type);
        $this->assertSame('Ground Control Point', $station->monument_type);
        $this->assertSame(StationStatus::Active, $station->status);
        $this->assertSame('Jitra North Interchange', $station->description);

        $this->assertSame('6.27873856', $station->coordinateSet->latitude);
        $this->assertSame('100.42603444', $station->coordinateSet->longitude);
        $this->assertSame('271440.940', $station->coordinateSet->easting);
        $this->assertSame('695039.238', $station->coordinateSet->northing);
        $this->assertSame('18.792', $station->coordinateSet->ellipsoidal_height);
        $this->assertSame('18.792', $station->coordinateSet->orthometric_height);
    }

    /**
     * Regression: the source's "Bound" column is only sometimes a
     * direction — see PlusGcpStationSeeder::mapDirection()'s docblock.
     * Verifies the suffix-matched compound case ("RSA NB" → Northbound,
     * facility_type preserved verbatim) and the bare non-directional case
     * ("Toll Plaza" → Both) alongside the exact-match case already
     * covered above (bare "NB").
     */
    public function test_a_compound_bound_value_yields_both_a_direction_and_the_raw_facility_type(): void
    {
        $this->seed(PlusGcpStationSeeder::class);

        $rsa = Station::query()->where('gcp_reference', 'GPH03')->firstOrFail();
        $this->assertSame(Direction::Northbound, $rsa->direction);
        $this->assertSame('RSA NB', $rsa->facility_type);

        $tollPlaza = Station::query()->where('gcp_reference', 'GP89')->firstOrFail();
        $this->assertSame(Direction::Both, $tollPlaza->direction);
        $this->assertSame('Toll Plaza', $tollPlaza->facility_type);
    }

    public function test_seeding_is_repeatable_via_migrate_fresh(): void
    {
        $this->seed(PlusGcpStationSeeder::class);

        $this->artisan('migrate:fresh')->run();
        $this->seed(PlusGcpStationSeeder::class);

        $this->assertSame(136, Station::query()->count());
    }
}
