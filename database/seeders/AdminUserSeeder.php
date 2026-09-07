<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the single admin user from config('batu.admin.*') (backed by ADMIN_NAME /
 * ADMIN_EMAIL / ADMIN_PASSWORD in .env). Idempotent: safe to run on every deploy.
 *
 * There is no registration route — see ADR-006 in plan/01-architecture.md. To
 * rotate the password afterwards use `php artisan batu:admin-password`.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('batu.admin.email');
        $password = config('batu.admin.password');

        if (blank($password)) {
            $this->command->error(
                'ADMIN_PASSWORD is not set in .env — refusing to seed an admin with no password.'
            );

            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) config('batu.admin.name'),
                'password' => (string) $password, // hashed by User's 'hashed' cast
                'email_verified_at' => now(),
            ],
        );
    }
}
