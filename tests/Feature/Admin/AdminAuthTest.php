<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * plan/phases/phase-08-admin-qr.md Test Gate #1/#2.
 */
class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Parametrised over the actual registered route list — see
     * plan/phases/phase-08-admin-qr.md Test Gate #1 — rather than a
     * hand-maintained list, so a newly added admin route can never be
     * left unguarded by accident. Called from within a test method (the
     * app is already booted by then), not as a PHPUnit data provider.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function adminRoutes(): array
    {
        $cases = [];

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with((string) $route->getName(), 'admin.')) {
                continue;
            }

            $method = collect($route->methods())->first(fn (string $m) => $m !== 'HEAD') ?? 'GET';
            $uri = preg_replace('/\{[^}]+\}/', 'placeholder', $route->uri());

            $cases[] = [$method, '/'.ltrim((string) $uri, '/')];
        }

        return $cases;
    }

    public function test_every_admin_route_redirects_to_login_when_unauthenticated(): void
    {
        $routes = $this->adminRoutes();
        $this->assertNotEmpty($routes, 'No admin.* routes were found — did routes/admin.php fail to load?');

        foreach ($routes as [$method, $uri]) {
            $response = $this->call($method, $uri);

            $response->assertRedirect(route('login', absolute: false));
        }
    }

    public function test_a_successful_login_redirects_to_the_admin_station_index(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('admin.stations.index', absolute: false));
    }

    /**
     * Regression: Laravel's RedirectIfAuthenticated (the `guest`
     * middleware on /login) ignores config('fortify.home') by default —
     * it redirects to whichever of a route literally named 'dashboard'
     * or 'home' exists first, which was the starter kit's unused
     * /dashboard here, not this app's actual admin console. An already
     * logged-in admin hitting /login (a stale tab, a bookmark, browser
     * back) landed on that leftover page instead of where a fresh login
     * already correctly goes.
     */
    public function test_visiting_login_while_already_authenticated_redirects_to_the_admin_station_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('admin.stations.index', absolute: false));
    }

    public function test_six_failed_logins_in_a_minute_are_throttled(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }
}
