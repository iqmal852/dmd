<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CoordinateSet;
use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoordinateSet>
 */
class CoordinateSetFactory extends Factory
{
    protected $model = CoordinateSet::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'station_id' => Station::factory(),
            // Roughly the LPT2 corridor in Peninsular Malaysia.
            'latitude' => $this->faker->randomFloat(8, 3.5, 5.5),
            'longitude' => $this->faker->randomFloat(8, 102.5, 104.5),
            'ellipsoidal_height' => $this->faker->randomFloat(3, 20, 200),
            'easting' => $this->faker->randomFloat(3, 300000, 500000),
            'northing' => $this->faker->randomFloat(3, 300000, 600000),
            'zone' => null,
            'orthometric_height' => $this->faker->randomFloat(3, 20, 200),
            'geoid_model' => 'MyGEOID',
            'epoch' => null,
            'computed_at' => null,
        ];
    }
}
