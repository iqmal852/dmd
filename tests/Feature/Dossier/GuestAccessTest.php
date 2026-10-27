<?php

declare(strict_types=1);

namespace Tests\Feature\Dossier;

use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dossier data is private: scanning a QR plate is not enough, the visitor
 * must be logged in, and after logging in they land back on the page they
 * scanned instead of the admin home.
 */
class GuestAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_redirected_to_login_for_every_dossier_page(): void
    {
        $station = Station::factory()->create(['is_published' => true]);

        foreach (['show', 'coordinates', 'map', 'photos', 'files', 'unlock'] as $name) {
            $this->get(route("dossier.{$name}", $station))->assertRedirect(route('login'));
        }
    }

    public function test_a_guest_cannot_probe_for_unpublished_or_missing_stations(): void
    {
        $unpublished = Station::factory()->create(['is_published' => false]);

        $this->get(route('dossier.show', $unpublished))->assertRedirect(route('login'));
        $this->get(route('dossier.show', ['station' => '01ARZ3NDEKTSV4RRFFQ69G5FAV']))
            ->assertRedirect(route('login'));
    }

    public function test_login_returns_the_user_to_the_dossier_page_they_scanned(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create(['is_published' => true]);
        $dossierUrl = route('dossier.show', $station);

        $this->get($dossierUrl)->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect($dossierUrl);

        $this->assertAuthenticated();
    }

    public function test_a_logged_in_user_can_open_the_dossier(): void
    {
        $station = Station::factory()->create(['is_published' => true]);

        $this->actingAs(User::factory()->create())
            ->get(route('dossier.show', $station))
            ->assertOk();
    }
}
