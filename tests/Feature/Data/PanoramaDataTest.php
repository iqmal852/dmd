<?php

declare(strict_types=1);

namespace Tests\Feature\Data;

use App\Data\PanoramaData;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanoramaDataTest extends TestCase
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

    public function test_from_maps_the_initial_view_properties(): void
    {
        $station = Station::factory()->create();

        $media = $station->addMediaFromString($this->fakeJpegBytes())
            ->usingFileName('panorama.jpg')
            ->withCustomProperties(['initial_yaw' => 45, 'initial_pitch' => -5, 'hfov' => 110])
            ->toMediaCollection('panoramas');

        $data = PanoramaData::from($media->fresh());

        $this->assertSame(45.0, $data->initialYaw);
        $this->assertSame(-5.0, $data->initialPitch);
        $this->assertSame(110, $data->hfov);
        $this->assertNotEmpty($data->url);
    }

    public function test_from_defaults_the_initial_view_when_custom_properties_are_absent(): void
    {
        $station = Station::factory()->create();

        $media = $station->addMediaFromString($this->fakeJpegBytes())
            ->usingFileName('panorama.jpg')
            ->toMediaCollection('panoramas');

        $data = PanoramaData::from($media->fresh());

        $this->assertSame(0.0, $data->initialYaw);
        $this->assertSame(0.0, $data->initialPitch);
        $this->assertSame(100, $data->hfov);
    }
}
