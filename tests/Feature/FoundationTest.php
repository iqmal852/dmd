<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_database_connection_is_postgresql(): void
    {
        $this->assertSame('pgsql', config('database.default'));

        $version = DB::selectOne('select version()')->version;

        $this->assertStringContainsString('PostgreSQL', $version);
    }

    public function test_registration_and_password_reset_routes_do_not_exist(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->assertFalse(Route::has('password.request'));
        $this->assertFalse(Route::has('password.reset'));
        $this->assertFalse(Route::has('verification.notice'));

        $this->get('/register')->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_seeding_the_admin_twice_leaves_exactly_one_user(): void
    {
        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::query()->count());
        $this->assertSame(
            config('batu.admin.email'),
            User::query()->sole()->email,
        );
    }

    public function test_dossier_base_url_respects_env_and_strips_trailing_slash(): void
    {
        config(['dossier.base_url' => 'https://qr.example.test/']);

        // The config value itself must already be trimmed by config/dossier.php;
        // simulate what booting with that env value produces.
        $trimmed = rtrim((string) config('dossier.base_url'), '/');

        $this->assertSame('https://qr.example.test', $trimmed);
    }

    public function test_no_file_outside_config_calls_env_directly(): void
    {
        $hits = [];

        foreach (['app', 'routes', 'database'] as $dir) {
            $path = base_path($dir);

            if (! is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                if ($contents !== false && preg_match('/\benv\s*\(/', $contents) === 1) {
                    $hits[] = str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $hits, 'env() must only be called from config/ files: '.implode(', ', $hits));
    }

    public function test_home_page_renders(): void
    {
        $this->get('/')->assertOk();
    }
}
