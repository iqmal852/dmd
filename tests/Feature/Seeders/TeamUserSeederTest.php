<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Models\User;
use Database\Seeders\TeamUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeamUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_team_accounts_with_the_shared_password(): void
    {
        $this->seed(TeamUserSeeder::class);

        $this->assertEqualsCanonicalizing(
            ['admin plus', 'admin mssb', 'user plus', 'user mssb', 'user lpt'],
            User::query()->pluck('name')->all(),
        );

        $user = User::query()->where('email', 'user.lpt@myspatial.com.my')->firstOrFail();
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_reseeding_does_not_duplicate_accounts(): void
    {
        $this->seed(TeamUserSeeder::class);
        $this->seed(TeamUserSeeder::class);

        $this->assertSame(5, User::query()->count());
    }

    public function test_a_seeded_account_can_log_in(): void
    {
        $this->seed(TeamUserSeeder::class);

        $this->post(route('login.store'), [
            'email' => 'admin.plus@myspatial.com.my',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticated();
    }

    public function test_a_seeded_account_can_log_in_with_its_username(): void
    {
        $this->seed(TeamUserSeeder::class);

        $this->post(route('login.store'), [
            'email' => 'user-mssb',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticated();
    }
}
