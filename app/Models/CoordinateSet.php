<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\CoordinateSetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Panel 2a of Picture1.png: WGS 84, GDM 2000 (TM), and MyGEOID for one station.
 * Every metric column is `numeric` with the exact precision shown on the poster —
 * see plan/02-data-model.md §3. Never cast these as float/double.
 *
 * @property int $id
 * @property int $station_id
 * @property string $latitude
 * @property string $longitude
 * @property string $ellipsoidal_height
 * @property string $easting
 * @property string $northing
 * @property string|null $zone
 * @property string $orthometric_height
 * @property string $geoid_model
 * @property string|null $epoch
 * @property Carbon|null $computed_at
 */
final class CoordinateSet extends Model
{
    /** @use HasFactory<CoordinateSetFactory> */
    use HasFactory;

    protected $fillable = [
        'station_id', 'latitude', 'longitude', 'ellipsoidal_height',
        'easting', 'northing', 'zone',
        'orthometric_height', 'geoid_model', 'epoch', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'ellipsoidal_height' => 'decimal:3',
            'easting' => 'decimal:3',
            'northing' => 'decimal:3',
            'orthometric_height' => 'decimal:3',
            'computed_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Station, $this>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
