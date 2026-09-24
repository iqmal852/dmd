<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Direction;
use App\Enums\StationStatus;
use App\Models\Station;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * The real GCP dataset — 136 ground control points spanning Perlis to
 * Johor (sections N1-N7, C1-C6, S1-S6, SPDH), supplied as
 * plan/GCP_GDM2000.xls and pre-converted to
 * database/seeders/data/plus_gcp_stations.csv (see that file's own header
 * for the exact source columns). Sourced from PLUS's own GIS export
 * (hence the file/class naming), but every one of these monuments
 * belongs to the LPT2 highway specifically — `highway` and `code` are
 * set to LPT2 accordingly, distinct from `BRAND_CLIENT=PLUS`
 * (config/dossier.php), which is the operator/client, not the highway.
 * Replaces the earlier all-fake DemoStationSeeder as this app's actual
 * production/dev data — see the plan/STATUS.md deviation log.
 *
 * The source's Latitude/Longitude columns in the original .csv export
 * were truncated to bare integers and useless; the .xls export has full
 * precision and is what database/seeders/data/plus_gcp_stations.csv was
 * built from. Spot-checked against a real-world location before trusting
 * it: row 1 ("Jitra North Interchange", Kedah) is 6.2787°N, 100.4260°E,
 * which is genuinely where Jitra is.
 */
class PlusGcpStationSeeder extends Seeder
{
    private const string DATA_FILE = __DIR__.'/data/plus_gcp_stations.csv';

    public function run(): void
    {
        $rows = $this->readCsv();

        foreach ($rows as $sequence => $row) {
            $station = Station::create([
                'code' => sprintf('LPT2-GCP-%03d', $sequence + 1),
                'gcp_reference' => $row['GCP'],
                'highway' => 'LPT2',
                'section' => $row['Section'] !== '' ? $row['Section'] : null,
                'location' => $row['Location'] !== '' ? $row['Location'] : null,
                'km' => $row['KM_Marker'],
                'direction' => $this->mapDirection($row['Bound'])->value,
                'facility_type' => $row['Bound'] !== '' ? $row['Bound'] : null,
                // The source dataset has no field for what kind of physical
                // marker this is — every row is simply "a GCP" in GIS terms.
                // The specific field survey ID (GP91, TBM03, ...) is kept
                // verbatim in `gcp_reference` above rather than guessed at
                // here.
                'monument_type' => 'Ground Control Point',
                'status' => StationStatus::Active->value,
                'description' => $row['Remark'] !== '' ? $row['Remark'] : null,
                'is_published' => true,
            ]);

            // The source gives one "Elevation" figure, not separate
            // ellipsoidal/orthometric measurements — both columns are
            // populated with it rather than leaving one blank or inventing
            // a geoid separation the dataset doesn't provide. Flagged here
            // for whoever has the real split figures later.
            $station->coordinateSet()->create([
                'latitude' => $row['Latitude'],
                'longitude' => $row['Longitude'],
                'ellipsoidal_height' => $row['Elevation'],
                'easting' => $row['Easting'],
                'northing' => $row['Northing'],
                'orthometric_height' => $row['Elevation'],
                'geoid_model' => 'MyGEOID',
            ]);

            // Deliberately no Specification row: the source dataset has no
            // observation-method/QC data at all (that's a separate survey
            // record this GIS export never carried) — the dossier's Specs
            // panel already renders a correct empty state for a station
            // with no specification(), same as DemoStationSeeder's
            // INCOMPLETE_STATION_NUMBERS did.
        }
    }

    /**
     * @return list<array<string, string>>
     */
    private function readCsv(): array
    {
        if (! is_file(self::DATA_FILE)) {
            throw new RuntimeException('Missing '.self::DATA_FILE.' — see plan/GCP_GDM2000.xls.');
        }

        $handle = fopen(self::DATA_FILE, 'r');

        if ($handle === false) {
            throw new RuntimeException('Could not open '.self::DATA_FILE);
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            throw new RuntimeException(self::DATA_FILE.' has no header row.');
        }

        /** @var list<string> $header */
        $header = array_map(fn (?string $value): string => trim($value ?? ''), $header);

        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            /** @var array<string, string> $row */
            $row = array_combine($header, array_map(fn (?string $value): string => trim($value ?? ''), $line));
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * The source's "Bound" column is only sometimes a direction — see
     * facility_type above for the raw value in every case. Suffix-matched
     * rather than exact-matched so compound values like "Layby NB" or
     * "Toll Plaza EB" still yield a real direction instead of falling
     * back to "both".
     */
    private function mapDirection(string $bound): Direction
    {
        return match (true) {
            $bound === 'NB' || str_ends_with($bound, ' NB') => Direction::Northbound,
            $bound === 'SB' || str_ends_with($bound, ' SB') => Direction::Southbound,
            $bound === 'EB' || str_ends_with($bound, ' EB') => Direction::Eastbound,
            $bound === 'WB' || str_ends_with($bound, ' WB') => Direction::Westbound,
            default => Direction::Both,
        };
    }
}
