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
        Schema::create('download_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();

            // No FK to `media` yet: spatie/laravel-medialibrary's media table doesn't
            // exist until Phase 06/07. A follow-up migration there adds the
            // constraint (set null on delete) once it does.
            $table->unsignedBigInteger('media_id')->nullable();

            // SHA-256 of IP + app key — never the raw address. See plan/02-data-model.md §6.
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('downloaded_at');

            $table->index('media_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_logs');
    }
};
