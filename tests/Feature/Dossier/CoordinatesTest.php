<?php

declare(strict_types=1);

namespace Tests\Feature\Dossier;

use App\Enums\AccessMode;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Assert;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Picture1.png panels 2a/2b — plan/phases/phase-04-coordinates-specs.md
 * Test Gate.
 */
class CoordinatesTest extends TestCase
{
    use RefreshDatabase;

    private function referenceStation(): Station
    {
        $station = Station::factory()->create([
            'is_published' => true,
            'code' => 'LPT2-GCP-015',
        ]);

        $station->coordinateSet()->create([
            'latitude' => '4.27412582',
            'longitude' => '103.43658211',
            'ellipsoidal_height' => '128.346',
            'easting' => '428765.212',
            'northing' => '472318.678',
            'orthometric_height' => '112.436',
            'geoid_model' => 'MyGEOID',
        ]);

        $station->specification()->create([
            'observation_method' => 'Static / RTK',
            'observation_minutes' => 120,
            'satellite_count' => 18,
            'pdop_max' => '1.60',
            'elevation_cutoff_deg' => 15,
            'antenna_type' => 'Geodetic L1/L2',
            'antenna_height' => '1.532',
            'antenna_reference_point' => 'Bottom of ARP',
            'horizontal_rms_mm' => '10.00',
            'vertical_rms_mm' => '15.00',
            'qc_status' => 'verified',
        ]);

        return $station->fresh();
    }

    public function test_it_returns_200_with_the_coordinates_component(): void
    {
        $station = Station::factory()->create(['is_published' => true]);

        $response = $this->get(route('dossier.coordinates', $station));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('dossier/coordinates'));
    }

    public function test_props_contain_exactly_the_dto_fields(): void
    {
        $station = $this->referenceStation();

        $response = $this->get(route('dossier.coordinates', $station));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('coordinateSet', fn (AssertableInertia $c) => $c
                ->hasAll([
                    'latitude', 'longitude', 'ellipsoidalHeight', 'easting', 'northing',
                    'zone', 'orthometricHeight', 'geoidModel', 'epoch',
                    'latitudeRaw', 'longitudeRaw',
                ])
                ->missing('id')
                ->missing('station_id')
                ->etc()
            )
            ->has('specification', fn (AssertableInertia $s) => $s
                ->hasAll([
                    'observationMethod', 'observationMinutes', 'satelliteCount', 'pdopMax',
                    'elevationCutoff', 'antennaType', 'antennaHeight', 'antennaReferencePoint',
                    'horizontalRms', 'verticalRms', 'qcStatus', 'qcStatusColor',
                ])
                ->missing('id')
                ->missing('station_id')
                ->etc()
            )
        );
    }

    public function test_every_value_matches_the_poster_exactly(): void
    {
        $station = $this->referenceStation();

        $response = $this->get(route('dossier.coordinates', $station));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('coordinateSet.latitude', '4.27412582 °')
            ->where('coordinateSet.longitude', '103.43658211 °')
            ->where('coordinateSet.ellipsoidalHeight', '128.346 m')
            ->where('coordinateSet.easting', '428,765.212 m')
            ->where('coordinateSet.northing', '472,318.678 m')
            ->where('coordinateSet.orthometricHeight', '112.436 m')
            ->where('specification.observationMethod', 'Static / RTK')
            ->where('specification.observationMinutes', '120 min')
            ->where('specification.satelliteCount', 18)
            ->where('specification.pdopMax', '1.6')
            ->where('specification.elevationCutoff', '15 °')
            ->where('specification.antennaType', 'Geodetic L1/L2')
            ->where('specification.antennaHeight', '1.532 m')
            ->where('specification.antennaReferencePoint', 'Bottom of ARP')
            ->where('specification.horizontalRms', '≤ 10 mm')
            ->where('specification.verticalRms', '≤ 15 mm')
            ->where('specification.qcStatus', 'Verified')
        );
    }

    public function test_latitude_raw_has_no_degree_sign_or_separators(): void
    {
        $station = $this->referenceStation();

        $response = $this->get(route('dossier.coordinates', $station));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('coordinateSet.latitudeRaw', '4.27412582')
            ->where('coordinateSet.longitudeRaw', '103.43658211')
        );
    }

    public function test_a_station_without_a_coordinate_set_renders_without_error(): void
    {
        $station = Station::factory()->create(['is_published' => true]);

        $response = $this->get(route('dossier.coordinates', $station));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('coordinateSet', null)
            ->where('specification', null)
        );
    }

    public function test_the_route_is_behind_the_access_gate(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'secret',
        ]);
        $station = $this->referenceStation();

        $response = $this->get(route('dossier.coordinates', $station));

        $response->assertRedirect();
        $this->assertStringNotContainsString('4.27412582', $response->getContent() ?: '');
    }

    public function test_the_coordinates_route_issues_a_bounded_number_of_queries(): void
    {
        $station = $this->referenceStation();

        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->get(route('dossier.coordinates', $station))->assertOk();

        Assert::assertLessThanOrEqual(3, $queryCount);
    }
}
