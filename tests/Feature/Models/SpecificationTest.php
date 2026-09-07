<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\QcStatus;
use App\Models\Specification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_specification_with_only_the_required_fields_saves_cleanly(): void
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
            'qc_status' => QcStatus::Pending->value,
        ]);

        $fresh = $specification->fresh();

        $this->assertSame(QcStatus::Pending, $fresh->qc_status);
        $this->assertNull($fresh->observation_method);
        $this->assertNull($fresh->pdop_max);
    }

    public function test_qc_status_casts_to_the_enum(): void
    {
        $specification = Specification::factory()->create(['qc_status' => QcStatus::Verified->value]);

        $this->assertSame(QcStatus::Verified, $specification->fresh()->qc_status);
    }
}
