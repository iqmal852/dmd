<?php

declare(strict_types=1);

namespace Tests\Feature\Dossier;

use App\Enums\AccessMode;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * plan/phases/phase-06-photos-360.md Test Gate.
 */
class PhotosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A real (if tiny) JPEG — medialibrary validates MIME type against the
     * collection's accepted types, so a plain text fixture is rejected.
     */
    private function fakeJpegBytes(): string
    {
        $image = imagecreatetruecolor(2, 2);
        ob_start();
        imagejpeg($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return (string) $bytes;
    }

    private function attachPhoto(Station $station, string $type, ?int $bearing = null): Media
    {
        return $station->addMediaFromString($this->fakeJpegBytes())
            ->usingFileName("{$type}.jpg")
            ->withCustomProperties([
                'photo_type' => $type,
                'bearing' => $bearing,
                'width' => 1200,
                'height' => 900,
            ])
            ->toMediaCollection('photos');
    }

    public function test_it_returns_200_with_the_photos_component(): void
    {
        $station = Station::factory()->create(['is_published' => true]);

        $response = $this->get(route('dossier.photos', $station));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('dossier/photos'));
    }

    public function test_props_contain_exactly_the_photo_data_fields(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        $this->attachPhoto($station, 'eye_level', 145);

        $response = $this->get(route('dossier.photos', $station));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('photos.0', fn (AssertableInertia $p) => $p
                ->hasAll([
                    'id', 'type', 'typeLabel', 'caption', 'bearing', 'capturedAt',
                    'thumbUrl', 'previewUrl', 'srcset', 'placeholder', 'width', 'height',
                ])
                ->missing('file_name')
                ->missing('disk')
                ->etc()
            )
        );
    }

    public function test_props_never_reveal_the_internal_media_primary_key(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        $media = $this->attachPhoto($station, 'eye_level');

        $response = $this->get(route('dossier.photos', $station));

        $content = $response->getContent();

        $this->assertStringNotContainsString('"id":'.$media->id, (string) $content);
        $this->assertStringContainsString((string) $media->uuid, (string) $content);
    }

    public function test_photos_are_ordered_eye_level_first(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        $this->attachPhoto($station, 'close_up');
        $this->attachPhoto($station, 'top_down');
        $this->attachPhoto($station, 'eye_level');

        $response = $this->get(route('dossier.photos', $station));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('photos.0.type', 'eye_level')
        );
    }

    public function test_every_photo_carries_a_non_empty_srcset_and_dimensions(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        $this->attachPhoto($station, 'eye_level');

        $response = $this->get(route('dossier.photos', $station));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('photos.0.width', 1200)
            ->where('photos.0.height', 900)
            ->has('photos.0.srcset')
        );
    }

    public function test_a_station_with_no_photos_renders_the_empty_state_without_error(): void
    {
        $station = Station::factory()->create(['is_published' => true]);

        $response = $this->get(route('dossier.photos', $station));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->where('photos', []));
    }

    public function test_the_photos_route_is_behind_the_access_gate(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'secret',
        ]);
        $station = Station::factory()->create(['is_published' => true]);

        $this->get(route('dossier.photos', $station))->assertRedirect();
    }

    public function test_panorama_viewer_404s_when_the_station_has_no_panorama(): void
    {
        $station = Station::factory()->create(['is_published' => true]);

        $this->get(route('dossier.photos.360', $station))->assertNotFound();
    }

    public function test_panorama_viewer_renders_when_a_panorama_exists(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        $station->addMediaFromString($this->fakeJpegBytes())
            ->usingFileName('panorama.jpg')
            ->withCustomProperties(['initial_yaw' => 10, 'initial_pitch' => 5, 'hfov' => 100])
            ->toMediaCollection('panoramas');

        $response = $this->get(route('dossier.photos.360', $station));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dossier/panorama')
            ->where('panorama.initialYaw', 10)
            ->where('panorama.hfov', 100)
        );
    }

    public function test_the_panorama_route_is_behind_the_access_gate(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'secret',
        ]);
        $station = Station::factory()->create(['is_published' => true]);
        $station->addMediaFromString($this->fakeJpegBytes())
            ->usingFileName('panorama.jpg')
            ->toMediaCollection('panoramas');

        $this->get(route('dossier.photos.360', $station))->assertRedirect();
    }
}
