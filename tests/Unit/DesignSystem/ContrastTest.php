<?php

declare(strict_types=1);

namespace Tests\Unit\DesignSystem;

use Tests\TestCase;

/**
 * Phase 02 M2.5 — the accessibility gate. Computes real WCAG 2.1 contrast
 * ratios for every token pair the Neumorphism system actually uses, parsed
 * directly out of resources/css/app.css so this test can never drift from
 * what ships. If a token fails here, change the token — see
 * plan/03-design-system.md §1.1: "the surface may be soft, the content
 * may not."
 */
class ContrastTest extends TestCase
{
    /** @var array<string, string> */
    private static array $light;

    /** @var array<string, string> */
    private static array $dark;

    protected function setUp(): void
    {
        parent::setUp();

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertNotFalse($css, 'resources/css/app.css must exist');

        self::$light = $this->extractBlockVariables($css, ':root');
        self::$dark = $this->extractBlockVariables($css, '.dark');
    }

    /**
     * Extracts `--name: #hex;` declarations from the first `{selector} { ... }`
     * block. Deliberately simple (no full CSS parser) — this file's :root and
     * .dark blocks are hand-written and flat, not generated.
     *
     * @return array<string, string>
     */
    private function extractBlockVariables(string $css, string $selector): array
    {
        $pattern = '/'.preg_quote($selector, '/').'\s*\{([^}]*)\}/s';
        $this->assertMatchesRegularExpression($pattern, $css, "Missing `{$selector} {{...}}` block in app.css");
        preg_match($pattern, $css, $blockMatch);

        preg_match_all('/--([a-z0-9-]+):\s*(#[0-9a-fA-F]{6});/', $blockMatch[1], $varMatches, PREG_SET_ORDER);

        $vars = [];
        foreach ($varMatches as $match) {
            $vars[$match[1]] = strtolower($match[2]);
        }

        return $vars;
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function relativeLuminance(string $hex): float
    {
        [$r, $g, $b] = array_map(function (int $c): float {
            $c /= 255;

            return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, $this->hexToRgb($hex));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private function contrast(string $hexA, string $hexB): float
    {
        $lA = $this->relativeLuminance($hexA);
        $lB = $this->relativeLuminance($hexB);
        $lighter = max($lA, $lB);
        $darker = min($lA, $lB);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Body-text pairs: ink / ink-muted read directly against the page
     * surface, in both themes. Must clear the 4.5:1 AA floor.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function lightBodyTextPairs(): array
    {
        return [
            'light ink on surface' => ['neu-ink', 'neu-surface'],
            'light ink-muted on surface' => ['neu-ink-muted', 'neu-surface'],
            'light ink on surface-raised' => ['neu-ink', 'neu-surface-raised'],
            'light ink on surface-sunken' => ['neu-ink', 'neu-surface-sunken'],
            'light link on surface' => ['neu-link', 'neu-surface'],
        ];
    }

    public function test_light_theme_body_text_pairs_meet_aa(): void
    {
        foreach (self::lightBodyTextPairs() as $label => [$fg, $bg]) {
            $ratio = $this->contrast(self::$light[$fg], self::$light[$bg]);

            $this->assertGreaterThanOrEqual(
                4.5,
                $ratio,
                sprintf('%s: %s (%s) on %s (%s) is only %.2f:1', $label, $fg, self::$light[$fg], $bg, self::$light[$bg], $ratio),
            );
        }
    }

    public function test_dark_theme_body_text_pairs_meet_aa(): void
    {
        $pairs = [
            'dark ink on surface' => ['neu-ink', 'neu-surface'],
            'dark ink-muted on surface' => ['neu-ink-muted', 'neu-surface'],
            'dark ink on surface-raised' => ['neu-ink', 'neu-surface-raised'],
            'dark ink on surface-sunken' => ['neu-ink', 'neu-surface-sunken'],
            'dark link on surface' => ['neu-link', 'neu-surface'],
        ];

        foreach ($pairs as $label => [$fg, $bg]) {
            $ratio = $this->contrast(self::$dark[$fg], self::$dark[$bg]);

            $this->assertGreaterThanOrEqual(
                4.5,
                $ratio,
                sprintf('%s: %s (%s) on %s (%s) is only %.2f:1', $label, $fg, self::$dark[$fg], $bg, self::$dark[$bg], $ratio),
            );
        }
    }

    public function test_ink_subtle_meets_the_large_text_only_floor_in_both_themes(): void
    {
        $lightRatio = $this->contrast(self::$light['neu-ink-subtle'], self::$light['neu-surface']);
        $darkRatio = $this->contrast(self::$dark['neu-ink-subtle'], self::$dark['neu-surface']);

        $this->assertGreaterThanOrEqual(3.0, $lightRatio, "ink-subtle/surface light: {$lightRatio}:1");
        $this->assertGreaterThanOrEqual(3.0, $darkRatio, "ink-subtle/surface dark: {$darkRatio}:1");
    }

    /**
     * Solid-fill semantic colours each pair with ONE fixed foreground
     * (--color-neu-on-*) regardless of page theme — see the comment above
     * these declarations in app.css for why the foreground isn't uniformly
     * white. Every pair must clear 4.5:1.
     */
    public function test_every_solid_fill_colour_meets_aa_with_its_paired_foreground(): void
    {
        $onColors = $this->extractBlockVariables(
            file_get_contents(resource_path('css/app.css')),
            '@theme',
        );

        $fills = ['primary', 'primary-bright', 'accent', 'info', 'violet', 'warning', 'danger'];

        foreach ($fills as $fill) {
            $bg = self::$light["neu-{$fill}"];
            $fg = $onColors["color-neu-on-{$fill}"];
            $ratio = $this->contrast($fg, $bg);

            $this->assertGreaterThanOrEqual(
                4.5,
                $ratio,
                sprintf('neu-%s (%s) with its foreground (%s) is only %.2f:1', $fill, $bg, $fg, $ratio),
            );
        }
    }

    public function test_ink_muted_on_surface_sunken_meets_aa(): void
    {
        // Regression guard: NeuPill's "ink-muted" tone originally paired
        // ink-muted text with a surface-sunken background at 4.49:1 — caught
        // by the Phase 02 browser a11y test, fixed by using plain ink
        // instead. This pins the fix.
        $ratio = $this->contrast(self::$light['neu-ink'], self::$light['neu-surface-sunken']);

        $this->assertGreaterThanOrEqual(4.5, $ratio);
    }
}
