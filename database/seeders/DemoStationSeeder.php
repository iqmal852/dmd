<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Direction;
use App\Enums\QcStatus;
use App\Enums\StationStatus;
use App\Models\CoordinateSet;
use App\Models\Specification;
use App\Models\Station;
use Illuminate\Database\Seeder;

/**
 * 25 LPT2 stations — the coverage number stated on Picture1.png's footer strip
 * ("25 GCP LPT2 COVERAGE"). LPT2-GCP-015 is seeded with the exact values shown
 * across every panel of the poster and is the reference fixture used by
 * browser tests and demos: any regression against the poster shows up as a
 * failure against this one record. See plan/02-data-model.md §9.
 */
class DemoStationSeeder extends Seeder
{
    private const int TOTAL_STATIONS = 25;

    private const int REFERENCE_STATION_NUMBER = 15;

    /** Deliberately incomplete records, to exercise empty-state rendering. */
    private const array INCOMPLETE_STATION_NUMBERS = [7, 22];

    public function run(): void
    {
        for ($number = 1; $number <= self::TOTAL_STATIONS; $number++) {
            if ($number === self::REFERENCE_STATION_NUMBER) {
                $this->seedReferenceStation();

                continue;
            }

            $this->seedGeneratedStation($number);
        }
    }

    /**
     * LPT2-GCP-015 — every value here is transcribed directly from Picture1.png.
     */
    private function seedReferenceStation(): void
    {
        $station = Station::factory()->create([
            'code' => 'LPT2-GCP-015',
            'highway' => 'LPT2',
            'section' => 'E1',
            'km' => '318.200',
            'direction' => Direction::Westbound->value,
            'monument_type' => 'Concrete Block',
            'installed_at' => '2025-03-15',
            'status' => StationStatus::Active->value,
            'is_published' => true,
        ]);

        $station->coordinateSet()->create([
            'latitude' => '4.27412582',
            'longitude' => '103.43658211',
            'ellipsoidal_height' => '128.346',
            'easting' => '428765.212',
            'northing' => '472318.678',
            'orthometric_height' => '112.436',
            'geoid_model' => 'MyGEOID',
        ]);

        $station->specification()->create([
            'observation_method' => 'Static / RTK',
            'observation_minutes' => 120,
            'satellite_count' => 18,
            'pdop_max' => '1.60',
            'elevation_cutoff_deg' => 15,
            'antenna_type' => 'Geodetic L1/L2',
            'antenna_height' => '1.532',
            'antenna_reference_point' => 'Bottom of ARP',
            'horizontal_rms_mm' => '10.00',
            'vertical_rms_mm' => '15.00',
            'qc_status' => QcStatus::Verified->value,
        ]);
    }

    private function seedGeneratedStation(int $number): void
    {
        $code = sprintf('LPT2-GCP-%03d', $number);

        // Spread chainages across KM 300-340 in installation order.
        $km = number_format(300 + ($number * 1.6), 3, '.', '');

        $station = Station::factory()->create([
            'code' => $code,
            'km' => $km,
            'direction' => $number % 2 === 0 ? Direction::Eastbound->value : Direction::Westbound->value,
            'status' => $number === 3 ? StationStatus::Damaged->value : StationStatus::Active->value,
        ]);

        if (in_array($number, self::INCOMPLETE_STATION_NUMBERS, true)) {
            // Left without a coordinate set or specification on purpose.
            return;
        }

        // `for()`, not `make()`/`save()`: without it the factory's own
        // `station_id => Station::factory()` default would create an orphan
        // Station to satisfy the foreign key before we overwrite it.
        CoordinateSet::factory()->for($station)->create();
        Specification::factory()->for($station)->create();
    }
}
