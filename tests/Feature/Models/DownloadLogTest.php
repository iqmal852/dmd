<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\DownloadLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DownloadLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_ip_hash_is_a_64_character_hex_string_and_never_the_raw_address(): void
    {
        $log = DownloadLog::factory()->create([
            'ip_hash' => hash_hmac('sha256', '203.0.113.7', config('app.key')),
        ]);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $log->ip_hash);
        $this->assertStringNotContainsString('203.0.113.7', $log->ip_hash);
    }

    public function test_ip_hash_is_stable_for_the_same_ip_and_differs_across_ips(): void
    {
        $key = config('app.key');

        $hashA1 = hash_hmac('sha256', '203.0.113.7', $key);
        $hashA2 = hash_hmac('sha256', '203.0.113.7', $key);
        $hashB = hash_hmac('sha256', '198.51.100.4', $key);

        $this->assertSame($hashA1, $hashA2);
        $this->assertNotSame($hashA1, $hashB);
    }

    public function test_no_column_on_download_logs_stores_a_raw_ip(): void
    {
        $columns = Schema::getColumnListing('download_logs');

        $this->assertNotContains('ip_address', $columns);
        $this->assertNotContains('ip', $columns);
    }

    public function test_prunable_scope_selects_only_rows_past_the_retention_window(): void
    {
        config(['dossier.downloads.retention_days' => 30]);

        $old = DownloadLog::factory()->create(['downloaded_at' => now()->subDays(60)]);
        $recent = DownloadLog::factory()->create(['downloaded_at' => now()->subDays(1)]);

        $prunable = (new DownloadLog)->prunable()->pluck('id');

        $this->assertTrue($prunable->contains($old->id));
        $this->assertFalse($prunable->contains($recent->id));
    }

    public function test_download_log_is_append_only_with_no_updated_at(): void
    {
        $this->assertNull(DownloadLog::UPDATED_AT);
        $this->assertNull(DownloadLog::CREATED_AT);
    }
}
