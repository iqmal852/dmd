<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Data\Admin\CoordinateSetFormData;
use App\Data\Admin\DocumentFormData;
use App\Data\Admin\PanoramaFormData;
use App\Data\Admin\PhotoFormData;
use App\Data\Admin\SpecificationFormData;
use App\Data\Admin\StationFormData;
use App\Enums\Direction;
use App\Enums\DocumentType;
use App\Enums\StationStatus;
use App\Http\Controllers\Controller;
use App\Models\Station;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The full edit screen: station details, coordinates, specification, and
 * media, one page with tabs rather than five separate round trips. See
 * plan/phases/phase-08-admin-qr.md M8.3/M8.6/M8.7.
 */
class AdminStationEditController extends Controller
{
    public function __invoke(Station $station): Response
    {
        $station->loadMissing(['coordinateSet', 'specification', 'media']);

        return Inertia::render('admin/stations/form', [
            'station' => StationFormData::from($station),
            'coordinateSet' => $station->coordinateSet !== null
                ? CoordinateSetFormData::from($station->coordinateSet)
                : null,
            'specification' => $station->specification !== null
                ? SpecificationFormData::from($station->specification)
                : null,
            'photos' => $station->getMedia('photos')
                ->map(fn ($media) => PhotoFormData::from($media))
                ->values(),
            'panorama' => $station->getFirstMedia('panoramas') !== null
                ? PanoramaFormData::from($station->getFirstMedia('panoramas'))
                : null,
            'documents' => $station->getMedia('documents')
                ->filter(fn ($media) => $media->getCustomProperty('document_type') !== null)
                ->map(fn ($media) => DocumentFormData::from($media))
                ->values(),
            'directionOptions' => collect(Direction::cases())
                ->map(fn (Direction $direction) => ['value' => $direction->value, 'label' => $direction->label()]),
            'statusOptions' => collect(StationStatus::cases())
                ->map(fn (StationStatus $status) => ['value' => $status->value, 'label' => $status->label()]),
            'documentTypeOptions' => collect(DocumentType::cases())
                ->map(fn (DocumentType $type) => ['value' => $type->value, 'label' => $type->label()]),
        ]);
    }
}
