<?php

declare(strict_types=1);

namespace App\Actions\Qr;

use App\Models\Station;
use App\Services\QrUrlBuilder;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Cache;

/**
 * See plan/phases/phase-08-admin-qr.md M8.4. Error correction level High
 * (30%) is deliberate, not a default left unchanged: these codes live
 * outdoors on a highway shoulder, exposed to grime, sun, and scratches,
 * and a code that fails to scan means a wasted site visit.
 */
final readonly class GenerateStationQr
{
    public function __construct(
        private QrUrlBuilder $urlBuilder,
    ) {}

    public function __invoke(Station $station, string $format = 'png'): QrImage
    {
        $key = 'qr:'.$station->public_id.':'.$format.':'.$this->configFingerprint();

        // Cache a plain array, never a QrImage instance. The `database`
        // cache store's default config('cache.serializable_classes') is
        // `false`, which tells PHP's unserialize() to disallow every
        // class — not just GdImage — so *any* cached object silently
        // comes back as __PHP_Incomplete_Class on the next (cache-hit)
        // read. Arrays of scalars are unaffected by that restriction,
        // so the object is rebuilt fresh from the array on every call
        // instead of ever being what gets serialized.
        $data = Cache::remember($key, now()->addDay(), fn () => $this->build($station, $format));

        return new QrImage($data['content'], $data['mimeType'], $data['dataUri']);
    }

    /**
     * @return array{content: string, mimeType: string, dataUri: string}
     */
    private function build(Station $station, string $format): array
    {
        $size = (int) config('dossier.qr.size');
        $logoPath = config('dossier.qr.logo_path');

        $builder = new Builder(
            writer: $format === 'svg' ? new SvgWriter : new PngWriter,
            data: $this->urlBuilder->forStation($station),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: (int) config('dossier.qr.margin'),
            logoPath: $logoPath ?: '',
            logoResizeToWidth: $logoPath ? (int) ($size * 0.18) : null,
        );

        $result = $builder->build();

        return [
            'content' => $result->getString(),
            'mimeType' => $result->getMimeType(),
            'dataUri' => $result->getDataUri(),
        ];
    }

    /**
     * Changing DOSSIER_BASE_URL, DOSSIER_ROUTE_PREFIX, or any QR_* setting
     * must invalidate every cached QR image automatically — this
     * fingerprint is part of every cache key, so a stale entry is simply
     * never looked up again rather than needing an explicit cache flush.
     */
    private function configFingerprint(): string
    {
        return md5(json_encode([
            config('dossier.base_url'),
            config('dossier.route_prefix'),
            config('dossier.qr'),
        ]) ?: '');
    }
}
