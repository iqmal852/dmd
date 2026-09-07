<?php

declare(strict_types=1);

namespace Tests\Feature\Dossier;

use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * The route prefix is read from config('dossier.route_prefix') once at
 * boot — see plan/phases/phase-03-dossier-shell.md M3.1. Verified against
 * a genuinely fresh boot (a subprocess), the same technique used in
 * tests/Feature/DevUiRouteTest.php, since config() overrides in the
 * current process can't retroactively change already-registered routes.
 */
class RoutePrefixTest extends TestCase
{
    public function test_changing_the_route_prefix_moves_the_dossier_routes(): void
    {
        $result = Process::path(base_path())
            ->env([
                'APP_ENV' => 'testing',
                'APP_KEY' => (string) config('app.key'),
                'DB_CONNECTION' => (string) config('database.default'),
                'DOSSIER_ROUTE_PREFIX' => 'gcp',
            ])
            ->run([PHP_BINARY, 'artisan', 'route:list', '--no-ansi']);

        $output = $result->output();

        $this->assertStringContainsString('gcp/{station}', $output);
        $this->assertStringNotContainsString('d/{station}', $output);
    }

    public function test_the_default_prefix_is_d(): void
    {
        $this->assertSame('d', config('dossier.route_prefix'));
    }
}
