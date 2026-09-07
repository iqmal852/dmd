<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deferred from Phase 01 (see plan/02-data-model.md §6): the `media` table
 * didn't exist until spatie/laravel-medialibrary was installed in this
 * phase. Now that it does, download_logs.media_id gets its real
 * foreign key — set null on delete, since a download log documents that a
 * download happened even if the file is later removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('download_logs', function (Blueprint $table): void {
            $table->foreign('media_id')->references('id')->on('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('download_logs', function (Blueprint $table): void {
            $table->dropForeign(['media_id']);
        });
    }
};
