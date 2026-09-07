<?php

declare(strict_types=1);

namespace Tests\Feature\Dossier;

use App\Models\CoordinateSet;
use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Assert;
use Tests\TestCase;

/**
 * plan/phases/phase-09-hardening-release.md M9.2.
 */
class StationCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_second_request_for_the_same_station_issues_fewer_queries(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        CoordinateSet::factory()->for($station)->create();

        $this->get(route('dossier.show', $station))->assertOk();

        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->get(route('dossier.show', $station))->assertOk();

        Assert::assertLessThanOrEqual(
            1,
            $queryCount,
            "Second (cached) request issued {$queryCount} queries, expected the station-binding query only.",
        );
    }

    public function test_an_admin_write_makes_the_next_public_read_reflect_the_change(): void
    {
        $admin = User::factory()->create();
        $station = Station::factory()->create(['is_published' => true, 'monument_type' => 'Original Type']);

        // Warm the cache with the original value.
        $this->get(route('dossier.show', $station))
            ->assertInertia(fn ($page) => $page->where('station.monumentType', 'Original Type'));

        $this->actingAs($admin)->put(route('admin.stations.update', $station), [
            'code' => $station->code,
            'highway' => $station->highway,
            'km' => (string) $station->km,
            'direction' => $station->direction->value,
            'monument_type' => 'Updated Type',
            'status' => $station->status->value,
            'is_published' => '1',
        ]);

        $this->get(route('dossier.show', $station))
            ->assertInertia(fn ($page) => $page->where('station.monumentType', 'Updated Type'));
    }

    public function test_a_photo_upload_makes_the_next_public_read_reflect_the_new_count(): void
    {
        $admin = User::factory()->create();
        $station = Station::factory()->create(['is_published' => true]);

        $this->get(route('dossier.show', $station))
            ->assertInertia(fn ($page) => $page->where('station.modules.photoCount', 0));

        $this->actingAs($admin)->post(route('admin.stations.photos.store', $station), [
            'file' => UploadedFile::fake()->image('a.jpg', 800, 600),
            'photo_type' => 'eye_level',
        ]);

        $this->get(route('dossier.show', $station))
            ->assertInertia(fn ($page) => $page->where('station.modules.photoCount', 1));
    }
}
