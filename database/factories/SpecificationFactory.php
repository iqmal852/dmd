<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QcStatus;
use App\Models\Specification;
use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Specification>
 */
class SpecificationFactory extends Factory
{
    protected $model = Specification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'station_id' => Station::factory(),
            'observation_method' => 'Static / RTK',
            'observation_minutes' => $this->faker->numberBetween(60, 180),
            'satellite_count' => $this->faker->numberBetween(8, 24),
            'pdop_max' => $this->faker->randomFloat(2, 1, 3),
            'elevation_cutoff_deg' => 15,
            'antenna_type' => 'Geodetic L1/L2',
            'antenna_height' => $this->faker->randomFloat(3, 1, 2),
            'antenna_reference_point' => 'Bottom of ARP',
            'horizontal_rms_mm' => $this->faker->randomFloat(2, 5, 10),
            'vertical_rms_mm' => $this->faker->randomFloat(2, 10, 15),
            'qc_status' => QcStatus::Verified->value,
            'verified_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'verified_by' => null,
            'remarks' => null,
        ];
    }
}
