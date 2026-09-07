<?php

declare(strict_types=1);

namespace Tests\Feature\Dossier;

use App\Enums\AccessMode;
use App\Enums\DocumentType;
use App\Models\DownloadLog;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * plan/phases/phase-07-asbuilt-files.md Test Gate.
 */
class FilesTest extends TestCase
{
    use RefreshDatabase;

    private function attachDocument(
        Station $station,
        string $fileName = 'as-built.pdf',
        string $mime = 'application/pdf',
        bool $isPrimary = true,
        ?string $revision = 'A',
    ): Media {
        return $station->addMediaFromString($this->fakeFileBytes($mime))
            ->usingFileName($fileName)
            ->withCustomProperties([
                'document_type' => DocumentType::AsBuilt->value,
                'title' => 'As-Built Drawing',
                'revision' => $revision,
                'is_primary' => $isPrimary,
            ])
            ->toMediaCollection('documents');
    }

    private function fakeFileBytes(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => "%PDF-1.4\n%%EOF",
            // A leading binary-looking marker keeps the mime guess off
            // `text/plain` deterministically — plain random bytes
            // occasionally sniff as text, which `documents` rejects.
            default => "AC1032\x00\x00".random_bytes(64),
        };
    }

    public function test_it_returns_200_with_the_files_component(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        $this->attachDocument($station);

        $response = $this->get(route('dossier.files', $station));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('dossier/files'));
    }

    public function test_props_contain_exactly_the_document_data_fields(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        $this->attachDocument($station);

        $response = $this->get(route('dossier.files', $station));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('documents.0', fn (AssertableInertia $d) => $d
                ->hasAll([
                    'id', 'type', 'typeLabel', 'title', 'revision', 'extension',
                    'size', 'isPrimary', 'previewUrl', 'previewKind', 'downloadUrl',
                ])
                ->missing('file_name')
                ->missing('disk')
                ->missing('preview_media_id')
                ->etc()
            )
        );
    }

    public function test_props_never_reveal_the_internal_media_primary_key(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        $media = $this->attachDocument($station);

        $response = $this->get(route('dossier.files', $station));
        $content = $response->getContent();

        $this->assertNotEmpty($content);
        $this->assertStringNotContainsString('"id":'.$media->id, (string) $content);
        $this->assertStringContainsString((string) $media->uuid, (string) $content);
    }

    public function test_a_preview_only_companion_media_row_is_not_listed_as_its_own_document(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        $preview = $station->addMediaFromString($this->fakeFileBytes('application/pdf'))
            ->usingFileName('preview.pdf')
            ->withCustomProperties(['is_preview_only' => true])
            ->toMediaCollection('documents');

        $station->addMediaFromString($this->fakeFileBytes('application/acad'))
            ->usingFileName('as-built.dwg')
            ->withCustomProperties([
                'document_type' => DocumentType::AsBuilt->value,
                'title' => 'As-Built Drawing',
                'is_primary' => true,
                'preview_media_id' => $preview->id,
            ])
            ->toMediaCollection('documents');

        $response = $this->get(route('dossier.files', $station));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('documents', 1)
            ->where('documents.0.previewKind', 'pdf')
        );
    }

    public function test_a_station_with_no_documents_renders_the_empty_state_without_error(): void
    {
        $station = Station::factory()->create(['is_published' => true]);

        $response = $this->get(route('dossier.files', $station));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->where('documents', []));
    }

    public function test_the_files_route_is_behind_the_access_gate(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'secret',
        ]);
        $station = Station::factory()->create(['is_published' => true]);

        $this->get(route('dossier.files', $station))->assertRedirect();
    }

    public function test_download_returns_200_with_an_attachment_disposition_and_a_sanitised_filename(): void
    {
        $station = Station::factory()->create(['is_published' => true, 'code' => 'LPT2-GCP-015']);
        $media = $this->attachDocument($station, revision: 'A');

        $response = $this->get(route('dossier.files.download', ['station' => $station, 'media' => $media->uuid]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('lpt2-gcp-015-as-built-rev-a.pdf', (string) $response->headers->get('Content-Disposition'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_downloading_a_media_row_belonging_to_another_station_404s(): void
    {
        $stationA = Station::factory()->create(['is_published' => true]);
        $stationB = Station::factory()->create(['is_published' => true]);
        $mediaOfB = $this->attachDocument($stationB);

        $this->get(route('dossier.files.download', ['station' => $stationA, 'media' => $mediaOfB->uuid]))
            ->assertNotFound();
    }

    public function test_preview_is_behind_the_access_gate(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'secret',
        ]);
        $station = Station::factory()->create(['is_published' => true]);
        $media = $this->attachDocument($station);

        $this->get(route('dossier.files.preview', ['station' => $station, 'media' => $media->uuid]))
            ->assertRedirect();
    }

    public function test_previewing_a_non_renderable_document_404s(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        $media = $this->attachDocument($station, fileName: 'as-built.dwg', mime: 'application/acad', revision: null);

        $this->get(route('dossier.files.preview', ['station' => $station, 'media' => $media->uuid]))
            ->assertNotFound();
    }

    public function test_download_is_behind_the_access_gate(): void
    {
        config([
            'dossier.access_mode' => AccessMode::Password,
            'dossier.access_password' => 'secret',
        ]);
        $station = Station::factory()->create(['is_published' => true]);
        $media = $this->attachDocument($station);

        $this->get(route('dossier.files.download', ['station' => $station, 'media' => $media->uuid]))
            ->assertRedirect();

        $this->assertDatabaseCount('download_logs', 0);
    }

    public function test_a_download_writes_exactly_one_download_log_row_with_a_hashed_ip_and_no_raw_ip(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        $media = $this->attachDocument($station);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->get(route('dossier.files.download', ['station' => $station, 'media' => $media->uuid]))
            ->assertOk();

        $this->assertDatabaseCount('download_logs', 1);

        $log = DownloadLog::query()->firstOrFail();

        $this->assertSame($station->id, $log->station_id);
        $this->assertSame($media->id, $log->media_id);
        $this->assertSame(64, strlen((string) $log->ip_hash));
        $this->assertNotSame('203.0.113.42', $log->ip_hash);
        $this->assertStringNotContainsString('203.0.113.42', (string) $log->ip_hash);
    }

    public function test_download_log_enabled_false_writes_no_row_but_still_serves_the_file(): void
    {
        config(['dossier.downloads.log_enabled' => false]);
        $station = Station::factory()->create(['is_published' => true]);
        $media = $this->attachDocument($station);

        $this->get(route('dossier.files.download', ['station' => $station, 'media' => $media->uuid]))
            ->assertOk();

        $this->assertDatabaseCount('download_logs', 0);
    }

    public function test_model_prune_deletes_download_logs_older_than_the_retention_window_and_keeps_newer_ones(): void
    {
        config(['dossier.downloads.retention_days' => 30]);
        $station = Station::factory()->create();

        $old = DownloadLog::factory()->for($station)->create(['downloaded_at' => now()->subDays(31)]);
        $recent = DownloadLog::factory()->for($station)->create(['downloaded_at' => now()->subDays(29)]);

        Artisan::call('model:prune', ['--model' => [DownloadLog::class]]);

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }

    public function test_a_documents_media_file_is_not_reachable_at_a_public_storage_url(): void
    {
        $station = Station::factory()->create(['is_published' => true]);
        $media = $this->attachDocument($station);

        $response = $this->get("/storage/{$media->id}/{$media->file_name}");

        $this->assertContains($response->getStatusCode(), [403, 404]);
    }
}
