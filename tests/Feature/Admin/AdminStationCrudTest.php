<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * plan/phases/phase-08-admin-qr.md M8.2/M8.3 Test Gate.
 */
class AdminStationCrudTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'LPT2-GCP-999',
            'highway' => 'LPT2',
            'section' => 'E1',
            'km' => '100.500',
            'direction' => 'northbound',
            'monument_type' => 'GCP',
            'installed_at' => '2020-01-01',
            'status' => 'active',
            'description' => 'Test station',
        ], $overrides);
    }

    public function test_the_index_lists_stations_and_supports_search_and_filter(): void
    {
        $user = User::factory()->create();
        Station::factory()->create(['code' => 'LPT2-GCP-001', 'highway' => 'LPT2']);
        Station::factory()->create(['code' => 'NKVE-GCP-002', 'highway' => 'NKVE']);

        $response = $this->actingAs($user)->get(route('admin.stations.index', ['search' => 'LPT2']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/stations/index')
            ->has('stations.data', 1)
            ->where('stations.data.0.code', 'LPT2-GCP-001')
        );
    }

    public function test_creating_a_station_assigns_a_ulid_public_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.stations.store'), $this->validPayload());

        $station = Station::query()->where('code', 'LPT2-GCP-999')->firstOrFail();

        $this->assertMatchesRegularExpression('/^[0-9A-Z]{26}$/', $station->public_id);
        $response->assertRedirect(route('admin.stations.edit', $station));
    }

    public function test_public_id_cannot_be_changed_by_submitting_it_in_the_update_payload(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();
        $originalPublicId = $station->public_id;

        $this->actingAs($user)->put(route('admin.stations.update', $station), $this->validPayload([
            'public_id' => '01ATTACKERCONTROLLEDVALUE0',
        ]));

        $this->assertSame($originalPublicId, $station->fresh()->public_id);
    }

    public function test_case_insensitive_uniqueness_on_code_is_enforced_by_the_form_request(): void
    {
        $user = User::factory()->create();
        Station::factory()->create(['code' => 'LPT2-GCP-015']);

        $response = $this->actingAs($user)->post(route('admin.stations.store'), $this->validPayload([
            'code' => 'lpt2-gcp-015',
        ]));

        $response->assertSessionHasErrors('code');
    }

    public function test_updating_a_station_does_not_trip_its_own_uniqueness_check(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create(['code' => 'LPT2-GCP-015']);

        $response = $this->actingAs($user)->put(route('admin.stations.update', $station), $this->validPayload([
            'code' => 'LPT2-GCP-015',
        ]));

        $response->assertSessionDoesntHaveErrors('code');
    }

    public function test_a_blank_access_password_on_update_leaves_the_existing_one_unchanged(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create(['access_password' => 'original-secret']);
        $originalHash = $station->access_password;

        $this->actingAs($user)->put(route('admin.stations.update', $station), $this->validPayload([
            'access_password' => '',
        ]));

        $this->assertSame($originalHash, $station->fresh()->access_password);
    }

    public function test_the_explicit_clear_action_nulls_the_access_password(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create(['access_password' => 'original-secret']);

        $this->actingAs($user)->put(route('admin.stations.update', $station), $this->validPayload([
            'access_password' => '',
            'clear_access_password' => '1',
        ]));

        $this->assertNull($station->fresh()->access_password);
    }

    public function test_submitting_a_new_access_password_replaces_the_existing_one(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create(['access_password' => 'original-secret']);

        $this->actingAs($user)->put(route('admin.stations.update', $station), $this->validPayload([
            'access_password' => 'a-brand-new-secret',
        ]));

        $this->assertTrue(Hash::check('a-brand-new-secret', $station->fresh()->access_password));
    }

    public function test_the_publish_toggle_updates_only_is_published(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create(['is_published' => false, 'code' => 'LPT2-GCP-777']);

        $this->actingAs($user)->patch(route('admin.stations.publish', $station), ['is_published' => true]);

        $station->refresh();
        $this->assertTrue($station->is_published);
        $this->assertSame('LPT2-GCP-777', $station->code);
    }

    public function test_deleting_a_station_soft_deletes_it(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $this->actingAs($user)->delete(route('admin.stations.destroy', $station));

        $this->assertSoftDeleted($station);
    }
}
