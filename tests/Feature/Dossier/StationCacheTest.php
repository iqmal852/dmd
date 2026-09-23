<?php

declare(strict_types=1);

namespace Tests\Feature\Dossier;

use App\Models\CoordinateSet;
use App\Models\Specification;
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

    /**
     * Regression: StationCache::remember() was caching Data objects
     * (StationSummaryData, CoordinateSetData, PhotoData, ...) directly,
     * or arrays containing them. config('cache.serializable_classes')
     * defaults to `false`, which makes the real `database` cache store's
     * unserialize() refuse to reconstruct *any* object class on a
     * cache-hit read — it returns a `__PHP_Incomplete_Class` stand-in
     * instead, with every original property still attached.
     *
     * That's silent, not a crash — every Data class here has nothing but
     * a constructor (verified: no instance methods, no `instanceof`
     * check anywhere reads a cached value), so property-only access
     * still works, and json_encode() happily serializes an incomplete
     * object using its preserved properties. The one observable symptom
     * is that PHP's json_encode adds an extra `__PHP_Incomplete_Class_Name`
     * key alongside the real ones — everything else about the response
     * looks completely normal, which is exactly why this went unnoticed
     * (and why phpunit.xml's CACHE_STORE=array override, which never
     * serializes anything, couldn't have caught it either). This forces
     * the real `database` store and checks for that marker's absence on
     * a genuine cache-hit (second request, same version, no write in
     * between) rather than trusting a merely-200 status.
     */
    public function test_a_cache_hit_never_produces_an_incomplete_class(): void
    {
        config(['cache.default' => 'database']);

        $admin = User::factory()->create();
        $station = Station::factory()->create(['is_published' => true]);
        CoordinateSet::factory()->for($station)->create();
        Specification::factory()->for($station)->create();
        $this->actingAs($admin)->post(route('admin.stations.photos.store', $station), [
            'files' => [UploadedFile::fake()->image('a.jpg', 800, 600)],
        ]);
        $station->addMediaFromString('%PDF-1.4')
            ->usingFileName('a.pdf')
            ->withCustomProperties(['document_type' => 'as_built', 'title' => 'A', 'is_primary' => true])
            ->toMediaCollection('documents');

        $topLevelProp = [
            'dossier.show' => 'station',
            'dossier.coordinates' => 'coordinateSet',
            'dossier.map' => 'station',
            'dossier.photos' => 'photos.0',
            'dossier.files' => 'documents.0',
        ];

        foreach ($topLevelProp as $routeName => $prop) {
            $url = route($routeName, $station);

            $this->get($url)->assertOk();

            // The real assertion: a genuine cache-hit response must not
            // carry PHP's incomplete-class marker on any cached Data
            // object, top-level or nested.
            $this->get($url)->assertOk()->assertInertia(
                fn ($page) => $page->missing("{$prop}.__PHP_Incomplete_Class_Name"),
            );
        }
    }

    public function test_a_photo_upload_makes_the_next_public_read_reflect_the_new_count(): void
    {
        $admin = User::factory()->create();
        $station = Station::factory()->create(['is_published' => true]);

        $this->get(route('dossier.show', $station))
            ->assertInertia(fn ($page) => $page->where('station.modules.photoCount', 0));

        $this->actingAs($admin)->post(route('admin.stations.photos.store', $station), [
            'files' => [UploadedFile::fake()->image('a.jpg', 800, 600)],
        ]);

        $this->get(route('dossier.show', $station))
            ->assertInertia(fn ($page) => $page->where('station.modules.photoCount', 1));
    }
}
