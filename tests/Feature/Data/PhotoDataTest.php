<?php

declare(strict_types=1);

namespace Tests\Feature\Data;

use App\Data\PhotoData;
use App\Enums\PhotoType;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
            ->usingFileName('eye_level.jpg')
            ->withCustomProperties([
                'photo_type' => PhotoType::EyeLevel->value,
                'caption' => 'Facing the highway',
                'bearing' => 145,
                'captured_at' => '2026-01-15',
                'width' => 1200,
                'height' => 900,
            ])
            ->toMediaCollection('photos');

        $data = PhotoData::from($media->fresh());

        $this->assertSame((string) $media->uuid, $data->id);
        $this->assertSame(PhotoType::EyeLevel->value, $data->type);
        $this->assertSame(PhotoType::EyeLevel->label(), $data->typeLabel);
        $this->assertSame('Facing the highway', $data->caption);
        $this->assertSame(145, $data->bearing);
        $this->assertSame('2026-01-15', $data->capturedAt);
        $this->assertSame(1200, $data->width);
        $this->assertSame(900, $data->height);
    }

    public function test_from_falls_back_to_the_type_label_when_no_caption_is_set(): void
    {
        $station = Station::factory()->create();

        $media = $station->addMediaFromString($this->fakeJpegBytes())
            ->usingFileName('close_up.jpg')
            ->withCustomProperties(['photo_type' => PhotoType::CloseUp->value])
            ->toMediaCollection('photos');

        $data = PhotoData::from($media->fresh());

        $this->assertSame(PhotoType::CloseUp->label(), $data->caption);
    }

    public function test_from_builds_a_two_candidate_srcset_from_the_thumb_and_preview_conversions(): void
    {
        $station = Station::factory()->create();

        $media = $station->addMediaFromString($this->fakeJpegBytes())
            ->usingFileName('top_down.jpg')
            ->withCustomProperties(['photo_type' => PhotoType::TopDown->value])
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
            ->usingFileName('eye_level.jpg')
            ->withCustomProperties(['photo_type' => PhotoType::EyeLevel->value])
            ->toMediaCollection('photos');

        $data = PhotoData::from($media->fresh());

        $this->assertNull($data->bearing);
        $this->assertNull($data->capturedAt);
    }

    public function test_from_defaults_dimensions_when_absent(): void
    {
        $station = Station::factory()->create();

        $media = $station->addMediaFromString($this->fakeJpegBytes())
            ->usingFileName('eye_level.jpg')
            ->withCustomProperties(['photo_type' => PhotoType::EyeLevel->value])
            ->toMediaCollection('photos');

        $data = PhotoData::from($media->fresh());

        $this->assertSame(1200, $data->width);
        $this->assertSame(800, $data->height);
    }
}
