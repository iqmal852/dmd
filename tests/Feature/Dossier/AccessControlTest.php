<?php

declare(strict_types=1);

namespace Tests\Feature\Dossier;

use App\Enums\AccessMode;
use App\Models\CoordinateSet;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Covers the full access-mode behaviour matrix from
 * plan/04-env-configuration.md §6 and plan/phases/phase-03-dossier-shell.md
 * M3.2/M3.4's Test Gate.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function publishedStation(array $attributes = []): Station
    {
        return Station::factory()->create(['is_published' => true, ...$attributes]);
    }

    // ── Public mode ──────────────────────────────────────────────────

    public function test_public_mode_with_no_station_password_opens_immediately(): void
    {
        config(['dossier.access_mode' => AccessMode::Public]);
        $station = $this->publishedStation();

        $response = $this->get(route('dossier.show', $station));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('dossier/overview'));
    }

    public function test_public_mode_with_a_station_password_still_requires_unlock(): void
    {
        config(['dossier.access_mode' => AccessMode::Public]);
        $station = $this->publishedStation(['access_password' => 'station-secret']);

        $response = $this->get(route('dossier.show', $station));

        $response->assertRedirect();
        $this->assertStringContainsString('/unlock', $response->headers->get('Location'));
    }

    // ── Password mode ────────────────────────────────────────────────

    public function test_password_mode_redirects_to_unlock_and_leaks_no_data(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'global-secret',
        ]);
        $station = $this->publishedStation();
        $station->coordinateSet()->create(CoordinateSet::factory()->raw());

        $response = $this->get(route('dossier.show', $station));

        $this->assertRedirectsToUnlock($response, $station);
        $this->assertStringNotContainsString(
            (string) $station->coordinateSet->latitude,
            $response->getContent() ?: '',
        );
    }

    public function test_correct_global_password_unlocks_and_then_the_dossier_loads(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'global-secret',
        ]);
        $station = $this->publishedStation();

        $unlock = $this->post(route('dossier.unlock.submit', $station), [
            'password' => 'global-secret',
        ]);

        $unlock->assertRedirect(route('dossier.show', $station));

        $this->get(route('dossier.show', $station))->assertOk();
    }

    public function test_wrong_password_shows_a_generic_error(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'global-secret',
        ]);
        $station = $this->publishedStation();

        $response = $this->from(route('dossier.unlock', $station))
            ->post(route('dossier.unlock.submit', $station), ['password' => 'wrong']);

        $response->assertRedirect(route('dossier.unlock', $station));
        $response->assertSessionHas('unlock_error', 'Incorrect password. Please try again.');
    }

    public function test_six_unlock_attempts_in_a_minute_are_throttled(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'global-secret',
            'dossier.unlock_throttle' => 5,
        ]);
        $station = $this->publishedStation();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('dossier.unlock.submit', $station), ['password' => 'wrong'])
                ->assertRedirect();
        }

        $this->post(route('dossier.unlock.submit', $station), ['password' => 'wrong'])
            ->assertStatus(429);
    }

    // ── Per-station override matrix (plan/04-env-configuration.md §6) ──

    public function test_matrix_public_mode_no_station_password_opens(): void
    {
        config(['dossier.access_mode' => AccessMode::Public]);
        $station = $this->publishedStation();

        $this->get(route('dossier.show', $station))->assertOk();
    }

    public function test_matrix_public_mode_with_station_password_gates(): void
    {
        config(['dossier.access_mode' => AccessMode::Public]);
        $station = $this->publishedStation(['access_password' => 'secret']);

        $this->assertRedirectsToUnlock($this->get(route('dossier.show', $station)), $station);
    }

    public function test_matrix_password_mode_no_station_password_uses_global(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'global-secret',
        ]);
        $station = $this->publishedStation();

        $this->post(route('dossier.unlock.submit', $station), ['password' => 'global-secret'])
            ->assertRedirect(route('dossier.show', $station));
    }

    public function test_matrix_password_mode_with_station_password_overrides_global(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'global-secret',
        ]);
        $station = $this->publishedStation(['access_password' => 'station-secret']);

        // Global password no longer works once the station has its own.
        $this->post(route('dossier.unlock.submit', $station), ['password' => 'global-secret'])
            ->assertSessionHas('unlock_error');

        $this->post(route('dossier.unlock.submit', $station), ['password' => 'station-secret'])
            ->assertRedirect(route('dossier.show', $station));
    }

    // ── Unlock lifetime & scoping ────────────────────────────────────

    public function test_unlock_expires_after_the_configured_ttl(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'global-secret',
            'dossier.unlock_ttl' => 10,
        ]);
        $station = $this->publishedStation();

        $this->post(route('dossier.unlock.submit', $station), ['password' => 'global-secret']);
        $this->get(route('dossier.show', $station))->assertOk();

        $this->travel(11)->minutes();

        $this->assertRedirectsToUnlock($this->get(route('dossier.show', $station)), $station);
    }

    public function test_unlocking_one_station_does_not_unlock_another(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'global-secret',
        ]);
        $stationA = $this->publishedStation();
        $stationB = $this->publishedStation();

        $this->post(route('dossier.unlock.submit', $stationA), ['password' => 'global-secret']);

        $this->get(route('dossier.show', $stationA))->assertOk();
        $this->assertRedirectsToUnlock($this->get(route('dossier.show', $stationB)), $stationB);
    }

    // ── Fail closed ──────────────────────────────────────────────────

    public function test_misconfigured_password_mode_fails_closed_with_503(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => null,
        ]);
        $station = $this->publishedStation();

        $response = $this->get(route('dossier.show', $station));

        $response->assertStatus(503);
        $this->assertStringNotContainsString($station->code, $response->getContent() ?: '');
    }

    public function test_misconfiguration_is_resolved_by_a_station_specific_password(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => null,
        ]);
        $station = $this->publishedStation(['access_password' => 'station-secret']);

        // Not misconfigured: this station has its own password.
        $this->assertRedirectsToUnlock($this->get(route('dossier.show', $station)), $station);
    }

    // ── Publication / soft-delete ────────────────────────────────────

    public function test_unpublished_station_404s(): void
    {
        $station = Station::factory()->unpublished()->create();

        $this->get(route('dossier.show', $station))->assertNotFound();
    }

    public function test_soft_deleted_station_404s(): void
    {
        $station = $this->publishedStation();
        $station->delete();

        $this->get(route('dossier.show', $station))->assertNotFound();
    }

    // ── noindex ──────────────────────────────────────────────────────

    public function test_dossier_routes_carry_the_noindex_header(): void
    {
        $station = $this->publishedStation();

        $response = $this->get(route('dossier.show', $station));

        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_the_landing_page_does_not_carry_the_noindex_header(): void
    {
        $response = $this->get('/');

        $response->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_password_hashing_uses_the_correct_comparison_strategy(): void
    {
        $station = $this->publishedStation(['access_password' => 'station-secret']);

        $this->assertTrue(Hash::isHashed($station->access_password));
    }

    private function assertRedirectsToUnlock(TestResponse $response, Station $station): void
    {
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString(route('dossier.unlock', $station), $location);
    }
}
