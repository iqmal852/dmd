<?php

declare(strict_types=1);

namespace Tests\Feature\Data;

use App\Data\StationMapData;
use App\Models\CoordinateSet;
use App\Models\Station;
use App\Services\GeoFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationMapDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_from_produces_numeric_lat_lon_and_formatted_detail_fields(): void
    {
        $station = Station::factory()->create([
            'code' => 'LPT2-GCP-015',
            'km' => '318.200',
        ]);
        CoordinateSet::factory()->for($station)->create([
            'latitude' => '4.27412582',
            'longitude' => '103.43658211',
        ]);

        $data = StationMapData::from($station->fresh(), new GeoFormatter);

        $this->assertNotNull($data);
        $this->assertIsFloat($data->latitude);
        $this->assertIsFloat($data->longitude);
        $this->assertSame(4.27412582, $data->latitude);
        $this->assertSame(103.43658211, $data->longitude);
        $this->assertSame('KM 318.200', $data->km);
    }

    public function test_from_returns_null_without_a_coordinate_set(): void
    {
        $station = Station::factory()->create();

        $this->assertNull(StationMapData::from($station, new GeoFormatter));
    }
}
