<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Station;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Attaches placeholder site photos and a panorama to a handful of demo
 * stations. There is no real GCP monument photography available for this
 * project, so these are generated solid-colour JPEGs, each labelled with
 * free text — enough to exercise every path in the Photos screen (hero,
 * carousel, compass rose, 360° viewer) end to end. A real admin upload
 * flow replaces this entirely in Phase 08. Photos carry a free-text label
 * only, not a fixed type/category — explicit user request — so these
 * three labels are just descriptive strings, not enum cases.
 */
class DemoPhotoSeeder extends Seeder
{
    private const array TARGET_CODES = ['LPT2-GCP-015', 'LPT2-GCP-001', 'LPT2-GCP-010'];

    public function run(): void
    {
        $tempDir = storage_path('app/demo-photos');
        File::ensureDirectoryExists($tempDir);

        $stations = Station::query()->whereIn('code', self::TARGET_CODES)->get();

        foreach ($stations as $station) {
            $this->attachPhoto($station, $tempDir, 'eye-level', 'Eye-Level Approach', '#2b6cb0', 145);
            $this->attachPhoto($station, $tempDir, 'top-down', 'Top-Down (Sky Visibility)', '#2f855a', null);
            $this->attachPhoto($station, $tempDir, 'close-up', 'Close-Up (Monument)', '#6b46c1', null);
            $this->attachPanorama($station, $tempDir);
        }

        File::deleteDirectory($tempDir);
    }

    private function attachPhoto(Station $station, string $tempDir, string $slug, string $label, string $hexColor, ?int $bearing): void
    {
        $width = 1200;
        $height = 900;
        $path = "{$tempDir}/{$station->code}-{$slug}.jpg";

        $this->renderPlaceholder($path, $width, $height, $hexColor, strtoupper($label));

        $station->addMedia($path)
            ->withCustomProperties([
                'label' => $label,
                'bearing' => $bearing,
                'captured_at' => now()->subMonths(random_int(1, 6))->toDateString(),
                'width' => $width,
                'height' => $height,
            ])
            ->toMediaCollection('photos');
    }

    private function attachPanorama(Station $station, string $tempDir): void
    {
        $width = 2048;
        $height = 1024;
        $path = "{$tempDir}/{$station->code}-panorama.jpg";

        $this->renderPlaceholder($path, $width, $height, '#553c9a', '360 PANORAMA');

        $station->addMedia($path)
            ->withCustomProperties([
                'initial_yaw' => 0,
                'initial_pitch' => 0,
                'hfov' => 110,
            ])
            ->toMediaCollection('panoramas');
    }

    private function renderPlaceholder(string $path, int $width, int $height, string $hexColor, string $label): void
    {
        $image = imagecreatetruecolor(max(1, $width), max(1, $height));

        if ($image === false) {
            throw new \RuntimeException("Failed to allocate a {$width}x{$height} placeholder image.");
        }

        /** @var array{int, int, int} $rgb */
        $rgb = sscanf($hexColor, '#%02x%02x%02x') ?: [0, 0, 0];
        $clamp = fn (int $channel): int => min(255, max(0, $channel));
        [$r, $g, $b] = array_map($clamp, $rgb);

        $background = imagecolorallocate($image, $r, $g, $b);
        $white = imagecolorallocate($image, 255, 255, 255);

        if ($background === false || $white === false) {
            throw new \RuntimeException('Failed to allocate colours for the placeholder image.');
        }

        imagefill($image, 0, 0, $background);

        $fontSize = 5;
        $textWidth = imagefontwidth($fontSize) * strlen($label);
        $x = (int) (($width - $textWidth) / 2);
        $y = (int) ($height / 2);
        imagestring($image, $fontSize, $x, $y, $label, $white);

        imagejpeg($image, $path, 85);
        imagedestroy($image);
    }
}
