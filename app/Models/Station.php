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

/**
 * A physical GCP monument. The aggregate root of the dossier.
 *
 * @property int $id
 * @property string $public_id
 * @property string $code
 * @property string $highway
 * @property string|null $section
 * @property string $km
 * @property Direction $direction
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
final class Station extends Model
{
    /** @use HasFactory<StationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'highway', 'section', 'km', 'direction', 'monument_type',
        'installed_at', 'status', 'description', 'access_password', 'is_published',
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
}
