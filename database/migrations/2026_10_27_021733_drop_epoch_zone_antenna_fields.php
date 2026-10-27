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
        Schema::table('coordinate_sets', function (Blueprint $table): void {
            $table->dropColumn(['zone', 'epoch']);
        });

        Schema::table('specifications', function (Blueprint $table): void {
            $table->dropColumn(['antenna_type', 'antenna_height', 'antenna_reference_point']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coordinate_sets', function (Blueprint $table): void {
            $table->string('zone', 20)->nullable();
            $table->string('epoch', 20)->nullable();
        });

        Schema::table('specifications', function (Blueprint $table): void {
            $table->string('antenna_type', 100)->nullable();
            $table->decimal('antenna_height', 6, 3)->nullable();
            $table->string('antenna_reference_point', 50)->nullable();
        });
    }
};
