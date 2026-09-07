# 03 — Neumorphism Design System

The brief: **the UI must be Neumorphism.** The poster: a navy/blue corporate palette with
soft white cards. This document reconciles the two and pins down the tokens so Phase 02
is implementation, not deliberation.

---

## 1. What Neumorphism is here

One surface colour shared by the page and its elements, with depth carried entirely by a
paired shadow: a **light shadow up-left** and a **dark shadow down-right**. Elements are
either **raised** (extruded from the surface), **inset** (pressed into it), or **flat**.

```
raised   box-shadow:  6px  6px 12px var(--neu-shadow-dark),
                     -6px -6px 12px var(--neu-shadow-light);
inset    box-shadow: inset 4px 4px 8px var(--neu-shadow-dark),
                     inset -4px -4px 8px var(--neu-shadow-light);
```

Three depth steps only — `sm` (4px), `md` (6px), `lg` (10px). More steps produce mush.

### 1.1 The rule that keeps this accessible

> **The surface may be soft. The content may not.**

- Every text string, numeric value and icon meets **WCAG AA 4.5:1** against its surface.
- Depth is never the *only* signal. A pressed toggle also changes label weight or shows a
  check. A disabled button also drops opacity and gets `aria-disabled`.
- Every focusable element gets a **2px solid accent focus ring with a 2px offset** —
  a real ring, not a shadow change, because shadow-only focus is invisible to many users
  and to `prefers-contrast: more`.
- `@media (prefers-reduced-motion)` disables the press animation.
- `@media (prefers-contrast: more)` swaps neumorphic shadows for **1px solid borders**
  across the board. This is a first-class supported mode, implemented in Phase 02 (M2.4),
  not an afterthought.

Phase 02 M2.5 runs an automated contrast audit over every token pair. It is a gate.

---

## 2. Tokens

Declared in `resources/css/app.css` inside Tailwind v4's `@theme` block, so every token is
available as a utility (`bg-surface`, `text-ink`, `shadow-neu-md`).

### 2.1 Light theme (default)

| Token | Value | Use |
|-------|-------|-----|
| `--color-surface` | `#e8ecf3` | Page background **and** card background — they must match |
| `--color-surface-raised` | `#eef1f7` | Slightly lifted panels |
| `--color-surface-sunken` | `#dfe4ee` | Inset wells, input interiors |
| `--neu-shadow-light` | `#ffffff` | Up-left highlight |
| `--neu-shadow-dark` | `#b8c1d1` | Down-right shadow |
| `--color-ink` | `#1b2436` | Primary text — 12.6:1 on surface ✅ |
| `--color-ink-muted` | `#5a6683` | Labels, captions — 4.9:1 ✅ |
| `--color-ink-subtle` | `#7c88a3` | Hints only, never body text — 3.4:1, **large text only** |

### 2.2 Brand & semantic (sampled from `Picture1.png`)

| Token | Value | Source in poster |
|-------|-------|------------------|
| `--color-primary` | `#0f3d7c` | Header bar navy |
| `--color-primary-bright` | `#1f6fd0` | Coordinates tile, active tab, links |
| `--color-accent` | `#12b981` | MySpatial green / `ACTIVE` pill / As-Built tile |
| `--color-info` | `#17a3c7` | Site Photos tile (cyan) |
| `--color-violet` | `#6d5ce7` | 360° View tile |
| `--color-warning` | `#e0a13a` | `Pending` states |
| `--color-danger` | `#d94b4b` | `Damaged` / `Rejected` |

Module tile colours are fixed by the poster and belong to the four modules permanently —
Coordinates is blue, As-Built green, Site Photos cyan, 360° violet. They are the primary
wayfinding cue and must not be re-themed.

### 2.3 Dark theme

Neumorphism in dark mode is genuinely hard: the "light" shadow must be a lifted grey, not
white, or everything glows.

| Token | Value |
|-------|-------|
| `--color-surface` | `#232833` |
| `--color-surface-raised` | `#282e3b` |
| `--color-surface-sunken` | `#1c2028` |
| `--neu-shadow-light` | `#2e3542` |
| `--neu-shadow-dark` | `#171a21` |
| `--color-ink` | `#e8ecf3` |
| `--color-ink-muted` | `#a3adc2` |

Follows the system via `prefers-color-scheme`, with a manual override persisted in
`localStorage` and applied as `data-theme` on `<html>` before first paint (inline script
in the Blade root, to avoid a flash).

### 2.4 Type scale

System font stack — no webfont. It is faster, and a field app has no brand reason to pay
300 KB for one.

| Role | Size / weight | Notes |
|------|---------------|-------|
| Page title | 24 / 700 | `GCP STATION: LPT2-GCP-015` |
| Section header | 13 / 700, `0.08em` tracking, uppercase | `GNSS OBSERVATION` |
| Data value | 17 / 600, **tabular numerals** | `4.27412582 °` |
| Data label | 14 / 400, `--color-ink-muted` | `Latitude` |
| Caption | 12 / 400 | |

**`font-variant-numeric: tabular-nums` on every numeric value.** Coordinates read as
columns of digits; proportional figures make them jitter and misread. This is a
correctness concern, not a typographic nicety.

### 2.5 Spacing, radius, motion

- Spacing: 4px base — `4 8 12 16 24 32 48`.
- Radius: `--radius-sm 12px`, `--radius-md 18px`, `--radius-lg 26px`, `--radius-pill 999px`.
  Neumorphism needs generous radii; the shadow pair reads as a hard edge below ~10px.
- Motion: `150ms ease-out` for press, `250ms` for route transitions. Nothing longer.

---

## 3. Component inventory (built in Phase 02)

| Component | Description | Used by |
|-----------|-------------|---------|
| `NeuCard` | Raised container. `variant: raised \| inset \| flat`, `depth: sm \| md \| lg` | Everywhere |
| `NeuTile` | Large coloured module tile with icon + label | Overview module grid |
| `NeuStat` | Label / value / unit row with tabular numerals | Coordinates, Specs |
| `NeuGroup` | Titled group of `NeuStat`s | `WGS 84 (GPS)`, `ACCURACY (RMS)` |
| `NeuPill` | Status pill, semantic-coloured | `ACTIVE`, `VERIFIED` |
| `NeuButton` | `variant: primary \| ghost \| danger`, press = inset | All actions |
| `NeuIconButton` | 44×44 min, icon only | Map controls, carousel arrows |
| `NeuInput` / `NeuSelect` / `NeuTextarea` | Inset fields with visible labels | Admin forms, unlock gate |
| `NeuTabs` | Radix Tabs, neumorphic skin | Desktop dossier nav |
| `NeuBottomNav` | Fixed 4-item bar, safe-area aware | Mobile dossier nav |
| `NeuSheet` | Radix Dialog as a bottom sheet on mobile | Photo detail, filters |
| `NeuSkeleton` | Shimmer placeholder | Deferred-prop loading |
| `NeuEmptyState` | Icon + message + optional action | Stations with no photos/files |
| `MetaChip` | Icon + label + value, horizontally scrollable | The Highway/KM/Section/Direction row |

Each ships with props typed, a story on the kitchen-sink page (`/dev/ui`, local only), and
at least one browser test at 390px.

---

## 4. Mobile-first rules

The poster's own mockups are phones. Design at **390 × 844** and treat desktop as
progressive enhancement.

| Breakpoint | Layout |
|-----------|--------|
| `< 640px` | Single column. Fixed bottom tab bar. Module tiles 2×2. Meta row scrolls horizontally. Map full-bleed. |
| `640–1023px` | Two-column data groups. Bottom bar becomes a top tab strip. Module tiles 4×1. |
| `≥ 1024px` | Centred `max-w-5xl`. Overview becomes two columns (map + quick view side by side) exactly as the poster's panel 1. |

Non-negotiables:

- Tap targets ≥ 44 × 44 px with ≥ 8px between them.
- `env(safe-area-inset-bottom)` padding on the bottom nav (iPhone home indicator).
- No horizontal page scroll at any width — a browser test asserts
  `document.body.scrollWidth <= window.innerWidth` on every public route.
- Images `loading="lazy"` with explicit `width`/`height` to prevent layout shift.
- Numbers never wrap mid-value: `white-space: nowrap` on `NeuStat` values.

---

## 5. Layouts

**`DossierLayout`** (public) — sticky header (`MYSPATIAL | DIGITAL MONUMENT DOSSIER` +
shield icon), page content, fixed bottom nav on mobile. Read-only: no forms, no
destructive actions, nothing that could imply editability.

**`AdminLayout`** — same tokens, collapsible sidebar, breadcrumb, toast region.

**`GuestLayout`** — centred single card. Used by admin login and the dossier unlock gate.

---

## 6. Reference screens

Phase 02 delivers `/dev/ui` (registered only when `app()->environment('local')`), showing
every primitive in every variant, in light and dark, at 390px and 1280px. It is the visual
regression target and the first thing to open when a component looks wrong.
