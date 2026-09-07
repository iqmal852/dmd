<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The kitchen-sink route must never exist outside local/testing — see
 * routes/web.php and plan/phases/phase-02-design-system.md M2.6. Verified
 * against a genuinely fresh production boot (a subprocess), not a mocked
 * environment() call, since the route gate is evaluated once at boot time
 * and cannot be flipped mid-process in the current test's own app instance.
 */
class DevUiRouteTest extends TestCase
{
    public function test_dev_ui_route_does_not_exist_when_booted_as_production(): void
    {
        $result = Process::path(base_path())
            ->env([
                'APP_ENV' => 'production',
                'APP_KEY' => (string) config('app.key'),
                'DB_CONNECTION' => (string) config('database.default'),
            ])
            ->run([PHP_BINARY, 'artisan', 'route:list', '--name=dev.ui', '--no-ansi']);

        $this->assertStringNotContainsString('dev/ui', $result->output());
    }

    public function test_dev_ui_route_exists_in_the_current_testing_environment(): void
    {
        $this->assertTrue(Route::has('dev.ui'));
    }
}
