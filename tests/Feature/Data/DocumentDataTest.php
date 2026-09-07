<?php

declare(strict_types=1);

namespace Tests\Feature\Data;

use App\Data\DocumentData;
use App\Enums\DocumentType;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentDataTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Random bytes alone occasionally sniff as `text/plain` (a fluke of
     * the sampled bytes happening to be valid ASCII), which the
     * `documents` collection rejects. A leading binary-looking marker
     * keeps this deterministic without needing a real DWG file.
     */
    private function fakeDwgBytes(): string
    {
        return "AC1032\x00\x00".random_bytes(256);
    }

    public function test_from_maps_a_pdf_original_as_its_own_preview(): void
    {
        $station = Station::factory()->create();

        $media = $station->addMediaFromString("%PDF-1.4\n%%EOF")
            ->usingFileName('as-built.pdf')
            ->withCustomProperties([
                'document_type' => DocumentType::AsBuilt->value,
                'title' => 'As-Built Drawing',
                'revision' => 'A',
                'is_primary' => true,
            ])
            ->toMediaCollection('documents');

        $data = DocumentData::from($media->fresh(), $station);

        $this->assertSame((string) $media->uuid, $data->id);
        $this->assertSame(DocumentType::AsBuilt->value, $data->type);
        $this->assertSame(DocumentType::AsBuilt->label(), $data->typeLabel);
        $this->assertSame('As-Built Drawing', $data->title);
        $this->assertSame('A', $data->revision);
        $this->assertSame('PDF', $data->extension);
        $this->assertTrue($data->isPrimary);
        $this->assertSame('pdf', $data->previewKind);
        $this->assertNotNull($data->previewUrl);
        $this->assertStringContainsString((string) $media->uuid, $data->previewUrl);
        $this->assertStringContainsString((string) $media->uuid, $data->downloadUrl);
    }

    public function test_from_falls_back_to_the_type_label_when_no_title_is_set(): void
    {
        $station = Station::factory()->create();

        $media = $station->addMediaFromString("%PDF-1.4\n%%EOF")
            ->usingFileName('report.pdf')
            ->withCustomProperties(['document_type' => DocumentType::Report->value])
            ->toMediaCollection('documents');

        $data = DocumentData::from($media->fresh(), $station);

        $this->assertSame(DocumentType::Report->label(), $data->title);
        $this->assertNull($data->revision);
        $this->assertFalse($data->isPrimary);
    }

    public function test_from_resolves_a_dwg_originals_preview_via_the_sibling_media_row(): void
    {
        $station = Station::factory()->create();

        $preview = $station->addMediaFromString("%PDF-1.4\n%%EOF")
            ->usingFileName('preview.pdf')
            ->withCustomProperties(['is_preview_only' => true])
            ->toMediaCollection('documents');

        $dwg = $station->addMediaFromString($this->fakeDwgBytes())
            ->usingFileName('as-built.dwg')
            ->withCustomProperties([
                'document_type' => DocumentType::AsBuilt->value,
                'preview_media_id' => $preview->id,
            ])
            ->toMediaCollection('documents');

        $data = DocumentData::from($dwg->fresh(), $station);

        $this->assertSame('pdf', $data->previewKind);
        $this->assertNotNull($data->previewUrl);
        $this->assertStringContainsString((string) $preview->uuid, $data->previewUrl);
    }

    public function test_from_has_no_preview_when_a_dwg_original_has_no_linked_preview(): void
    {
        $station = Station::factory()->create();

        $dwg = $station->addMediaFromString($this->fakeDwgBytes())
            ->usingFileName('as-built.dwg')
            ->withCustomProperties(['document_type' => DocumentType::AsBuilt->value])
            ->toMediaCollection('documents');

        $data = DocumentData::from($dwg->fresh(), $station);

        $this->assertSame('none', $data->previewKind);
        $this->assertNull($data->previewUrl);
    }
}
