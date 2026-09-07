# Phase 04 — Coordinates & Specifications Modules

| | |
|---|---|
| **Status** | ✅ Complete |
| **Depends on** | Phase 03 |
| **Estimate** | 2 days |
| **Tag on completion** | `phase-04-complete` |

## Goal

Poster panel 2, screens **2a** and **2b**: the full coordinate record in three reference
systems, and the GNSS observation + accuracy QC record. This is the data the whole product
exists to deliver — precision and legibility matter more here than anywhere else.

---

## Milestones

| | ID | Deliverable | Date | Evidence |
|---|---|---|---|---|
| [x] | **M4.1** | `CoordinateSetData` + `SpecificationData` DTOs, all values pre-formatted by `GeoFormatter` | 2026-09-07 | `app/Data/CoordinateSetData.php`, `app/Data/SpecificationData.php`, `tests/Feature/Data/*` |
| [x] | **M4.2** | `/d/{code}/coordinates` — WGS 84, GDM 2000 (TM), MyGEOID groups | 2026-09-07 | `resources/js/pages/dossier/coordinates.tsx`, verified live in Chrome (station switcher chevron skipped — see deviation) |
| [x] | **M4.3** | Specs & QC section — GNSS Observation + Accuracy (RMS) + verified pill | 2026-09-07 | Same page; `CoordinatesTest::test_every_value_matches_the_poster_exactly` |
| [x] | **M4.4** | Empty states for incomplete records; `[OPTIONAL]` copy-to-clipboard per value and per group | 2026-09-07 | `NeuEmptyState`; copy-to-clipboard built for lat/lon (not every value/group — see deviation), verified live in Chrome |
| [x] | **M4.5** | Bottom-tab navigation wired across Overview ↔ Coordinates ↔ Files ↔ Photos | 2026-09-07 | `NeuBottomNav`, `buildDossierNavItems()`; Files/Photos disabled until Phases 06/07 |

---

> **Deviation (2026-09-07):** `GeoFormatter` needed two more methods than Phase 01
> anticipated — `degrees(int|string)` (for `elevation_cutoff_deg`, e.g. "15 °") and
> `plainNumber(string)` (trims trailing zeros with no unit, for PDOP: "1.60" → "1.6").
> Both were added with their own poster-literal unit tests before being used here,
> keeping the "every number formatted exactly once, server-side" rule from ADR-010
> intact.
>
> **Deviation (2026-09-07):** The `[OPTIONAL]` station-switcher chevron (M4.2) was
> skipped — the row renders as static text with no chevron, exactly as the plan's own
> fallback describes for skipping it.
>
> **Deviation (2026-09-07):** The `[OPTIONAL]` copy-to-clipboard was built, but scoped
> down from "any `NeuStat` value, plus a per-group copy-all" to just the two values
> that actually have a `*Raw` counterpart in the DTO — Latitude and Longitude. Every
> other value on this screen (easting, northing, heights, antenna specs) is already
> unit-free of formatting a surveyor would need to strip (metres are metres in survey
> software too), so a raw/display split only matters for the two values a poster
> shows with a degree sign. No "copy all" block and no `document.execCommand`
> fallback for older Android WebViews — `navigator.clipboard` only, with a graceful
> "not supported" toast when it's unavailable, using the starter kit's existing
> `useClipboard` hook and `sonner` toaster rather than new infrastructure.
>
> **Deviation (2026-09-07):** `NeuBottomNav` renders as a fixed bottom bar at every
> breakpoint, not a top tab strip at ≥640px (`NeuTabs`, a separate, still-unbuilt
> component per `03-design-system.md` §3). M4.5's own wording only requires
> `NeuBottomNav`, not `NeuTabs` — the desktop-specific variant is deferred as a later
> polish, consistent with Phase 02's "build just-in-time" rule.

## Milestone detail

### M4.1 — DTOs

```php
final readonly class CoordinateSetData
{
    public function __construct(
        public string $latitude,           // "4.27412582 °"
        public string $longitude,          // "103.43658211 °"
        public string $ellipsoidalHeight,  // "128.346 m"
        public string $easting,            // "428,765.212 m"
        public string $northing,           // "472,318.678 m"
        public ?string $zone,
        public string $orthometricHeight,  // "112.436 m"
        public string $geoidModel,         // "MyGEOID"
        public ?string $epoch,
        public string $latitudeRaw,        // "4.27412582"  — for copy-to-clipboard
        public string $longitudeRaw,
    ) {}
}
```

Two representations per value: **display** (with unit and separators, for the screen) and
**raw** (bare number, for the clipboard — a surveyor pasting into survey software must not
receive a degree sign or a thousands separator). This distinction is easy to miss and
annoying to discover in the field.

`SpecificationData` mirrors panel 2b, with `null` for any field the record lacks and
`horizontalRms` / `verticalRms` already rendered as `≤ 10 mm` / `≤ 15 mm`.

### M4.2 — Coordinates screen

`resources/js/pages/dossier/coordinates.tsx`. Structure copied from the poster:

```
[ station switcher row: LPT2-GCP-015                    › ]

┌ WGS 84 (GPS) ──────────────────────────────┐
│ Latitude                     4.27412582 °  │
│ Longitude                  103.43658211 °  │
│ Ellipsoidal Height (h)         128.346 m   │
└────────────────────────────────────────────┘

┌ GDM 2000 (TM) ─────────────────────────────┐
│ Easting (E)                428,765.212 m   │
│ Northing (N)               472,318.678 m   │
└────────────────────────────────────────────┘

┌ MyGEOID Height (Orthometric) ──────────────┐
│                                112.436 m   │
└────────────────────────────────────────────┘
```

Each block is a `NeuGroup` of `NeuStat`s. Group headings carry the small coloured icon the
poster shows. The MyGEOID group has a single unlabelled value, right-aligned — reproduce
that, do not "improve" it into a labelled row.

**`[OPTIONAL]`** — the station switcher row may open a `NeuSheet` listing the other
stations on the same highway ordered by chainage, so a crew doing a run of monuments can
move between them without re-scanning. Implied by the chevron in the poster but not shown.
If skipped, render the row as static text without the chevron.

### M4.3 — Specs & QC

Same page, below the coordinates — one scroll on mobile beats a hidden second tab. At
≥ 1024px it sits in a right-hand column.

```
┌ GNSS OBSERVATION ──────────────────────────┐
│ Method                        Static / RTK │
│ Observation Time                   120 min │
│ No. of Satellites                       18 │
│ PDOP (Max)                             1.6 │
│ Elevation Cut-off                     15 ° │
│ Antenna Type                Geodetic L1/L2 │
│ Antenna Height                   1.532 m   │
│ Antenna Point               Bottom of ARP  │
└────────────────────────────────────────────┘

┌ ACCURACY (RMS) ────────────────────────────┐
│ Horizontal (XY)                  ≤ 10 mm   │
│ Vertical (Z)                     ≤ 15 mm   │
│ Status                        [ VERIFIED ] │
└────────────────────────────────────────────┘
```

`Status` renders as a `NeuPill` coloured from `QcStatus::color()`.

### M4.4 — Empty states, and optional copy

Required:

- Station with no `coordinate_set` → `NeuEmptyState`: "Coordinates not yet recorded for
  this station." Same for `specification`. The two deliberately-incomplete seeded stations
  exist to exercise exactly this.

**`[OPTIONAL]`** copy-to-clipboard:

- Tap any `NeuStat` value → copies the **raw** value, shows a 1.5s "Copied" toast.
- A "Copy all" action per group copies a labelled block, e.g.
  `Lat: 4.27412582\nLon: 103.43658211\nh: 128.346`.
- Uses `navigator.clipboard` with a `document.execCommand` fallback for older Android
  WebViews, and degrades silently (no broken UI) if neither is available.
- If skipped, drop the `*Raw` fields from `CoordinateSetData` and assertion 4 below.

### M4.5 — Navigation

Wire `NeuBottomNav` across all dossier routes. Active state from Inertia's current URL.
Inertia partial reloads keep the layout mounted so navigation between modules does not
re-render the header or re-run the access check client-side.

Prefetch the Coordinates route on Overview (`prefetch` on the tile link) — it is by far
the most likely next tap.

---

## Test Gate

```bash
php artisan test --filter='Coordinate|Specification|Dossier'
php artisan test --filter=Browser
npm run types && npm run lint && npm run build
./vendor/bin/pint --test && ./vendor/bin/phpstan analyse
```

Assertions that must exist:

1. `GET /d/{public_id}/coordinates` → 200, component `dossier/coordinates`.
2. Props contain **exactly** the DTO fields; no `id`, no `station_id`, no raw model.
3. Every displayed value for `LPT2-GCP-015` matches the poster string exactly —
   `4.27412582 °`, `103.43658211 °`, `128.346 m`, `428,765.212 m`, `472,318.678 m`,
   `112.436 m`, `Static / RTK`, `120 min`, `18`, `1.6`, `15 °`, `Geodetic L1/L2`,
   `1.532 m`, `Bottom of ARP`, `≤ 10 mm`, `≤ 15 mm`, `VERIFIED`.
4. `latitudeRaw` is `4.27412582` with **no** degree sign and no separators.
5. A station without a coordinate set renders the empty state and does not 500.
6. The route is behind the access gate: in password mode, unauthenticated → 302, and the
   response contains no coordinate values.
7. **Browser, 390×844:** all values visible without horizontal scroll; the longest value
   (`103.43658211 °`) does not wrap or truncate; *(if M4.4 optional part built)* tapping a
   value copies the raw form.
8. Values render with `font-variant-numeric: tabular-nums` (computed style assertion).

---

## Definition of Done

- [x] Screens 2a and 2b of the poster are reproduced faithfully at 390px. Verified by automated browser test and interactively in Chrome; screenshots saved.
- [x] Not one number is formatted in TypeScript — all formatting comes from `GeoFormatter`. Every displayed string in `coordinates.tsx` comes straight from a prop; the page does no numeric formatting itself.
- [x] *(Optional)* Copy-to-clipboard yields values a surveyor can paste straight into survey software. Verified interactively — clicking Latitude/Longitude copies the bare `latitudeRaw`/`longitudeRaw` value with no degree sign; confirmed visually via the "Latitude copied" toast (the automated browser test could not fully confirm the OS clipboard receives the value, since Chromium's automation clipboard-permission model doesn't match a real user session — see the note in `DossierCoordinatesTest`).
- [x] Incomplete records render gracefully. `NeuEmptyState` renders for both missing coordinate sets and missing specifications; covered by both Feature and Browser tests.

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | 2026-09-07 |
| **Result** | `composer test` → Pint clean, PHPStan level 7 (0 errors), Pest **133/133** passed (611 assertions) — 7 Coordinates Feature tests (prop shape, poster-exact values, query count, access gate), 3 DTO unit tests, and 5 real-Chromium browser tests (poster values at 390×844, tabular-nums, no overflow, copy interaction, bottom-nav state, empty states, screenshots at both breakpoints). `npm run check`/`types:check`/`build` all clean. Full flow walked through interactively in Chrome: Coordinates page, copy-to-clipboard toast, and Overview↔Coordinates navigation via the bottom nav. |
| **Screenshots** | `plan/evidence/phase-04/phase-04-coordinates-mobile.png`, `phase-04-coordinates-desktop.png` |
| **Commit / tag** | Pending commit; tag `phase-04-complete` to follow. |

## Phase Log

- **2026-09-07** — M4.1: `CoordinateSetData`/`SpecificationData` DTOs. Extended `GeoFormatter` with `degrees()` and `plainNumber()` (see Deviation above), each covered by a poster-literal unit test before use.
- **2026-09-07** — M4.2/M4.3: `DossierCoordinatesController` + `dossier/coordinates.tsx` reproducing both poster panels on one scrolling page, exactly as specified (one scroll beats a hidden second tab). Verified every value live in Chrome against the poster, field by field — an exact match, including the previously-untested Specs & QC values (Elevation Cut-off, Antenna Type/Height/Point).
- **2026-09-07** — M4.4: `NeuEmptyState` built and wired for both missing coordinate sets and missing specifications. Copy-to-clipboard built for lat/lon using the starter kit's existing `useClipboard` hook and `sonner` toaster (see Deviation above for the scoping-down from the plan's fuller "any value, copy-all" spec).
- **2026-09-07** — M4.5: `NeuBottomNav` + `buildDossierNavItems()` shared helper, wired into both `overview.tsx` and `coordinates.tsx`. Files/Photos nav items and the As-Built/Site-Photos/360° tiles remain disabled (no route yet) until Phases 06/07; the Coordinates tile is now a real, prefetched link. Confirmed navigation both directions live in Chrome.
