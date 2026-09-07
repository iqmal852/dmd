<?php

declare(strict_types=1);

namespace Tests\Feature\Dossier;

use App\Enums\AccessMode;
use App\Models\CoordinateSet;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * plan/phases/phase-05-location-map.md Test Gate.
 */
class MapTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_200_with_the_map_component(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        CoordinateSet::factory()->for($station)->create();

        $response = $this->get(route('dossier.map', $station));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('dossier/map'));
    }

    public function test_lat_lon_are_numbers_while_detail_card_fields_are_formatted_strings(): void
    {
        $station = Station::factory()->create([
            'is_published' => true,
            'km' => '318.200',
        ]);
        CoordinateSet::factory()->for($station)->create([
            'latitude' => '4.27412582',
            'longitude' => '103.43658211',
        ]);

        $response = $this->get(route('dossier.map', $station));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('station.latitude', 4.27412582)
            ->where('station.longitude', 103.43658211)
            ->where('station.km', 'KM 318.200')
        );
    }

    public function test_tile_urls_come_from_config(): void
    {
        config(['dossier.map.satellite_url' => 'https://example.test/{z}/{x}/{y}.png']);
        $station = Station::factory()->create(['is_published' => true]);
        CoordinateSet::factory()->for($station)->create();

        $response = $this->get(route('dossier.map', $station));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('map.satelliteUrl', 'https://example.test/{z}/{x}/{y}.png')
        );
    }

    public function test_the_route_is_behind_the_access_gate(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'secret',
        ]);
        $station = Station::factory()->create(['is_published' => true]);
        CoordinateSet::factory()->for($station)->create();

        $this->get(route('dossier.map', $station))->assertRedirect();
    }

    public function test_a_station_without_coordinates_renders_without_a_broken_map(): void
    {
        $station = Station::factory()->create(['is_published' => true]);

        $response = $this->get(route('dossier.map', $station));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->where('station', null));
    }
}
