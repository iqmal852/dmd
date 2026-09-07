<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\Direction;
use App\Enums\StationStatus;
use App\Models\CoordinateSet;
use App\Models\Specification;
use App\Models\Station;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_station_assigns_a_ulid_public_id(): void
    {
        $station = Station::factory()->create();

        $this->assertNotNull($station->public_id);
        $this->assertSame(26, strlen($station->public_id));
    }

    public function test_the_route_key_is_the_public_id_not_the_numeric_id(): void
    {
        $station = Station::factory()->create();

        $this->assertSame('public_id', $station->getRouteKeyName());
        $this->assertSame($station->public_id, $station->getRouteKey());
    }

    public function test_code_uniqueness_is_case_insensitive(): void
    {
        Station::factory()->create(['code' => 'LPT2-GCP-001']);

        $this->expectException(QueryException::class);

        Station::factory()->create(['code' => 'lpt2-gcp-001']);
    }

    public function test_access_password_is_hashed_on_write(): void
    {
        $station = Station::factory()->create(['access_password' => 'a-plain-password']);

        $this->assertNotSame('a-plain-password', $station->access_password);
        $this->assertTrue(Hash::check('a-plain-password', $station->access_password));
    }

    public function test_access_password_defaults_to_null(): void
    {
        $station = Station::factory()->create();

        $this->assertNull($station->access_password);
    }

    public function test_published_scope_excludes_unpublished_stations(): void
    {
        Station::factory()->unpublished()->create();
        $published = Station::factory()->create(['is_published' => true]);

        $result = Station::query()->published()->get();

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($published));
    }

    public function test_soft_deleting_removes_a_station_from_published_scope_but_keeps_the_row(): void
    {
        $station = Station::factory()->create(['is_published' => true]);

        $station->delete();

        $this->assertCount(0, Station::query()->published()->get());
        $this->assertDatabaseHas('stations', ['id' => $station->id]);
        $this->assertNotNull($station->fresh()->deleted_at);
    }

    public function test_deleting_a_station_cascades_to_coordinate_set_and_specification(): void
    {
        $station = Station::factory()->create();
        $coordinateSet = CoordinateSet::factory()->for($station)->create();
        $specification = Specification::factory()->for($station)->create();

        $station->forceDelete();

        $this->assertDatabaseMissing('coordinate_sets', ['id' => $coordinateSet->id]);
        $this->assertDatabaseMissing('specifications', ['id' => $specification->id]);
    }

    public function test_km_direction_and_status_cast_correctly(): void
    {
        $station = Station::factory()->create([
            'km' => '318.200',
            'direction' => Direction::Westbound->value,
            'status' => StationStatus::Active->value,
        ]);

        $this->assertSame('318.200', $station->km);
        $this->assertSame(Direction::Westbound, $station->direction);
        $this->assertSame(StationStatus::Active, $station->status);
    }

    public function test_for_highway_scope_filters_correctly(): void
    {
        Station::factory()->create(['highway' => 'LPT2']);
        Station::factory()->create(['highway' => 'NKVE']);

        $this->assertCount(1, Station::query()->forHighway('NKVE')->get());
    }
}
