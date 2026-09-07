# Phase 02 — Neumorphism Design System

| | |
|---|---|
| **Status** | ⬜ Not started |
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
| [ ] | **M2.1** | Tailwind v4 `@theme` token layer: colours, shadows, radii, spacing, motion — light theme required, dark theme `[OPTIONAL]` | | |
| [ ] | **M2.2** | Core primitives: `NeuCard`, `NeuButton`, `NeuIconButton`, `NeuPill`, `NeuStat`, `NeuGroup` | | |
| [ ] | **M2.3** | Navigation + layout: `DossierLayout`, `AdminLayout`, `GuestLayout`, `NeuBottomNav`, `NeuTabs`, `MetaChip` | | |
| [ ] | **M2.4** | Form + feedback: `NeuInput`, `NeuSelect`, `NeuTextarea`, `NeuSheet`, `NeuSkeleton`, `NeuEmptyState`; reduced-motion and high-contrast modes | | |
| [ ] | **M2.5** | Automated contrast + a11y audit over every token pair and every primitive | | |
| [ ] | **M2.6** | `/dev/ui` kitchen-sink page (local only) + browser tests at 390px and 1280px, light and dark | | |

---

> **Build just-in-time.** M2.1 and M2.2 must be complete before Phase 03 starts. M2.3 and
> M2.4 may be finished *during* Phases 03–08 as each primitive is first needed — but every
> primitive must land on `/dev/ui` with its tests the moment it is created, and M2.5/M2.6
> must be green before Phase 08 begins. This avoids three days of building components that
> may never be used.

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

## Sign-Off

| | |
|---|---|
| **Gate run on** | |
| **Result** | |
| **Contrast report** | |
| **Screenshots** | `plan/evidence/phase-02/` |
| **Commit / tag** | |

## Phase Log

_Append one dated line per completed milestone._
