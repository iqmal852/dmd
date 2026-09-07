<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coordinate_sets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('station_id')->unique()->constrained()->cascadeOnDelete();

            // WGS 84 (GPS). Precision matches Picture1.png exactly: 8 decimal places
            // on lat/lon (~1mm at the equator), 3 on heights. Never float/double —
            // see plan/01-architecture.md ADR-003 and plan/02-data-model.md §3.
            $table->decimal('latitude', 11, 8);
            $table->decimal('longitude', 12, 8);
            $table->decimal('ellipsoidal_height', 10, 3);

            // GDM 2000 (TM)
            $table->decimal('easting', 12, 3);
            $table->decimal('northing', 12, 3);
            $table->string('zone', 20)->nullable();

            // MyGEOID
            $table->decimal('orthometric_height', 10, 3);
            $table->string('geoid_model', 50)->default('MyGEOID');
            $table->string('epoch', 20)->nullable();
            $table->date('computed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coordinate_sets');
    }
};
