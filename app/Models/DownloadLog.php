<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\DownloadLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An audit row for one file download. Append-only — see plan/02-data-model.md §6.
 * Never stores a raw IP address, only an HMAC of it.
 *
 * @property int $id
 * @property int $station_id
 * @property int|null $media_id
 * @property string|null $ip_hash
 * @property string|null $user_agent
 * @property Carbon $downloaded_at
 */
final class DownloadLog extends Model
{
    /** @use HasFactory<DownloadLogFactory> */
    use HasFactory, MassPrunable;

    public const ?string UPDATED_AT = null;

    public const ?string CREATED_AT = null;

    protected $fillable = [
        'station_id', 'media_id', 'ip_hash', 'user_agent', 'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Station, $this>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where(
            'downloaded_at', '<', now()->subDays((int) config('dossier.downloads.retention_days')),
        );
    }
}
