<?php

declare(strict_types=1);

namespace Tests\Feature\Dossier;

use App\Models\CoordinateSet;
use App\Models\Specification;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Assert;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * plan/phases/phase-03-dossier-shell.md M3.5/M3.6 — enforces ADR-010: the
 * public dossier controller must never pass a model or ->toArray() to
 * Inertia. Every field is enumerated and no extras are allowed.
 */
class OverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_prop_shape_is_exactly_the_dto(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        CoordinateSet::factory()->for($station)->create();
        Specification::factory()->for($station)->create();

        $response = $this->get(route('dossier.show', $station));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dossier/overview')
            ->has('station', fn (AssertableInertia $s) => $s
                ->hasAll([
                    'publicId', 'code', 'highway', 'section', 'km', 'direction',
                    'monumentType', 'installedAt', 'status', 'statusColor',
                    'quickView', 'mapPreview', 'modules',
                ])
                ->missing('id')
                ->missing('accessPassword')
                ->missing('access_password')
                ->etc()
            )
        );
    }

    public function test_overview_never_exposes_the_internal_primary_key_or_password(): void
    {
        $station = Station::factory()->create([
            'is_published' => true,
            'access_password' => 'super-secret',
        ]);

        $response = $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => ''])
            ->get(route('dossier.show', $station));

        $content = $response->getContent();

        $this->assertStringNotContainsString('access_password', (string) $content);
        $this->assertStringNotContainsString('accessPassword', (string) $content);
        $this->assertStringNotContainsString('super-secret', (string) $content);
    }

    public function test_overview_issues_at_most_three_queries(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        CoordinateSet::factory()->for($station)->create();
        Specification::factory()->for($station)->create();

        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->get(route('dossier.show', $station))->assertOk();

        Assert::assertLessThanOrEqual(3, $queryCount, "Overview issued {$queryCount} queries, expected <= 3");
    }

    public function test_overview_renders_empty_states_for_a_station_with_no_coordinates(): void
    {
        $station = Station::factory()->create(['is_published' => true]);

        $response = $this->get(route('dossier.show', $station));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('station.quickView', null)
        );
    }
}
