<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * plan/phases/phase-08-admin-qr.md M8.7. StorePhotoRequest accepts every
 * selected file from one multi-select in a single request — explicit
 * user request ("upload multi photo, put label for each, click save") —
 * see that request's own docblock for why this is a native
 * `files[]`/`labels[]` array pair rather than a synthetic per-file
 * request loop.
 */
class AdminPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_a_single_photo_with_a_label(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.stations.photos.store', $station), [
            'files' => [UploadedFile::fake()->image('a.jpg', 800, 600)],
            'labels' => ['Facing the highway'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Photo uploaded.');

        $photos = $station->fresh()->getMedia('photos');
        $this->assertCount(1, $photos);
        $this->assertSame('Facing the highway', $photos->first()->getCustomProperty('label'));
    }

    public function test_it_uploads_several_photos_at_once_each_with_its_own_label(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.stations.photos.store', $station), [
            'files' => [
                UploadedFile::fake()->image('a.jpg', 800, 600),
                UploadedFile::fake()->image('b.jpg', 800, 600),
                UploadedFile::fake()->image('c.jpg', 800, 600),
            ],
            'labels' => ['First', 'Second', 'Third'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', '3 photos uploaded.');

        $labels = $station->fresh()->getMedia('photos')
            ->map(fn ($media) => $media->getCustomProperty('label'))
            ->all();

        $this->assertSame(['First', 'Second', 'Third'], $labels);
    }

    public function test_a_blank_label_is_stored_as_null_not_an_empty_string(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $this->actingAs($user)->post(route('admin.stations.photos.store', $station), [
            'files' => [UploadedFile::fake()->image('a.jpg', 800, 600)],
            'labels' => [''],
        ]);

        $photo = $station->fresh()->getMedia('photos')->first();
        $this->assertNull($photo->getCustomProperty('label'));
    }

    public function test_labels_are_optional_entirely(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.stations.photos.store', $station), [
            'files' => [UploadedFile::fake()->image('a.jpg', 800, 600)],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertCount(1, $station->fresh()->getMedia('photos'));
    }

    public function test_at_least_one_file_is_required(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.stations.photos.store', $station), [
            'files' => [],
        ]);

        $response->assertSessionHasErrors('files');
        $this->assertCount(0, $station->fresh()->getMedia('photos'));
    }

    public function test_a_non_image_file_is_rejected(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.stations.photos.store', $station), [
            'files' => [UploadedFile::fake()->create('a.pdf', 100, 'application/pdf')],
        ]);

        $response->assertSessionHasErrors('files.0');
        $this->assertCount(0, $station->fresh()->getMedia('photos'));
    }

    public function test_the_station_cache_is_bumped_exactly_once_for_the_whole_batch(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create(['is_published' => true]);

        $this->get(route('dossier.show', $station))
            ->assertInertia(fn ($page) => $page->where('station.modules.photoCount', 0));

        $this->actingAs($user)->post(route('admin.stations.photos.store', $station), [
            'files' => [
                UploadedFile::fake()->image('a.jpg', 800, 600),
                UploadedFile::fake()->image('b.jpg', 800, 600),
            ],
        ]);

        $this->get(route('dossier.show', $station))
            ->assertInertia(fn ($page) => $page->where('station.modules.photoCount', 2));
    }

    public function test_it_requires_authentication(): void
    {
        $station = Station::factory()->create();

        $response = $this->post(route('admin.stations.photos.store', $station), [
            'files' => [UploadedFile::fake()->image('a.jpg', 800, 600)],
        ]);

        $response->assertRedirect(route('login'));
        $this->assertCount(0, $station->fresh()->getMedia('photos'));
    }

    public function test_updating_a_photo_changes_its_label_and_bearing(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();
        $media = $station->addMediaFromString(
            (string) UploadedFile::fake()->image('a.jpg', 800, 600)->get(),
        )->usingFileName('a.jpg')->toMediaCollection('photos');

        $response = $this->actingAs($user)->patch(
            route('admin.stations.photos.update', ['station' => $station, 'media' => $media->uuid]),
            ['label' => 'Updated label', 'bearing' => 90],
        );

        $response->assertRedirect();

        $media->refresh();
        $this->assertSame('Updated label', $media->getCustomProperty('label'));
        $this->assertSame(90, $media->getCustomProperty('bearing'));
    }

    public function test_deleting_a_photo_removes_it_from_the_collection(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();
        $media = $station->addMediaFromString(
            (string) UploadedFile::fake()->image('a.jpg', 800, 600)->get(),
        )->usingFileName('a.jpg')->toMediaCollection('photos');

        $response = $this->actingAs($user)->delete(
            route('admin.stations.photos.destroy', ['station' => $station, 'media' => $media->uuid]),
        );

        $response->assertRedirect();
        $this->assertCount(0, $station->fresh()->getMedia('photos'));
    }
}
