<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DownloadLog;
use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DownloadLog>
 */
class DownloadLogFactory extends Factory
{
    protected $model = DownloadLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'station_id' => Station::factory(),
            'media_id' => null,
            'ip_hash' => hash('sha256', $this->faker->ipv4()),
            'user_agent' => $this->faker->userAgent(),
            'downloaded_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
