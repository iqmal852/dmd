# Phase 04 — Coordinates & Specifications Modules

| | |
|---|---|
| **Status** | ⬜ Not started |
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
| [ ] | **M4.1** | `CoordinateSetData` + `SpecificationData` DTOs, all values pre-formatted by `GeoFormatter` | | |
| [ ] | **M4.2** | `/d/{code}/coordinates` — WGS 84, GDM 2000 (TM), MyGEOID groups | | |
| [ ] | **M4.3** | Specs & QC section — GNSS Observation + Accuracy (RMS) + verified pill | | |
| [ ] | **M4.4** | Empty states for incomplete records; `[OPTIONAL]` copy-to-clipboard per value and per group | | |
| [ ] | **M4.5** | Bottom-tab navigation wired across Overview ↔ Coordinates ↔ Files ↔ Photos | | |

---

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

- [ ] Screens 2a and 2b of the poster are reproduced faithfully at 390px.
- [ ] Not one number is formatted in TypeScript — all formatting comes from `GeoFormatter`.
- [ ] *(Optional)* Copy-to-clipboard yields values a surveyor can paste straight into survey software.
- [ ] Incomplete records render gracefully.

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | |
| **Result** | |
| **Screenshots** | `plan/evidence/phase-04/` |
| **Commit / tag** | |

## Phase Log

_Append one dated line per completed milestone._
