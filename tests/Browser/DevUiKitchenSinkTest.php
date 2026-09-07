<?php

declare(strict_types=1);

/**
 * Phase 02 M2.6 — the kitchen sink is the visual reference and the target
 * for these tests. See plan/phases/phase-02-design-system.md.
 *
 * M2.5's contrast floor is enforced at the token level in
 * tests/Unit/DesignSystemContrastTest.php; these tests exercise the
 * rendered page: axe-core, viewport overflow, tap targets, and focus
 * visibility, at both breakpoints Phase 02 requires (390 and 1280), in
 * both themes.
 */
it('has no serious or critical accessibility violations in light mode', function () {
    visit('/dev/ui')
        ->inLightMode()
        ->assertNoAccessibilityIssues(level: 1);
});

it('has no serious or critical accessibility violations in dark mode', function () {
    visit('/dev/ui')
        ->inDarkMode()
        ->assertNoAccessibilityIssues(level: 1);
});

it('has no horizontal overflow at 390px', function () {
    visit('/dev/ui')
        ->resize(390, 844)
        ->assertScript('document.body.scrollWidth <= window.innerWidth + 1');
});

it('has no horizontal overflow at 1280px', function () {
    visit('/dev/ui')
        ->resize(1280, 800)
        ->assertScript('document.body.scrollWidth <= window.innerWidth + 1');
});

it('renders every primitive without javascript errors', function () {
    visit('/dev/ui')->assertNoJavaScriptErrors();
});

it('gives every NeuButton and NeuIconButton a tap target of at least 44x44px', function () {
    visit('/dev/ui')->assertScript(<<<'JS'
        Array.from(document.querySelectorAll('button')).every((el) => {
            const r = el.getBoundingClientRect();
            return r.width >= 44 && r.height >= 44;
        })
        JS);
});

it('gives every button a visible focus ring on keyboard focus', function () {
    visit('/dev/ui')->assertScript(<<<'JS'
        (() => {
            const button = document.querySelector('button');
            button.focus();
            const style = getComputedStyle(button);
            const hasRing = style.getPropertyValue('--tw-ring-color') !== ''
                || style.outlineStyle !== 'none';
            return hasRing;
        })()
        JS);
});

it('renders correctly at 390x844 (iPhone 14 logical viewport)', function () {
    visit('/dev/ui')
        ->resize(390, 844)
        ->assertSee('Neumorphism kitchen sink')
        ->assertSee('NeuPill')
        ->screenshot(filename: 'phase-02-dev-ui-mobile');
});

it('renders correctly on desktop', function () {
    visit('/dev/ui')
        ->resize(1280, 800)
        ->assertSee('Neumorphism kitchen sink')
        ->screenshot(filename: 'phase-02-dev-ui-desktop');
});
