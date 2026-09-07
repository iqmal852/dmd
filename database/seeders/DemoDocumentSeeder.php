<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Models\Station;
use Illuminate\Database\Seeder;

/**
 * Attaches as-built documents to a couple of demo stations, exercising
 * every preview path from plan/phases/phase-07-asbuilt-files.md M7.1/M7.3:
 * a PDF that is its own preview, an image that is its own preview (the
 * lightbox), and a DWG original paired with a separately-uploaded PDF
 * preview (LPT2-GCP-001). There is no real as-built survey drawing
 * available for this project, so the PDF/PNG files are generated
 * placeholders and the "DWG" is arbitrary bytes with a `.dwg` extension —
 * enough to exercise every path on the Files screen end to end. A real
 * admin upload flow replaces this entirely in Phase 08.
 */
class DemoDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $withOwnPreview = Station::query()->where('code', 'LPT2-GCP-015')->first();
        $withSeparatePreview = Station::query()->where('code', 'LPT2-GCP-001')->first();
        $withImagePreview = Station::query()->where('code', 'LPT2-GCP-010')->first();

        if ($withOwnPreview) {
            $withOwnPreview->addMediaFromString($this->minimalPdfBytes($withOwnPreview->code))
                ->usingFileName('as-built.pdf')
                ->withCustomProperties([
                    'document_type' => DocumentType::AsBuilt->value,
                    'title' => 'As-Built Drawing',
                    'revision' => 'A',
                    'is_primary' => true,
                ])
                ->toMediaCollection('documents');

            $withOwnPreview->addMediaFromString($this->minimalPdfBytes($withOwnPreview->code.' — QC Report'))
                ->usingFileName('qc-report.pdf')
                ->withCustomProperties([
                    'document_type' => DocumentType::Report->value,
                    'title' => 'QC Report',
                    'is_primary' => false,
                ])
                ->toMediaCollection('documents');

            $withOwnPreview->addMediaFromString($this->placeholderPngBytes())
                ->usingFileName('site-certificate.png')
                ->withCustomProperties([
                    'document_type' => DocumentType::Certificate->value,
                    'title' => 'Site Acceptance Certificate',
                    'is_primary' => false,
                ])
                ->toMediaCollection('documents');
        }

        if ($withImagePreview) {
            $withImagePreview->addMediaFromString($this->placeholderPngBytes())
                ->usingFileName('as-built.png')
                ->withCustomProperties([
                    'document_type' => DocumentType::AsBuilt->value,
                    'title' => 'As-Built Drawing',
                    'revision' => 'A',
                    'is_primary' => true,
                ])
                ->toMediaCollection('documents');
        }

        if ($withSeparatePreview) {
            $preview = $withSeparatePreview->addMediaFromString($this->minimalPdfBytes($withSeparatePreview->code))
                ->usingFileName('as-built-preview.pdf')
                ->withCustomProperties(['is_preview_only' => true])
                ->toMediaCollection('documents');

            $withSeparatePreview->addMediaFromString($this->fakeDwgBytes())
                ->usingFileName('as-built.dwg')
                ->withCustomProperties([
                    'document_type' => DocumentType::AsBuilt->value,
                    'title' => 'As-Built Drawing',
                    'revision' => 'B',
                    'is_primary' => true,
                    'preview_media_id' => $preview->id,
                ])
                ->toMediaCollection('documents');
        }
    }

    /**
     * A tiny placeholder PNG, to exercise the image-preview path (the
     * lightbox) alongside the PDF path — same rationale as
     * DemoPhotoSeeder: no real certificate scan exists for this project.
     */
    private function placeholderPngBytes(): string
    {
        $image = imagecreatetruecolor(240, 320);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    /**
     * A DWG file's binary format isn't reproducible without real CAD
     * software; arbitrary bytes with a `.dwg` extension are enough to
     * exercise the "original can't be rendered, use the paired preview"
     * path that's the actual point of this pairing.
     */
    private function fakeDwgBytes(): string
    {
        return "AC1032\x00\x00".random_bytes(256);
    }

    /**
     * A minimal, spec-valid single-page PDF with a text label — built by
     * hand rather than via a PDF library, since none is installed and the
     * content only needs to be real enough for a browser's native PDF
     * viewer to render without erroring.
     */
    private function minimalPdfBytes(string $label): string
    {
        $header = "%PDF-1.4\n";

        $objects = [];
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 400 300] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
        $objects[4] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $escaped = addcslashes($label, '()\\');
        $stream = "BT /F1 18 Tf 30 150 Td ({$escaped}) Tj ET";
        $objects[5] = "5 0 obj\n<< /Length ".strlen($stream)." >>\nstream\n{$stream}\nendstream\nendobj\n";

        $body = '';
        $offsets = [];
        $position = strlen($header);

        foreach ($objects as $number => $object) {
            $offsets[$number] = $position;
            $body .= $object;
            $position += strlen($object);
        }

        $xrefStart = $position;
        $count = count($objects) + 1;
        $xref = "xref\n0 {$count}\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $xref .= sprintf("%010d 00000 n \n", $offset);
        }

        $trailer = "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";

        return $header.$body.$xref.$trailer;
    }
}
