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
        Schema::create('stations', function (Blueprint $table): void {
            $table->id();

            // What the QR code actually encodes — a ULID, non-enumerable, immutable
            // once printed onto a physical plate. See Station::getRouteKeyName().
            $table->char('public_id', 26)->unique();

            // Human identifier shown on screen, e.g. "LPT2-GCP-015". Never routed on.
            $table->string('code', 50);

            $table->string('highway', 50);
            $table->string('section', 20)->nullable();
            $table->decimal('km', 10, 3);
            $table->string('direction', 20);
            $table->string('monument_type', 50);
            $table->date('installed_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();

            // Per-station password override. Hashed via the model's 'hashed' cast.
            // Null means "defer to the global DOSSIER_ACCESS_MODE / _PASSWORD".
            $table->string('access_password')->nullable();

            $table->boolean('is_published')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['highway', 'km']);
        });

        // Case-insensitive uniqueness on code: "LPT2-GCP-001" and "lpt2-gcp-001"
        // must not both exist. Not expressible via the fluent Blueprint API.
        DB::statement('create unique index stations_code_lower_unique on stations (lower(code))');

        // Partial index: the public read path only ever queries published, non-deleted
        // stations, so index exactly that subset rather than the whole table.
        DB::statement('create index stations_published_idx on stations (is_published) where deleted_at is null');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
