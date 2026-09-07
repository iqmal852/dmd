<?php

declare(strict_types=1);

namespace Tests\Feature\Data;

use App\Data\CoordinateSetData;
use App\Models\CoordinateSet;
use App\Services\GeoFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinateSetDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_from_produces_the_exact_poster_strings(): void
    {
        $coordinateSet = CoordinateSet::factory()->create([
            'latitude' => '4.27412582',
            'longitude' => '103.43658211',
            'ellipsoidal_height' => '128.346',
            'easting' => '428765.212',
            'northing' => '472318.678',
            'orthometric_height' => '112.436',
        ]);

        $data = CoordinateSetData::from($coordinateSet, new GeoFormatter);

        $this->assertSame('4.27412582 °', $data->latitude);
        $this->assertSame('103.43658211 °', $data->longitude);
        $this->assertSame('128.346 m', $data->ellipsoidalHeight);
        $this->assertSame('428,765.212 m', $data->easting);
        $this->assertSame('472,318.678 m', $data->northing);
        $this->assertSame('112.436 m', $data->orthometricHeight);
        $this->assertSame('4.27412582', $data->latitudeRaw);
        $this->assertSame('103.43658211', $data->longitudeRaw);
    }
}
