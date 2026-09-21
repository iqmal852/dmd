<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Direction;
use App\Enums\StationStatus;
use Carbon\Carbon;
use Database\Factories\StationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A physical GCP monument. The aggregate root of the dossier.
 *
 * @property int $id
 * @property string $public_id
 * @property string $code
 * @property string|null $gcp_reference
 * @property string $highway
 * @property string|null $section
 * @property string|null $location
 * @property string $km
 * @property Direction $direction
 * @property string|null $facility_type
 * @property string $monument_type
 * @property Carbon|null $installed_at
 * @property StationStatus $status
 * @property string|null $description
 * @property string|null $access_password
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
final class Station extends Model implements HasMedia
{
    /** @use HasFactory<StationFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'code', 'gcp_reference', 'highway', 'section', 'location', 'km', 'direction',
        'facility_type', 'monument_type', 'installed_at', 'status', 'description',
        'access_password', 'is_published',
    ];

    /**
     * Routed on the ULID, never the numeric PK or the human code — see
     * plan/02-data-model.md §2: "QR codes are physically permanent, so the
     * identifier they encode must be immutable."
     */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected static function booted(): void
    {
        self::creating(function (Station $station): void {
            $station->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'km' => 'decimal:3',
            'installed_at' => 'date',
            'status' => StationStatus::class,
            'direction' => Direction::class,
            'is_published' => 'boolean',
            'access_password' => 'hashed',
        ];
    }

    /**
     * @return HasOne<CoordinateSet, $this>
     */
    public function coordinateSet(): HasOne
    {
        return $this->hasOne(CoordinateSet::class);
    }

    /**
     * @return HasOne<Specification, $this>
     */
    public function specification(): HasOne
    {
        return $this->hasOne(Specification::class);
    }

    /**
     * @return HasMany<DownloadLog, $this>
     */
    public function downloadLogs(): HasMany
    {
        return $this->hasMany(DownloadLog::class);
    }

    /**
     * @param  Builder<Station>  $query
     * @return Builder<Station>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<Station>  $query
     * @return Builder<Station>
     */
    public function scopeForHighway(Builder $query, string $highway): Builder
    {
        return $query->where('highway', $highway);
    }

    /**
     * Photos and panoramas are used from Phase 06; the `documents`
     * collection (as-built drawings) is registered now too since it costs
     * nothing to declare and Phase 07 will use it as-is. Panoramas get
     * no image conversions — resampling an equirectangular image breaks
     * the projection. See plan/phases/phase-06-photos-360.md M6.1.
     *
     * `documents` lives on the `local` disk (private, no `storage:link`
     * target) rather than the package default `public` disk that photos
     * and panoramas use — as-built drawings are never publicly reachable
     * by URL; every byte is served through DownloadDocumentController /
     * PreviewDocumentController instead. See
     * plan/phases/phase-07-asbuilt-files.md M7.1/M7.4.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('panoramas')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/webp']);

        $this->addMediaCollection('documents')
            ->useDisk('local')
            ->acceptsMimeTypes(['application/pdf', 'image/png', 'image/jpeg', 'application/acad', 'image/vnd.dwg', 'application/octet-stream']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Conversion-native methods (performOnCollections, queued/nonQueued)
        // come before the image-manipulation ones (fit/format/quality/etc):
        // those are magic-forwarded to Spatie\Image's ImageDriver via
        // Conversion's @mixin, which is where PHPStan's static type for the
        // chain "leaves" Conversion — calling them last keeps every
        // Conversion-specific method call correctly typed.
        $this->addMediaConversion('thumb')
            ->performOnCollections('photos')
            ->queued()
            ->fit(Fit::Crop, 320, 320)
            ->format('webp')
            ->quality(78);

        // 'documents' deliberately has no generated conversion: a preview is
        // either the original file itself (a PDF/image rendered as-is) or a
        // second, separately-uploaded media row (for DWG/DXF originals) —
        // never a derived resize. Both are streamed at full resolution
        // through PreviewDocumentController, since a resized engineering
        // drawing can hide the detail a field crew needs. See
        // plan/phases/phase-07-asbuilt-files.md M7.1.
        $this->addMediaConversion('preview')
            ->performOnCollections('photos')
            ->queued()
            ->width(1200)
            ->format('webp')
            ->quality(82);

        $this->addMediaConversion('placeholder')
            ->performOnCollections('photos')
            ->nonQueued()
            ->width(24)
            ->blur(8)
            ->format('webp');
    }
}
