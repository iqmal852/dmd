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
        Schema::create('specifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('station_id')->unique()->constrained()->cascadeOnDelete();

            // GNSS observation. Nullable throughout except station_id/qc_status:
            // real survey records arrive incomplete and the UI must render that.
            $table->string('observation_method', 50)->nullable();
            $table->smallInteger('observation_minutes')->nullable();
            $table->smallInteger('satellite_count')->nullable();
            $table->decimal('pdop_max', 4, 2)->nullable();
            $table->smallInteger('elevation_cutoff_deg')->nullable();
            $table->string('antenna_type', 100)->nullable();
            $table->decimal('antenna_height', 6, 3)->nullable();
            $table->string('antenna_reference_point', 50)->nullable();

            // Accuracy (RMS)
            $table->decimal('horizontal_rms_mm', 6, 2)->nullable();
            $table->decimal('vertical_rms_mm', 6, 2)->nullable();
            $table->string('qc_status', 20)->default('pending');
            $table->date('verified_at')->nullable();
            $table->string('verified_by', 100)->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specifications');
    }
};
