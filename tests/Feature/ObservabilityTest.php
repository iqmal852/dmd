<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

/**
 * plan/phases/phase-09-hardening-release.md M9.4.
 */
class ObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_up_reports_healthy_when_the_database_and_disk_are_fine(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_up_reports_unhealthy_when_the_database_is_unreachable(): void
    {
        DB::shouldReceive('connection->getPdo')->andThrow(new \RuntimeException('connection refused'));

        $this->get('/up')->assertStatus(500);
    }

    public function test_up_reports_unhealthy_when_the_storage_disk_is_not_writable(): void
    {
        $disk = Mockery::mock(Storage::disk('local'))->makePartial();
        $disk->shouldReceive('put')->andThrow(new \RuntimeException('disk full'));
        Storage::set('local', $disk);

        $this->get('/up')->assertStatus(500);
    }

    public function test_every_response_carries_a_request_id_header(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Request-Id');
        $this->assertNotEmpty($response->headers->get('X-Request-Id'));
    }

    public function test_an_inbound_request_id_is_echoed_back_rather_than_replaced(): void
    {
        $response = $this->withHeader('X-Request-Id', 'trace-abc-123')->get('/');

        $response->assertHeader('X-Request-Id', 'trace-abc-123');
    }
}
