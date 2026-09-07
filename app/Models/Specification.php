<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QcStatus;
use Carbon\Carbon;
use Database\Factories\SpecificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Panel 2b of Picture1.png: GNSS observation method and accuracy (RMS) QC record.
 * All GNSS fields are nullable except station_id/qc_status — real survey records
 * arrive incomplete. See plan/02-data-model.md §4.
 *
 * @property int $id
 * @property int $station_id
 * @property string|null $observation_method
 * @property int|null $observation_minutes
 * @property int|null $satellite_count
 * @property string|null $pdop_max
 * @property int|null $elevation_cutoff_deg
 * @property string|null $antenna_type
 * @property string|null $antenna_height
 * @property string|null $antenna_reference_point
 * @property string|null $horizontal_rms_mm
 * @property string|null $vertical_rms_mm
 * @property QcStatus $qc_status
 * @property Carbon|null $verified_at
 * @property string|null $verified_by
 * @property string|null $remarks
 */
final class Specification extends Model
{
    /** @use HasFactory<SpecificationFactory> */
    use HasFactory;

    protected $fillable = [
        'station_id', 'observation_method', 'observation_minutes', 'satellite_count',
        'pdop_max', 'elevation_cutoff_deg', 'antenna_type', 'antenna_height',
        'antenna_reference_point', 'horizontal_rms_mm', 'vertical_rms_mm',
        'qc_status', 'verified_at', 'verified_by', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'pdop_max' => 'decimal:2',
            'antenna_height' => 'decimal:3',
            'horizontal_rms_mm' => 'decimal:2',
            'vertical_rms_mm' => 'decimal:2',
            'qc_status' => QcStatus::class,
            'verified_at' => 'date',
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
