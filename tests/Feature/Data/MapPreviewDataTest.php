<?php

declare(strict_types=1);

namespace Tests\Feature\Data;

use App\Data\MapPreviewData;
use App\Models\CoordinateSet;
use App\Models\Station;
use App\Services\GeoFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapPreviewDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_from_builds_a_tile_url_and_pin_position(): void
    {
        $station = Station::factory()->create(['km' => '318.200']);
        CoordinateSet::factory()->for($station)->create([
            'latitude' => '4.27412582',
            'longitude' => '103.43658211',
        ]);

        $data = MapPreviewData::from($station->fresh(), new GeoFormatter);

        $this->assertNotNull($data);
        $this->assertStringContainsString('/15/15994/25799', $data->tileUrl);
        $this->assertGreaterThanOrEqual(0.0, $data->pinLeftPercent);
        $this->assertLessThanOrEqual(100.0, $data->pinLeftPercent);
        $this->assertGreaterThanOrEqual(0.0, $data->pinTopPercent);
        $this->assertLessThanOrEqual(100.0, $data->pinTopPercent);
        $this->assertSame('KM 318.000', $data->kmBefore);
        $this->assertSame('KM 318.400', $data->kmAfter);
    }

    public function test_from_returns_null_without_a_coordinate_set(): void
    {
        $station = Station::factory()->create();

        $this->assertNull(MapPreviewData::from($station, new GeoFormatter));
    }
}
