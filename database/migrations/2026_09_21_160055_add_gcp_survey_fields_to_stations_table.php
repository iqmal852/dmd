<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fields needed to import the real PLUS North-South Expressway GCP
     * dataset (plan/GCP_GDM2000.xls) without losing any of its columns —
     * see database/seeders/PlusGcpStationSeeder.php.
     */
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table): void {
            // The source data's "Location" — a state (e.g. "KEDAH") or,
            // for stations on a named spur/link road, a sub-route label
            // (e.g. "DL1- Dengkil NB"). Doesn't fit `highway`/`section`.
            $table->string('location')->nullable()->after('section');

            // The source's "Bound" column: only sometimes a direction
            // (NB/SB/EB/WB, which `direction` already captures) — the
            // rest of the time it's what's physically at this point
            // (Toll Plaza, Interchange, Rest & Service Area, Layby,
            // Vista Point, overhead bridge). Stored verbatim rather than
            // forced into the direction enum.
            $table->string('facility_type')->nullable()->after('direction');

            // The source's "GCP" column — the actual survey point ID
            // used in the field (e.g. "GP91", "TBM03"), distinct from
            // this app's own human-readable `code`.
            $table->string('gcp_reference')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table): void {
            $table->dropColumn(['location', 'facility_type', 'gcp_reference']);
        });
    }
};
