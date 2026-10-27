<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The filtered index references the column, so it has to go first.
        Schema::table('stations', function (Blueprint $table): void {
            $table->dropIndex('stations_published_idx');
        });

        Schema::table('stations', function (Blueprint $table): void {
            $table->dropColumn('is_published');
        });
    }

    /**
     * Reverse the migrations. Every existing station comes back published,
     * matching the "any record counts as published" behaviour it replaced.
     */
    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table): void {
            $table->boolean('is_published')->default(true);
        });

        DB::statement('create index stations_published_idx on stations (is_published) where deleted_at is null');
    }
};
