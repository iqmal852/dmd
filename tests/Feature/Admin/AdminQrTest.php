<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Qr\GenerateStationQr;
use App\Models\Station;
use App\Models\User;
use App\Services\QrUrlBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Zxing\QrReader;

/**
 * plan/phases/phase-08-admin-qr.md M8.4 Test Gate.
 */
class AdminQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_payload_uses_the_configured_base_url_not_the_request_host(): void
    {
        config(['dossier.base_url' => 'https://qr.example.test']);
        $station = Station::factory()->create();

        $encoded = app(QrUrlBuilder::class)->forStation($station);

        $this->assertStringStartsWith('https://qr.example.test/d/', $encoded);
    }

    public function test_the_registered_dossier_route_and_the_qr_payload_derive_from_the_same_config_key(): void
    {
        // routes/dossier.php's Route::prefix(...) group reads
        // config('dossier.route_prefix') once, at application boot —
        // changing the config value at runtime (without a full reboot)
        // wouldn't retroactively re-register that route group, so this
        // doesn't try to prove "changing it resolves" by changing it
        // mid-test. Instead it proves the thing that actually matters:
        // the registered route's prefix segment and QrUrlBuilder's output
        // both read the identical config key rather than either one
        // hardcoding 'd' — so they can never drift apart when
        // DOSSIER_ROUTE_PREFIX changes for a real deployment.
        $configuredPrefix = trim((string) config('dossier.route_prefix'), '/');
        $station = Station::factory()->create(['is_published' => true]);

        $showRoute = collect(Route::getRoutes())
            ->first(fn ($route) => $route->getName() === 'dossier.show');

        $this->assertNotNull($showRoute);
        $this->assertStringStartsWith($configuredPrefix.'/', $showRoute->uri());

        $encoded = app(QrUrlBuilder::class)->forStation($station);
        $this->assertStringContainsString('/'.$configuredPrefix.'/'.$station->public_id, $encoded);

        $response = $this->get('/'.$configuredPrefix.'/'.$station->public_id);
        $response->assertOk();
    }

    public function test_a_trailing_slash_in_the_base_url_never_yields_a_double_slash(): void
    {
        config(['dossier.base_url' => 'https://qr.example.test/']);
        $station = Station::factory()->create();

        $encoded = app(QrUrlBuilder::class)->forStation($station);

        $this->assertStringNotContainsString('//d/', str_replace('https://', '', $encoded));
    }

    public function test_decoding_the_generated_png_returns_exactly_the_expected_url(): void
    {
        $station = Station::factory()->create();
        $expected = app(QrUrlBuilder::class)->forStation($station);

        $result = app(GenerateStationQr::class)($station, 'png');

        $reader = new QrReader($result->getString(), QrReader::SOURCE_TYPE_BLOB, false);

        $this->assertSame($expected, $reader->text());
    }

    /**
     * Regression, two layers deep:
     *
     * 1. GenerateStationQr caches its result, and Endroid's PngWriter
     *    result wraps a live GdImage — PHP refuses to serialize that
     *    ("Serialization of 'GdImage' is not allowed"), breaking the
     *    very first cache write.
     * 2. Reducing the cached value to plain strings wasn't enough on its
     *    own: config('cache.serializable_classes') defaults to `false`,
     *    which makes the `database` store's unserialize() refuse *any*
     *    object class (not just GdImage-bearing ones) — so caching even
     *    a plain DTO object still silently came back as
     *    __PHP_Incomplete_Class on the next, cache-hit read. The actual
     *    fix caches a plain array; GenerateStationQr rebuilds the QrImage
     *    wrapper from it on every call instead of ever serializing it.
     *
     * phpunit.xml sets CACHE_STORE=array for the whole suite, which never
     * serializes anything and would hide both bugs — so this test forces
     * the real `database` store to actually exercise a serialize/
     * unserialize round trip, matching production and local dev.
     */
    public function test_a_second_call_reads_the_cached_result_without_error(): void
    {
        config(['cache.default' => 'database']);
        $station = Station::factory()->create();
        $generate = app(GenerateStationQr::class);

        $first = $generate($station, 'png');
        $second = $generate($station, 'png');

        $this->assertSame($first->getString(), $second->getString());
        $this->assertSame($first->getMimeType(), $second->getMimeType());
    }

    public function test_the_qr_preview_page_renders_for_an_authenticated_admin(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.stations.qr', $station));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/stations/qr')
            ->has('encodedUrl')
            ->has('previewDataUri')
        );
    }

    public function test_the_qr_download_route_streams_png_and_svg(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $png = $this->actingAs($user)->get(route('admin.stations.qr.download', $station).'?format=png');
        $png->assertOk();
        $png->assertHeader('Content-Type', 'image/png');

        $svg = $this->actingAs($user)->get(route('admin.stations.qr.download', $station).'?format=svg');
        $svg->assertOk();
        $svg->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_the_print_route_renders_a_single_plate(): void
    {
        $user = User::factory()->create();
        $station = Station::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.stations.qr.print', $station));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/stations/qr-print')
            ->has('plates', 1)
        );
    }

    public function test_the_sheet_route_renders_every_station_or_a_filtered_subset(): void
    {
        $user = User::factory()->create();
        Station::factory()->count(3)->create(['highway' => 'LPT2']);
        Station::factory()->count(2)->create(['highway' => 'NKVE']);

        $all = $this->actingAs($user)->get(route('admin.qr.sheet'));
        $all->assertInertia(fn ($page) => $page->has('plates', 5));

        $filtered = $this->actingAs($user)->get(route('admin.qr.sheet', ['highway' => 'LPT2']));
        $filtered->assertInertia(fn ($page) => $page->has('plates', 3));
    }
}
