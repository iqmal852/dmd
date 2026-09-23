<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Specification;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * plan/phases/phase-08-admin-qr.md M8.6. Every field on `specifications`
 * is nullable except the FK and `qc_status` (which defaults to
 * `pending`) — see that migration's own docblock — precisely so a real
 * GCP station (the PlusGcpStationSeeder import, none of which carry any
 * GNSS/QC data) can sit with no specification at all until an admin
 * fills it in later. This suite proves that "fill it in later" path
 * actually works, since nothing exercised it before.
 */
class AdminSpecificationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'observation_method' => 'Static / RTK',
            'observation_minutes' => '120',
            'satellite_count' => '18',
            'pdop_max' => '1.60',
            'elevation_cutoff_deg' => '15',
            'antenna_type' => 'Geodetic L1/L2',
            'antenna_height' => '1.532',
            'antenna_reference_point' => 'Bottom of ARP',
            'horizontal_rms_mm' => '10.00',
            'vertical_rms_mm' => '15.00',
            'qc_status' => 'verified',
        ], $overrides);
    }

    public function test_it_creates_a_specification_for_a_station_that_has_none_yet(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $this->assertNull($station->specification);

        $response = $this->actingAs($user)->put(
            route('admin.stations.specification.update', $station),
            $this->validPayload(),
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Specification updated.');

        $specification = $station->refresh()->specification;
        $this->assertNotNull($specification);
        $this->assertSame('Static / RTK', $specification->observation_method);
        $this->assertSame(18, $specification->satellite_count);
        $this->assertSame('verified', $specification->qc_status->value);
    }

    public function test_it_updates_an_existing_specification_in_place_rather_than_duplicating_it(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();
        Specification::factory()->for($station)->create(['qc_status' => 'pending']);

        $this->actingAs($user)->put(
            route('admin.stations.specification.update', $station),
            $this->validPayload(['qc_status' => 'verified']),
        );

        $this->assertSame(1, Specification::query()->where('station_id', $station->id)->count());
        $this->assertSame('verified', $station->refresh()->specification->qc_status->value);
    }

    public function test_every_field_except_qc_status_may_be_submitted_blank(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $response = $this->actingAs($user)->put(
            route('admin.stations.specification.update', $station),
            ['qc_status' => 'pending'],
        );

        $response->assertSessionHasNoErrors();

        $specification = $station->refresh()->specification;
        $this->assertNotNull($specification);
        $this->assertNull($specification->observation_method);
        $this->assertNull($specification->satellite_count);
        $this->assertSame('pending', $specification->qc_status->value);
    }

    public function test_qc_status_is_required(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $response = $this->actingAs($user)->put(
            route('admin.stations.specification.update', $station),
            $this->validPayload(['qc_status' => null]),
        );

        $response->assertSessionHasErrors('qc_status');
        $this->assertNull($station->refresh()->specification);
    }

    public function test_it_requires_authentication(): void
    {
        $station = Station::factory()->create();

        $response = $this->put(
            route('admin.stations.specification.update', $station),
            $this->validPayload(),
        );

        $response->assertRedirect(route('login'));
        $this->assertNull($station->refresh()->specification);
    }
}
