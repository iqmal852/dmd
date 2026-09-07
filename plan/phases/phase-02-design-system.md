# Phase 02 — Neumorphism Design System

| | |
|---|---|
| **Status** | 🟡 In progress — M2.1/M2.2 done, M2.5/M2.6 green for current primitives, M2.3/M2.4 deferred per the just-in-time rule below |
| **Depends on** | Phase 00 |
| **Estimate** | 3 days |
| **Tag on completion** | `phase-02-complete` |

## Goal

Every visual primitive the rest of the build needs, implemented once, accessible, themed
light + dark, verified at 390px. After this phase nobody writes a raw `box-shadow` again.

Reference: [`../03-design-system.md`](../03-design-system.md).

---

## Milestones

| | ID | Deliverable | Date | Evidence |
|---|---|---|---|---|
| [x] | **M2.1** | Tailwind v4 `@theme` token layer: colours, shadows, radii, spacing, motion — light theme required, dark theme built too (reusing the starter kit's existing `.dark` toggle, see deviation) | 2026-09-07 | `resources/css/app.css`, `tests/Unit/DesignSystem/ContrastTest.php` |
| [x] | **M2.2** | Core primitives: `NeuCard`, `NeuButton`, `NeuIconButton`, `NeuPill`, `NeuStat`, `NeuGroup` | 2026-09-07 | `resources/js/components/neu/*.tsx`, all on `/dev/ui` |
| [ ] | **M2.3** | Navigation + layout: `DossierLayout`, `AdminLayout`, `GuestLayout`, `NeuBottomNav`, `NeuTabs`, `MetaChip` | | Deferred — built during Phase 03 when the dossier shell needs them |
| [ ] | **M2.4** | Form + feedback: `NeuInput`, `NeuSelect`, `NeuTextarea`, `NeuSheet`, `NeuSkeleton`, `NeuEmptyState`; reduced-motion and high-contrast modes | | Deferred — built during Phases 04-08 as each is first needed |
| [~] | **M2.5** | Automated contrast + a11y audit over every token pair and every primitive | 2026-09-07 | `tests/Unit/DesignSystem/ContrastTest.php` (5 tests, 56 assertions) green for all tokens; re-run/extend as M2.3/M2.4 primitives land |
| [~] | **M2.6** | `/dev/ui` kitchen-sink page (local only) + browser tests at 390px and 1280px, light and dark | 2026-09-07 | `tests/Browser/DevUiKitchenSinkTest.php` (9 tests) green for current primitives; extend as M2.3/M2.4 land |

---

> **Build just-in-time.** M2.1 and M2.2 must be complete before Phase 03 starts. M2.3 and
> M2.4 may be finished *during* Phases 03–08 as each primitive is first needed — but every
> primitive must land on `/dev/ui` with its tests the moment it is created, and M2.5/M2.6
> must be green before Phase 08 begins. This avoids three days of building components that
> may never be used.

> **Deviation (2026-09-07):** The starter kit already ships a full shadcn/ui token
> system (`--background`, `--foreground`, `--primary`, `--accent`, `--radius-*`, etc.)
> powering the pre-existing auth/settings pages, plus a working light/dark toggle
> (`useAppearance()` / `.dark` class on `<html>`, no flash on load). Rather than the
> `data-theme` attribute mechanism and unprefixed token names originally drafted here,
> every Neumorphism token is namespaced `--neu-*` / `--color-neu-*` so it can never
> collide with the shadcn slots, and dark mode reuses the existing `.dark` class
> toggle instead of inventing a second mechanism. Dark theme was therefore built now
> rather than left `[OPTIONAL]`, since it was nearly free given the reused toggle.
>
> **Deviation (2026-09-07):** The literal hex values drafted in `03-design-system.md`
> were not all AA-compliant once actually contrast-checked. White text on the poster's
> own accent green (#12b981) is 2.54:1, on its amber warning (#e0a13a) is 2.25:1, on
> its info cyan (#17a3c7) is 2.96:1 — all real AA failures, not edge cases. Fixed by
> pairing each solid-fill semantic colour with ONE deliberately-chosen fixed
> foreground (`--color-neu-on-*`): dark ink for accent/info/warning, white for
> primary/primary-bright/violet/danger (danger's hex nudged from #d94b4b to #d33f3f
> so white on it clears 4.5:1). `neu-primary-bright` was found unsafe as body text on
> the surface in either theme (4.18:1 light, 2.98:1 dark) — a new theme-swapping
> `--neu-link` token carries "safe text on surface" instead (navy in light, a lighter
> blue in dark, since navy-on-dark-surface is only 1.39:1). Full ratios are documented
> inline in `resources/css/app.css`. This is exactly what M2.5's gate exists to catch,
> and it caught real problems.
>
> **Deviation (2026-09-07):** `resources/js/components/ui/` is already the shadcn
> component folder (button.tsx, card.tsx, input.tsx, etc.), so every Neu primitive
> lives in a new `resources/js/components/neu/` folder instead, to avoid filename
> collisions and import ambiguity. `01-architecture.md`'s repository layout should be
> read as `components/neu/` for these, not `components/ui/`.
>
> **Deviation (2026-09-07):** `pestphp/pest-plugin-browser` was not yet installed
> (not in the starter kit, and Boost's own testing-best-practices skill explicitly
> said not to write browser tests without it). Installed it plus `playwright` +
> Chromium (`npx playwright install chromium`) to satisfy M2.6's literal requirement
> for real browser tests. `phpunit.xml` did not declare a `Browser` testsuite at all
> — `composer test` was silently running 0 of the 9 browser tests until this was
> fixed; `.github/workflows/tests.yml` gained a Playwright install step to match.
> This is the single most important catch of this phase: a test suite that silently
> doesn't run is worse than no test suite, because it looks green.
>
> **A real bug the a11y test caught:** `NeuPill`'s `ink-muted` tone originally paired
> `--color-neu-ink-muted` text with a `--color-neu-surface-sunken` background at
> **4.49:1** — under the 4.5:1 floor by a hair, invisible to a manual read, caught
> immediately by `assertNoAccessibilityIssues()` on the first browser-test run. Fixed
> by using plain `ink` (12.18:1) for that tone instead; pinned with a regression test
> in `ContrastTest::test_ink_muted_on_surface_sunken_meets_aa`.

## Milestone detail

### M2.1 — Tokens

`resources/css/app.css`:

```css
@import "tailwindcss";

@theme {
  --color-surface:        #e8ecf3;
  --color-surface-raised: #eef1f7;
  --color-surface-sunken: #dfe4ee;
  --color-ink:            #1b2436;
  --color-ink-muted:      #5a6683;
  --color-ink-subtle:     #7c88a3;

  --color-primary:        #0f3d7c;
  --color-primary-bright: #1f6fd0;
  --color-accent:         #12b981;
  --color-info:           #17a3c7;
  --color-violet:         #6d5ce7;
  --color-warning:        #e0a13a;
  --color-danger:         #d94b4b;

  --neu-light: #ffffff;
  --neu-dark:  #b8c1d1;

  --shadow-neu-sm: 3px 3px 6px var(--neu-dark), -3px -3px 6px var(--neu-light);
  --shadow-neu-md: 6px 6px 12px var(--neu-dark), -6px -6px 12px var(--neu-light);
  --shadow-neu-lg: 10px 10px 20px var(--neu-dark), -10px -10px 20px var(--neu-light);
  --shadow-neu-inset-sm: inset 2px 2px 5px var(--neu-dark), inset -2px -2px 5px var(--neu-light);
  --shadow-neu-inset-md: inset 4px 4px 8px var(--neu-dark), inset -4px -4px 8px var(--neu-light);

  --radius-sm: 12px; --radius-md: 18px; --radius-lg: 26px;
}

[data-theme="dark"] {
  --color-surface: #232833; --color-surface-raised: #282e3b; --color-surface-sunken: #1c2028;
  --color-ink: #e8ecf3; --color-ink-muted: #a3adc2; --color-ink-subtle: #7f8ba1;
  --neu-light: #2e3542; --neu-dark: #171a21;
}

@media (prefers-contrast: more) {
  :root { --shadow-neu-sm: none; --shadow-neu-md: none; --shadow-neu-lg: none; }
  .neu { border: 1px solid var(--color-ink-muted); }
}

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
}
```

**Dark theme is `[OPTIONAL]`** — it is not in the poster. If implemented, theme resolution
runs as an inline script in the Blade root **before** first paint, reading
`localStorage.theme` and falling back to `prefers-color-scheme`, setting `data-theme` on
`<html>`, with no flash of wrong theme. If skipped, delete the `[data-theme="dark"]` block
and every "both themes" assertion below applies to light only.

### M2.2 — Core primitives

Built with `cva` for variants, `forwardRef`, and fully typed props. Example contract:

```tsx
type NeuCardProps = {
  variant?: 'raised' | 'inset' | 'flat';
  depth?: 'sm' | 'md' | 'lg';
  as?: React.ElementType;
} & React.HTMLAttributes<HTMLElement>;
```

`NeuStat` is the workhorse — it renders the Label / Value / Unit rows that make up both
poster screens in panel 2:

```tsx
<NeuStat label="Latitude" value="4.27412582" unit="°" />
<NeuStat label="Easting (E)" value="428,765.212" unit="m" />
```

It applies `font-variant-numeric: tabular-nums` and `white-space: nowrap` to the value,
and puts the label in `--color-ink-muted`. `NeuGroup` wraps a set of them under an
uppercase tracked heading (`WGS 84 (GPS)`, `ACCURACY (RMS)`).

`NeuButton` press state switches from `--shadow-neu-md` to `--shadow-neu-inset-sm` over
150ms, **and** shifts the label weight — depth is never the only affordance.

### M2.3 — Navigation and layouts

`NeuBottomNav` — fixed, four items (Overview · Coordinates · Files · Photos) exactly as
the poster's phone mockups, 56px tall plus `env(safe-area-inset-bottom)`, active item
inset with the accent colour, `aria-current="page"`.

`NeuTabs` — Radix Tabs skinned neumorphically; used instead of the bottom bar at ≥ 640px.

`MetaChip` — the Highway / KM / Section / Direction cells from the poster's title block.
On mobile they live in a horizontally scrollable row with scroll-snap and no visible
scrollbar; at ≥ 640px they lay out as a 4-column grid.

`DossierLayout` — sticky header carrying the `MYSPATIAL | DIGITAL MONUMENT DOSSIER` brand
lockup (from `config('dossier.branding')`, not hard-coded) plus the shield icon, then
`<main>`, then the bottom nav. Adds `padding-bottom` equal to the nav height so content
is never hidden behind it.

### M2.4 — Forms, feedback, and accessible modes

Inputs are **inset** surfaces with a persistent visible label (never placeholder-only —
placeholder-as-label fails on a bright roadside screen and for screen readers). Error
state adds a danger-coloured left border **and** text, not just a colour change.

`NeuSheet` — Radix Dialog; bottom sheet under 640px, centred modal above. Focus trapped,
Escape closes, background scroll locked.

`NeuSkeleton` — shimmer that respects `prefers-reduced-motion` by falling back to a static
tint.

`NeuEmptyState` — icon + message + optional action. Used wherever a station has no photos,
no documents, or no specification.

### M2.5 — The accessibility gate

This is the milestone that keeps Neumorphism honest.

1. **Token contrast test** (Vitest or a Node script in CI): computes WCAG contrast for
   every `--color-ink*` against every `--color-surface*`, in both themes, and fails if a
   pair used for body text is below **4.5:1** or a large-text pair below **3:1**.
2. **axe-core** run over the `/dev/ui` page and every layout, in both themes. Zero
   violations at `serious` or `critical`.
3. **Focus visibility test**: tab through `/dev/ui`; every focusable element must have a
   computed outline of ≥ 2px that is not `none`.
4. **Tap target test**: every element with a click handler measures ≥ 44 × 44 px at 390px.

> If a token fails, **change the token**, do not add an exception. The palette exists to be
> adjusted; the contrast floor does not.

### M2.6 — Kitchen sink

`/dev/ui`, registered only when `app()->environment('local')`. Renders every primitive in
every variant and state, with a light/dark toggle and a width switcher.

Browser tests (Pest 4/5 browser testing) visit it at 390×844 and 1280×800, in both themes,
and assert no horizontal overflow and no axe violations. Screenshots are saved to
`plan/evidence/phase-02/` and are the visual reference for later phases.

---

## Test Gate

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
npm run types && npm run lint && npm run build
php artisan test --filter='Ui|DesignSystem|Contrast'
npm run test:contrast
```

Assertions that must exist:

1. Every ink/surface token pair used for body text meets 4.5:1 in **both** themes.
2. axe-core reports zero serious/critical violations on `/dev/ui` in both themes.
3. `/dev/ui` has no horizontal overflow at 390px.
4. Every interactive primitive has a visible focus ring ≥ 2px.
5. Every interactive primitive is ≥ 44×44px at 390px.
6. `prefers-reduced-motion` removes transitions (asserted via computed style).
7. `/dev/ui` returns **404** when `APP_ENV=production`.

---

## Definition of Done

- [ ] Every component in [`../03-design-system.md`](../03-design-system.md) §3 exists, is typed, and appears on `/dev/ui`.
- [ ] Light and dark both look correct; no flash of wrong theme on load.
- [ ] `prefers-contrast: more` produces a bordered, shadow-free variant that is fully usable.
- [ ] Zero hard-coded hex colours outside `app.css`.
- [ ] Screenshots committed to `plan/evidence/phase-02/`.

---

## Sign-Off — partial (M2.1, M2.2, M2.5, M2.6 for current primitives)

| | |
|---|---|
| **Gate run on** | 2026-09-07 |
| **Result** | `composer test` → Pint passed, PHPStan level 7 (0 errors), Pest **82/82** passed (315 assertions) — includes 5 new token-level contrast tests (56 assertions) and 9 new real-Chromium browser tests against `/dev/ui` (axe-core in both themes, viewport overflow at 390/1280, tap targets, focus visibility, JS-error-free render). `npm run check`/`types:check`/`build` all clean. Full CI sequence (`composer setup` → Playwright install → `composer ci:check`) simulated locally against a scratch `batu_test` database. |
| **Contrast report** | Computed programmatically in `tests/Unit/DesignSystem/ContrastTest.php` directly from `resources/css/app.css`; every ratio is also documented inline in the CSS file next to the tokens it describes. Light ink/surface 13.11:1, ink-muted/surface 4.84:1, ink-subtle/surface 3.00:1, link/surface 8.96:1; dark equivalents 12.46:1 / 6.55:1 / 4.29:1 / 5.43:1; all seven solid-fill semantic colours ≥4.5:1 with their paired foreground. |
| **Screenshots** | `plan/evidence/phase-02/phase-02-dev-ui-desktop.png`, `plan/evidence/phase-02/phase-02-dev-ui-mobile.png` |
| **Commit / tag** | Pending commit. **Not tagging `phase-02-complete` yet** — by design, per the "build just-in-time" rule: M2.3/M2.4 and the rest of M2.5/M2.6 close out just before Phase 08. |

## Phase Log

- **2026-09-07** — M2.1: Full `--neu-*`/`--color-neu-*` token layer added to `resources/css/app.css` alongside (not replacing) the shadcn tokens the starter kit already ships. Computed real WCAG ratios before committing to hex values, found and fixed several AA failures in the values drafted in `03-design-system.md` (see Deviations). Reused the existing `.dark`-class appearance toggle rather than building a second theme mechanism.
- **2026-09-07** — M2.2: `NeuCard`, `NeuButton`, `NeuIconButton`, `NeuPill`, `NeuStat`, `NeuGroup` built in a new `components/neu/` folder (see Deviation on why not `components/ui/`), using `cva` for variants exactly as `01-architecture.md` ADR-005 specified. `NeuPill`'s tone names match `App\Contracts\HasColor::color()` output verbatim, so a status pill is `<NeuPill tone={station.statusColor}>`.
- **2026-09-07** — M2.5/M2.6: Installed `pestphp/pest-plugin-browser` + Playwright + Chromium (none of which existed in the project yet), built `/dev/ui` exercising every M2.2 primitive at three widths with a light/dark toggle (reusing `useAppearance()`), and wrote both the token-level contrast audit and the real-browser a11y/overflow/tap-target/focus suite. Caught and fixed a real contrast bug (`NeuPill`'s `ink-muted` tone, 4.49:1) and a real test-infrastructure bug (`phpunit.xml` had no `Browser` testsuite, so 9 browser tests were silently not running under `composer test`) — both are the kind of finding this milestone exists to produce. Screenshots saved to `plan/evidence/phase-02/`.
- **Remaining for this phase, deferred to Phases 03-08 per the just-in-time rule:** M2.3 (`DossierLayout`, `AdminLayout`, `GuestLayout`, `NeuBottomNav`, `NeuTabs`, `MetaChip`) and M2.4 (`NeuInput`, `NeuSelect`, `NeuTextarea`, `NeuSheet`, `NeuSkeleton`, `NeuEmptyState`). Each lands on `/dev/ui` with its own tests the moment it's built; M2.5/M2.6 are re-run and extended each time. This phase's tag and final sign-off come right before Phase 08.
