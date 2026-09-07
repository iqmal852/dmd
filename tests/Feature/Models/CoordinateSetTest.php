<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\CoordinateSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CoordinateSetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_survey_coordinates_without_losing_precision(): void
    {
        $coordinateSet = CoordinateSet::factory()->create([
            'latitude' => '4.27412582',
            'longitude' => '103.43658211',
            'easting' => '428765.212',
            'northing' => '472318.678',
            'ellipsoidal_height' => '128.346',
            'orthometric_height' => '112.436',
        ]);

        $fresh = $coordinateSet->fresh();

        $this->assertSame('4.27412582', $fresh->latitude);
        $this->assertSame('103.43658211', $fresh->longitude);
        $this->assertSame('428765.212', $fresh->easting);
        $this->assertSame('472318.678', $fresh->northing);
        $this->assertSame('128.346', $fresh->ellipsoidal_height);
        $this->assertSame('112.436', $fresh->orthometric_height);
    }

    /**
     * Guards against a migration silently using `double precision` instead of
     * `numeric` — that would pass the round-trip test above on small values
     * and only fail with real survey-grade precision in the field.
     */
    public function test_coordinate_columns_are_numeric_with_the_documented_precision(): void
    {
        $columns = DB::table('information_schema.columns')
            ->where('table_name', 'coordinate_sets')
            ->whereIn('column_name', [
                'latitude', 'longitude', 'ellipsoidal_height',
                'easting', 'northing', 'orthometric_height',
            ])
            ->get(['column_name', 'data_type', 'numeric_precision', 'numeric_scale'])
            ->keyBy('column_name');

        $expected = [
            'latitude' => [11, 8],
            'longitude' => [12, 8],
            'ellipsoidal_height' => [10, 3],
            'easting' => [12, 3],
            'northing' => [12, 3],
            'orthometric_height' => [10, 3],
        ];

        foreach ($expected as $column => [$precision, $scale]) {
            $row = $columns[$column];

            $this->assertSame('numeric', $row->data_type, "{$column} is not a numeric column");
            $this->assertSame($precision, (int) $row->numeric_precision, "{$column} precision mismatch");
            $this->assertSame($scale, (int) $row->numeric_scale, "{$column} scale mismatch");
        }
    }

    public function test_station_has_one_coordinate_set(): void
    {
        $coordinateSet = CoordinateSet::factory()->create();

        $this->assertTrue($coordinateSet->station->coordinateSet->is($coordinateSet));
    }
}
