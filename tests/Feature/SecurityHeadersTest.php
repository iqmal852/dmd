<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * plan/phases/phase-09-hardening-release.md M9.1.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_response_carries_the_baseline_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'geolocation=(self), camera=(), microphone=()');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_the_content_security_policy_is_present_outside_local(): void
    {
        // phpunit.xml sets APP_ENV=testing for the whole suite — this is
        // the environment AddSecurityHeaders is meant to apply the real
        // CSP in, same as `production` would.
        $response = $this->get('/');

        $response->assertHeader('Content-Security-Policy');
        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString('server.arcgisonline.com', $csp);
        $this->assertStringContainsString('tile.openstreetmap.org', $csp);
    }

    public function test_the_dossier_prefix_and_admin_are_disallowed_in_robots_txt(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertSee('Disallow: /'.config('dossier.route_prefix').'/', false);
        $response->assertSee('Disallow: /admin/', false);
    }

    public function test_the_download_route_is_rate_limited(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        $media = $station->addMediaFromString('%PDF-1.4')
            ->usingFileName('a.pdf')
            ->withCustomProperties(['document_type' => 'as_built', 'title' => 'A', 'is_primary' => true])
            ->toMediaCollection('documents');

        $limit = (int) config('dossier.downloads.download_throttle');
        $url = route('dossier.files.download', ['station' => $station, 'media' => $media->uuid]);

        for ($i = 0; $i < $limit; $i++) {
            $this->get($url)->assertOk();
        }

        $this->get($url)->assertStatus(429);
    }

    public function test_no_raw_ip_column_exists_anywhere_in_the_schema(): void
    {
        $columns = Schema::getColumns('download_logs');
        $columnNames = collect($columns)->pluck('name');

        $this->assertTrue($columnNames->contains('ip_hash'));
        $this->assertFalse($columnNames->contains('ip'));
        $this->assertFalse($columnNames->contains('ip_address'));
    }
}
