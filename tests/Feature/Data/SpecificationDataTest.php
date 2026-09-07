<?php

declare(strict_types=1);

namespace Tests\Feature\Data;

use App\Data\SpecificationData;
use App\Models\Specification;
use App\Services\GeoFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecificationDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_from_produces_the_exact_poster_strings(): void
    {
        $specification = Specification::factory()->create([
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

        $data = SpecificationData::from($specification, new GeoFormatter);

        $this->assertSame('Static / RTK', $data->observationMethod);
        $this->assertSame('120 min', $data->observationMinutes);
        $this->assertSame(18, $data->satelliteCount);
        $this->assertSame('1.6', $data->pdopMax);
        $this->assertSame('15 °', $data->elevationCutoff);
        $this->assertSame('Geodetic L1/L2', $data->antennaType);
        $this->assertSame('1.532 m', $data->antennaHeight);
        $this->assertSame('Bottom of ARP', $data->antennaReferencePoint);
        $this->assertSame('≤ 10 mm', $data->horizontalRms);
        $this->assertSame('≤ 15 mm', $data->verticalRms);
        $this->assertSame('Verified', $data->qcStatus);
        $this->assertSame('accent', $data->qcStatusColor);
    }

    public function test_from_handles_a_fully_null_specification(): void
    {
        $specification = Specification::factory()->create([
            'observation_method' => null,
            'observation_minutes' => null,
            'satellite_count' => null,
            'pdop_max' => null,
            'elevation_cutoff_deg' => null,
            'antenna_type' => null,
            'antenna_height' => null,
            'antenna_reference_point' => null,
            'horizontal_rms_mm' => null,
            'vertical_rms_mm' => null,
            'qc_status' => 'pending',
        ]);

        $data = SpecificationData::from($specification, new GeoFormatter);

        $this->assertNull($data->observationMethod);
        $this->assertNull($data->pdopMax);
        $this->assertNull($data->elevationCutoff);
        $this->assertSame('Pending', $data->qcStatus);
    }
}
