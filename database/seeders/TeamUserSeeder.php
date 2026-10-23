<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The team's extra logins, on top of the PLUS admin from AdminUserSeeder.
 * The app has no roles — every account here gets the same access as that admin.
 *
 * Idempotent (matched by username, so a changed email updates the existing
 * account instead of duplicating it) and deliberately NOT part of the production
 * deploy, which only runs AdminUserSeeder (docs/DEPLOYMENT.md). They share a
 * known development password, so don't seed them on a live server.
 */
class TeamUserSeeder extends Seeder
{
    private const string PASSWORD = 'secret123';

    /**
     * @var array<string, array{name: string, email: string}> keyed by username
     */
    private const array USERS = [
        'admin-plus' => ['name' => 'admin plus', 'email' => 'admin.plus@plus.com'],
        'admin-mssb' => ['name' => 'admin mssb', 'email' => 'admin.mssb@plus.com'],
        'user-plus' => ['name' => 'user plus', 'email' => 'user.plus@plus.com'],
        'user-mssb' => ['name' => 'user mssb', 'email' => 'user.mssb@plus.com'],
        'user-lpt' => ['name' => 'user lpt', 'email' => 'user.lpt@plus.com'],
    ];

    public function run(): void
    {
        foreach (self::USERS as $username => $account) {
            User::query()->updateOrCreate(
                ['username' => $username],
                [
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => self::PASSWORD, // hashed by User's 'hashed' cast
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
