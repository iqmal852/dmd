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
        Schema::table('users', function (Blueprint $table): void {
            // Optional alternative to the email at login. Stored lowercase.
            $table->string('username', 50)->nullable();
        });

        // Filtered rather than ->unique(): SQL Server's plain unique index
        // allows only one NULL, and most accounts have no username.
        DB::statement('create unique index users_username_unique on users (username) where username is not null');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_username_unique');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('username');
        });
    }
};
