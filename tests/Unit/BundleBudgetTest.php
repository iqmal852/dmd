<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * plan/phases/phase-09-hardening-release.md M9.2 — entry JS < 180 KB
 * gzipped. Walks the entry point's actual static import graph from the
 * Vite manifest (everything that loads on *every* page, before any
 * page-specific or lazy chunk) rather than trusting a single file's
 * reported size, since Leaflet and Pannellum must stay out of this
 * graph entirely — that's a separate, already-covered assertion (see
 * plan/phases/phase-05-location-map.md and
 * plan/phases/phase-06-photos-360.md's build-check milestones).
 *
 * Requires `npm run build` to have already run — this test reads the
 * build output, it doesn't produce it.
 */
class BundleBudgetTest extends TestCase
{
    private const int BUDGET_BYTES = 180 * 1024;

    public function test_the_entry_bundles_static_import_graph_stays_under_the_gzipped_budget(): void
    {
        $manifestPath = public_path('build/manifest.json');

        $this->assertFileExists(
            $manifestPath,
            'No build manifest found — run `npm run build` before this test.',
        );

        /** @var array<string, array{file: string, imports?: array<int, string>}> $manifest */
        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        $totalGzipped = $this->gzippedSizeOfImportGraph($manifest, 'resources/js/app.tsx');

        $this->assertLessThan(
            self::BUDGET_BYTES,
            $totalGzipped,
            sprintf(
                'Entry bundle graph is %.1f KB gzipped, over the 180 KB budget.',
                $totalGzipped / 1024,
            ),
        );
    }

    public function test_leaflet_and_pannellum_are_not_part_of_the_entry_graph(): void
    {
        $manifestPath = public_path('build/manifest.json');
        /** @var array<string, array{file: string, imports?: array<int, string>}> $manifest */
        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        $files = $this->collectImportGraphFiles($manifest, 'resources/js/app.tsx');

        foreach ($files as $file) {
            $this->assertStringNotContainsStringIgnoringCase('leaflet', $file);
            $this->assertStringNotContainsStringIgnoringCase('pannellum', $file);
        }
    }

    /**
     * @param  array<string, array{file: string, imports?: array<int, string>}>  $manifest
     */
    private function gzippedSizeOfImportGraph(array $manifest, string $entryKey): int
    {
        return array_sum(array_map(
            fn (string $file) => (int) strlen((string) gzencode((string) file_get_contents(public_path('build/'.$file)), 9)),
            $this->collectImportGraphFiles($manifest, $entryKey),
        ));
    }

    /**
     * Deduplicates across the *entire* graph, not per-branch: several
     * distinct import chains commonly share the same underlying chunk
     * (React's jsx-runtime, say), and counting it once per path that
     * reaches it would wildly overstate the actual bytes a browser
     * downloads (it fetches each chunk exactly once).
     *
     * @param  array<string, array{file: string, imports?: array<int, string>}>  $manifest
     * @return array<int, string>
     */
    private function collectImportGraphFiles(array $manifest, string $entryKey): array
    {
        $visited = [];
        $files = [];
        $queue = [$entryKey];

        while ($queue !== []) {
            $key = array_shift($queue);

            if (isset($visited[$key]) || ! isset($manifest[$key])) {
                continue;
            }

            $visited[$key] = true;
            $files[] = $manifest[$key]['file'];
            array_push($queue, ...($manifest[$key]['imports'] ?? []));
        }

        return $files;
    }
}
