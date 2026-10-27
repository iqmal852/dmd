<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Failure modes of the admin station forms — the happy paths live in the
 * per-form suites. Each invalid value must come back as a session error on
 * exactly that field (what the Inertia form renders under the input) and
 * must not write anything.
 */
class AdminFormValidationTest extends TestCase
{
    use RefreshDatabase;

    private function station(): array
    {
        return [
            'code' => 'LPT2-GCP-999',
            'highway' => 'LPT2',
            'km' => '100.500',
            'direction' => 'northbound',
            'monument_type' => 'GCP',
            'status' => 'active',
        ];
    }

    private function coordinates(): array
    {
        return [
            'latitude' => '4.27412582',
            'longitude' => '101.12345678',
            'ellipsoidal_height' => '45.123',
            'easting' => '500000.000',
            'northing' => '470000.000',
            'orthometric_height' => '30.456',
            'geoid_model' => 'MyGEOID',
        ];
    }

    private function specification(): array
    {
        return ['qc_status' => 'verified'];
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidStations(): array
    {
        return [
            'code missing' => [['code' => ''], 'code'],
            'code too long' => [['code' => str_repeat('A', 51)], 'code'],
            'highway missing' => [['highway' => ''], 'highway'],
            'km missing' => [['km' => ''], 'km'],
            'km not a number' => [['km' => 'abc'], 'km'],
            'km out of range' => [['km' => '2001'], 'km'],
            'direction invalid' => [['direction' => 'sideways'], 'direction'],
            'monument type missing' => [['monument_type' => ''], 'monument_type'],
            'status invalid' => [['status' => 'gone'], 'status'],
            'installed in the future' => [['installed_at' => now()->addDay()->toDateString()], 'installed_at'],
            'installed not a date' => [['installed_at' => 'yesterday-ish'], 'installed_at'],
            'password too short' => [['access_password' => 'short'], 'access_password'],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('invalidStations')]
    public function test_creating_a_station_rejects_invalid_input(array $override, string $field): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('admin.stations.store'), array_merge($this->station(), $override));

        $response->assertSessionHasErrors($field);
        $this->assertDatabaseCount('stations', 0);
    }

    public function test_updating_a_station_rejects_invalid_input_and_keeps_the_row(): void
    {
        $station = Station::factory()->create(['km' => '10.000']);

        $response = $this->actingAs(User::factory()->create())
            ->put(route('admin.stations.update', $station), array_merge($this->station(), ['km' => '9999']));

        $response->assertSessionHasErrors('km');
        $this->assertSame('10.000', $station->fresh()->km);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidCoordinates(): array
    {
        return [
            'latitude missing' => [['latitude' => ''], 'latitude'],
            'latitude too high' => [['latitude' => '91'], 'latitude'],
            'longitude too low' => [['longitude' => '-181'], 'longitude'],
            'longitude not numeric' => [['longitude' => 'east'], 'longitude'],
            'ellipsoidal height missing' => [['ellipsoidal_height' => ''], 'ellipsoidal_height'],
            'easting negative' => [['easting' => '-1'], 'easting'],
            'northing missing' => [['northing' => ''], 'northing'],
            'orthometric height too high' => [['orthometric_height' => '3001'], 'orthometric_height'],
            'geoid model missing' => [['geoid_model' => ''], 'geoid_model'],
            'computed in the future' => [['computed_at' => now()->addDay()->toDateString()], 'computed_at'],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('invalidCoordinates')]
    public function test_coordinates_reject_invalid_input(array $override, string $field): void
    {
        $station = Station::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->put(route('admin.stations.coordinates.update', $station), array_merge($this->coordinates(), $override));

        $response->assertSessionHasErrors($field);
        $this->assertNull($station->fresh()->coordinateSet);
    }

    public function test_valid_coordinates_are_saved(): void
    {
        $station = Station::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->put(route('admin.stations.coordinates.update', $station), $this->coordinates());

        $response->assertSessionHasNoErrors();
        $this->assertNotNull($station->fresh()->coordinateSet);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidSpecifications(): array
    {
        return [
            'qc status invalid' => [['qc_status' => 'maybe'], 'qc_status'],
            'minutes negative' => [['observation_minutes' => '-1'], 'observation_minutes'],
            'minutes not an integer' => [['observation_minutes' => '1.5'], 'observation_minutes'],
            'satellites too many' => [['satellite_count' => '61'], 'satellite_count'],
            'pdop too high' => [['pdop_max' => '100'], 'pdop_max'],
            'elevation cutoff too high' => [['elevation_cutoff_deg' => '91'], 'elevation_cutoff_deg'],
            'horizontal rms overflows the column' => [['horizontal_rms_mm' => '10000'], 'horizontal_rms_mm'],
            'vertical rms negative' => [['vertical_rms_mm' => '-0.5'], 'vertical_rms_mm'],
            'verified in the future' => [['verified_at' => now()->addDay()->toDateString()], 'verified_at'],
            'verified by too long' => [['verified_by' => str_repeat('a', 101)], 'verified_by'],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('invalidSpecifications')]
    public function test_specification_rejects_invalid_input(array $override, string $field): void
    {
        $station = Station::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->put(route('admin.stations.specification.update', $station), array_merge($this->specification(), $override));

        $response->assertSessionHasErrors($field);
        $this->assertNull($station->fresh()->specification);
    }

    public function test_the_largest_allowed_rms_fits_the_column(): void
    {
        $station = Station::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->put(route('admin.stations.specification.update', $station), array_merge($this->specification(), [
                'horizontal_rms_mm' => '9999.99',
                'pdop_max' => '99',
            ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame('9999.99', $station->fresh()->specification->horizontal_rms_mm);
    }
}
