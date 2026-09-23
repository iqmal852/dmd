<?php

declare(strict_types=1);

namespace Tests\Feature\Data;

use App\Data\PhotoData;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Photos carry a free-text label only — no fixed type/category — per
 * explicit user request. See App\Data\PhotoData's own docblock.
 */
class PhotoDataTest extends TestCase
{
    use RefreshDatabase;

    private function fakeJpegBytes(): string
    {
        $image = imagecreatetruecolor(2, 2);
        ob_start();
        imagejpeg($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return (string) $bytes;
    }

    public function test_from_maps_the_media_uuid_not_the_numeric_id(): void
    {
        $station = Station::factory()->create();

        $media = $station->addMediaFromString($this->fakeJpegBytes())
            ->usingFileName('photo.jpg')
            ->withCustomProperties([
                'label' => 'Facing the highway',
                'bearing' => 145,
                'captured_at' => '2026-01-15',
                'width' => 1200,
                'height' => 900,
            ])
            ->toMediaCollection('photos');

        $data = PhotoData::from($media->fresh());

        $this->assertSame((string) $media->uuid, $data->id);
        $this->assertSame('Facing the highway', $data->label);
        $this->assertSame(145, $data->bearing);
        $this->assertSame('2026-01-15', $data->capturedAt);
        $this->assertSame(1200, $data->width);
        $this->assertSame(900, $data->height);
    }

    public function test_from_falls_back_to_a_generic_label_when_none_is_set(): void
    {
        $station = Station::factory()->create();

        $media = $station->addMediaFromString($this->fakeJpegBytes())
            ->usingFileName('photo.jpg')
            ->toMediaCollection('photos');

        $data = PhotoData::from($media->fresh());

        $this->assertSame('Site Photo', $data->label);
    }

    public function test_from_builds_a_two_candidate_srcset_from_the_thumb_and_preview_conversions(): void
    {
        $station = Station::factory()->create();

        $media = $station->addMediaFromString($this->fakeJpegBytes())
            ->usingFileName('photo.jpg')
            ->toMediaCollection('photos');

        $data = PhotoData::from($media->fresh());

        $this->assertStringContainsString('320w', $data->srcset);
        $this->assertStringContainsString('1200w', $data->srcset);
        $this->assertStringContainsString($data->thumbUrl, $data->srcset);
        $this->assertStringContainsString($data->previewUrl, $data->srcset);
    }

    public function test_from_defaults_bearing_and_captured_at_to_null_when_absent(): void
    {
        $station = Station::factory()->create();

        $media = $station->addMediaFromString($this->fakeJpegBytes())
            ->usingFileName('photo.jpg')
            ->toMediaCollection('photos');

        $data = PhotoData::from($media->fresh());

        $this->assertNull($data->bearing);
        $this->assertNull($data->capturedAt);
    }

    public function test_from_defaults_dimensions_when_absent(): void
    {
        $station = Station::factory()->create();

        $media = $station->addMediaFromString($this->fakeJpegBytes())
            ->usingFileName('photo.jpg')
            ->toMediaCollection('photos');

        $data = PhotoData::from($media->fresh());

        $this->assertSame(1200, $data->width);
        $this->assertSame(800, $data->height);
    }
}
