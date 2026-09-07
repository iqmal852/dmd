<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Direction;
use App\Enums\StationStatus;
use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Station>
 */
class StationFactory extends Factory
{
    protected $model = Station::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = $this->faker->unique()->numberBetween(1, 9999);

        return [
            'code' => sprintf('LPT2-GCP-%03d', $number),
            'highway' => 'LPT2',
            'section' => $this->faker->randomElement(['E1', 'E2', 'E3', 'E4']),
            'km' => $this->faker->randomFloat(3, 300, 340),
            'direction' => $this->faker->randomElement(Direction::cases())->value,
            'monument_type' => 'Concrete Block',
            'installed_at' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
            'status' => StationStatus::Active->value,
            'description' => null,
            'access_password' => null,
            'is_published' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => StationStatus::Active->value]);
    }

    public function damaged(): static
    {
        return $this->state(['status' => StationStatus::Damaged->value]);
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }
}
