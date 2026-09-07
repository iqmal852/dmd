<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Enums\Direction;
use App\Enums\QcStatus;
use App\Enums\StationStatus;
use App\Models\Station;
use Database\Seeders\DemoStationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoStationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_exactly_25_stations_with_unique_public_ids(): void
    {
        $this->seed(DemoStationSeeder::class);

        $this->assertSame(25, Station::query()->count());
        $this->assertSame(25, Station::query()->distinct('public_id')->count('public_id'));
    }

    public function test_lpt2_gcp_015_matches_every_value_in_the_poster(): void
    {
        $this->seed(DemoStationSeeder::class);

        $station = Station::query()->where('code', 'LPT2-GCP-015')->with(['coordinateSet', 'specification'])->firstOrFail();

        $this->assertSame('LPT2', $station->highway);
        $this->assertSame('E1', $station->section);
        $this->assertSame('318.200', $station->km);
        $this->assertSame(Direction::Westbound, $station->direction);
        $this->assertSame('Concrete Block', $station->monument_type);
        $this->assertSame('2025-03-15', $station->installed_at->toDateString());
        $this->assertSame(StationStatus::Active, $station->status);

        $this->assertSame('4.27412582', $station->coordinateSet->latitude);
        $this->assertSame('103.43658211', $station->coordinateSet->longitude);
        $this->assertSame('128.346', $station->coordinateSet->ellipsoidal_height);
        $this->assertSame('428765.212', $station->coordinateSet->easting);
        $this->assertSame('472318.678', $station->coordinateSet->northing);
        $this->assertSame('112.436', $station->coordinateSet->orthometric_height);
        $this->assertSame('MyGEOID', $station->coordinateSet->geoid_model);

        $this->assertSame('Static / RTK', $station->specification->observation_method);
        $this->assertSame(120, $station->specification->observation_minutes);
        $this->assertSame(18, $station->specification->satellite_count);
        $this->assertSame('1.60', $station->specification->pdop_max);
        $this->assertSame(15, $station->specification->elevation_cutoff_deg);
        $this->assertSame('Geodetic L1/L2', $station->specification->antenna_type);
        $this->assertSame('1.532', $station->specification->antenna_height);
        $this->assertSame('Bottom of ARP', $station->specification->antenna_reference_point);
        $this->assertSame('10.00', $station->specification->horizontal_rms_mm);
        $this->assertSame('15.00', $station->specification->vertical_rms_mm);
        $this->assertSame(QcStatus::Verified, $station->specification->qc_status);
    }

    public function test_it_seeds_at_least_one_incomplete_station_for_empty_state_coverage(): void
    {
        $this->seed(DemoStationSeeder::class);

        $this->assertGreaterThan(0, Station::query()->doesntHave('coordinateSet')->count());
        $this->assertGreaterThan(0, Station::query()->doesntHave('specification')->count());
    }

    public function test_seeding_is_repeatable_via_migrate_fresh(): void
    {
        $this->seed(DemoStationSeeder::class);

        $this->artisan('migrate:fresh')->run();
        $this->seed(DemoStationSeeder::class);

        $this->assertSame(25, Station::query()->count());
    }
}
