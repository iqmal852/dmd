<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The team's extra logins, on top of the MySpatial admin from AdminUserSeeder.
 * The app has no roles — every account here gets the same access as that admin.
 *
 * Idempotent (matched by email) and deliberately NOT part of the production
 * deploy, which only runs AdminUserSeeder (docs/DEPLOYMENT.md). They share a
 * known development password, so don't seed them on a live server.
 */
class TeamUserSeeder extends Seeder
{
    private const string PASSWORD = 'secret123';

    /**
     * @var array<string, string> email => name
     */
    private const array USERS = [
        'admin.plus@myspatial.com.my' => 'admin plus',
        'admin.mssb@myspatial.com.my' => 'admin mssb',
        'user.plus@myspatial.com.my' => 'user plus',
        'user.lpt@myspatial.com.my' => 'user lpt',
    ];

    public function run(): void
    {
        foreach (self::USERS as $email => $name) {
            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => self::PASSWORD, // hashed by User's 'hashed' cast
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
