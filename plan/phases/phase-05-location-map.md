# Phase 05 — Location Map

| | |
|---|---|
| **Status** | ✅ Complete |
| **Depends on** | Phase 03 |
| **Estimate** | 2 days |
| **Tag on completion** | `phase-05-complete` |

## Goal

Poster panel 2c and the map preview in panel 1: a satellite map with the station pin,
chainage context, a layer toggle, the detail card, and a "navigate here" hand-off to the
phone's map app — the single most useful button for a crew trying to find a concrete block
on a highway shoulder.

---

## Milestones

| | ID | Deliverable | Date | Evidence |
|---|---|---|---|---|
| [x] | **M5.1** | Leaflet lazy-loaded on the map route only; tile config read from `config('dossier.map')` | 2026-09-07 | `resources/js/pages/dossier/map.tsx`; build check confirms `leaflet-src-*.js` (148.82 kB) is a separate chunk, not in `app-*.js` |
| [x] | **M5.2** | `/d/{code}/map` — full-bleed satellite map, station pin, zoom controls, layer toggle | 2026-09-07 | Verified live in Chrome: real Esri imagery, custom SVG pin, zoom +/-, layer toggle with correct attribution swap |
| [x] | **M5.3** | Detail card: Highway, KM, Direction, Section, Monument Type, Installed | 2026-09-07 | `StationMapData`; matches poster field order exactly |
| [x] | **M5.4** | Overview map preview (non-interactive, cheap) replacing the Phase 03 placeholder | 2026-09-07 | Approach 1 (static tile composite) — `MapPreviewData` + `SlippyMapMath`; +1.16 kB to the overview chunk, zero JS, no Leaflet |
| [x] | **M5.5** | `[OPTIONAL]` "Navigate here" hand-off (Apple Maps / Google Maps) + copy coordinates | 2026-09-07 | Built in full, including the safety caution line; verified live in Chrome |

---

> **Deviation (2026-09-07):** Build check confirmed via `npm run build` output directly
> (`leaflet-src-*.js` at 148.82 kB as its own chunk, `app-*.js` unchanged in size from
> before Leaflet was added) rather than a CI grep script — sufficient for this phase; a
> CI-enforced version of the same check is still worth adding in Phase 09.
>
> **Deviation (2026-09-07):** The layer toggle swaps tile layers via `removeLayer`/
> `addLayer` on two persistent `L.TileLayer` instances, not `setUrl()` on a single
> instance as a first draft attempted. `setUrl()` does not update Leaflet's
> attribution control text, which would have silently violated the "attribution is
> rendered and never removed, per-layer" requirement the moment someone switched to
> the street layer. Verified live in Chrome that the attribution text genuinely
> changes from "Tiles © Esri" to "© OpenStreetMap contributors" on toggle.
>
> **Note on M5.4's tile math:** `SlippyMapMath::tileForPoint()` was verified against
> an independent Python implementation of the standard OSM slippy-map formula before
> being trusted — the first version of its test used a guessed expected tile
> coordinate, which was wrong, and was replaced with a value cross-checked
> independently. This is the kind of arithmetic where "the test passes" and "the test
> is correct" are different claims, so the ground truth was computed a second way
> before being written down as a fixed expectation.

## Milestone detail

### M5.1 — Leaflet, lazily

```ts
const L = (await import('leaflet')).default;
await import('leaflet/dist/leaflet.css');
```

Loaded inside a `useEffect` on mount, never in the shared bundle. Leaflet plus its CSS is
~150 KB; the Overview screen must not pay for it.

Tile layers are constructed from config, passed down as props:

```php
'map' => [
    'satelliteUrl' => config('dossier.map.satellite_url'),
    'satelliteAttribution' => config('dossier.map.satellite_attr'),
    'streetUrl' => config('dossier.map.street_url'),
    'streetAttribution' => config('dossier.map.street_attr'),
    'defaultZoom' => config('dossier.map.default_zoom'),
    'maxZoom' => config('dossier.map.max_zoom'),
],
```

**Attribution is rendered and never removed** — Esri and OSM both require it, and stripping
it is a licence violation. A browser test asserts the attribution control is present.

Fix the classic Leaflet+Vite marker-icon bug explicitly (bundled icon URLs break); use a
custom `L.divIcon` with an inline SVG pin styled with `--color-primary-bright`, which
sidesteps the issue entirely and matches the poster's blue pin.

### M5.2 — Map screen

Full-bleed map, `height: 100dvh - header - bottomnav` on mobile (`dvh`, not `vh` — mobile
browser chrome makes `vh` wrong). Controls:

- Neumorphic `+` / `−` zoom buttons, bottom-right, ≥ 44px, replacing Leaflet's defaults.
- Layer toggle (Satellite / Street) top-right, matching the poster's header icon.
- Recentre button that returns to the station pin after panning.
- `scrollWheelZoom` disabled until the map is clicked (so page scroll is not hijacked on
  desktop); on touch, `L.Map` `tap` handling left at defaults.

The pin carries a small permanent label with the station code, as the poster shows.

### M5.3 — Detail card

Below the map on mobile, overlaid bottom-left on desktop. Two-column grid, exactly the
poster's fields and order:

| | |
|---|---|
| Highway `LPT2` | KM `318.200` |
| Direction `Westbound` | Section `E1` |
| Monument Type `Concrete Block` | Installed `15/03/2025` |

### M5.4 — Overview preview

The Overview screen needs a map thumbnail without paying Leaflet's cost. Approach, in
order of preference:

1. **Static tile composite** — build an `<img>` from the Esri tile URL at the station's
   zoom/tile coordinates, with the SVG pin absolutely positioned over it. Zero JS, one or
   four image requests, fully cacheable. **This is the default choice.**
2. If tile-maths proves fiddly, fall back to a Leaflet instance with all interaction
   handlers disabled, still lazy-loaded and only mounted when scrolled into view.

Include the `KM 318.0` / `KM 318.4` chainage callouts and the `LPT2` route shield the
poster shows, computed as `km ± 0.2` and rendered as absolutely-positioned labels.

Record which approach was taken in the Phase Log.

### M5.5 — Navigate here `[OPTIONAL]`

Not in the poster, but the single most useful button for a crew trying to find a concrete
block on a highway shoulder. A prominent `NeuButton` opening the device's native map app:

```ts
const url = isIOS
  ? `https://maps.apple.com/?ll=${lat},${lon}&q=${encodeURIComponent(code)}`
  : `https://www.google.com/maps/search/?api=1&query=${lat},${lon}`;
```

Plus a "Copy coordinates" action yielding `4.27412582, 103.43658211` — the format map apps
and survey controllers accept.

> **Safety note for the UI copy:** these monuments sit on a live highway shoulder. The
> button label should read "Navigate to station", and the card carries a short caution
> line about roadside safety. It costs one line and it is the right thing to put in front
> of someone about to walk onto a motorway verge.

---

## Test Gate

```bash
php artisan test --filter='Map|Dossier'
php artisan test --filter=Browser
npm run build   # inspect chunk output
```

Assertions that must exist:

1. `GET /d/{public_id}/map` → 200, component `dossier/map`.
2. Map props carry lat/lon as **numbers** (Leaflet needs numeric) while the detail card
   values remain pre-formatted strings.
3. Tile URLs come from config — overriding `MAP_SATELLITE_URL` changes the prop.
4. The route is behind the access gate.
5. A station with no coordinate set renders an empty state instead of a broken map.
6. **Build check:** Leaflet is in its own chunk and is *not* in the entry bundle. Assert
   this by grepping the Vite manifest / chunk list in CI.
7. **Browser:** the map container renders, exactly one pin exists, attribution text for
   Esri/OSM is present, zoom buttons are ≥ 44px, the layer toggle switches the tile URL.
8. **Browser, 390×844:** map + detail card fit with no horizontal overflow; the map is not
   hidden behind the bottom nav.

---

## Definition of Done

- [x] Map renders satellite imagery at the correct location for `LPT2-GCP-015`. Verified live in Chrome — real Esri imagery, and the street layer shows the actual road name (Jalan Chukai-Kerteh) at that real Malaysian location.
- [x] Leaflet loads only on the map route. Confirmed via build output and a browser test asserting `window.L` is undefined on the Overview page.
- [x] Attribution is present and correct for both layers. Verified live: "Tiles © Esri" on satellite, "© OpenStreetMap contributors" on street, correctly swapping on toggle.
- [x] *(Optional)* "Navigate here" opens the correct app on real iOS and real Android devices. **Partially verified** — the URL construction (`isIos()` sniff, Apple Maps vs. Google Maps URL schemes) was built and the button/link render correctly in Chrome, but opening an actual native map app cannot be exercised from a desktop browser or verified without a physical device (same constraint noted in Phase 03's sign-off).
- [x] Overview preview adds < 30 KB and no blocking JS. The overview chunk grew from 7.79 kB to 8.95 kB (+1.16 kB); it is a plain `<img>` tag, no JS execution required to display it.

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | 2026-09-07 |
| **Result** | `composer test` → Pint clean, PHPStan level 7 (0 errors), Pest **150/150** passed (686 assertions) — Map Feature tests (numeric lat/lon vs. formatted detail fields, config-driven tile URLs, access gate, empty state), `StationMapData`/`MapPreviewData`/`SlippyMapMath` unit tests (the tile-math ones cross-checked against an independent Python computation), and 6 real-Chromium browser tests (exactly one pin, attribution present and correct per layer, tap targets, no overflow at 390px, map not hidden behind the bottom nav, Leaflet absent from the Overview page). `npm run check`/`types:check`/`build` all clean; Leaflet confirmed in its own 148.82 kB chunk, not in the entry bundle. Full flow — satellite/street toggle, zoom, recentre, Overview-preview tap-through to the full map, Navigate/Copy buttons — walked through interactively in Chrome. |
| **Preview approach used** | Approach 1 (static tile composite) — a single Esri tile at zoom 15 located via `SlippyMapMath`, with the pin and KM callouts CSS-positioned over it. No fallback to Leaflet was needed. |
| **Bundle report** | `leaflet-src-*.js`: 148.82 kB (43.39 kB gzipped), separate chunk. `leaflet-*.css`: 10.57 kB, separate chunk. Entry `app-*.js`: 167.85 kB, unchanged from before this phase. Overview page chunk: 7.79 kB → 8.95 kB (+1.16 kB) for the static preview. |
| **Screenshots** | `plan/evidence/phase-05/phase-05-map-mobile.png`, `phase-05-map-desktop.png` |
| **Commit / tag** | Pending commit; tag `phase-05-complete` to follow. |

## Phase Log

- **2026-09-07** — M5.1: Installed `leaflet` + `@types/leaflet`. Lazy-loaded via dynamic `import()` inside a `useEffect`, confirmed in the build output to land in its own chunk. Sidestepped the classic Leaflet+bundler marker-icon path bug entirely with a custom inline-SVG `L.divIcon`, styled with the primary-bright brand colour, matching the poster's blue pin.
- **2026-09-07** — M5.2: `DossierMapController` + `dossier/map.tsx`. Custom Neumorphic zoom/layer/recentre controls replacing Leaflet's defaults. Found and fixed a real bug before it shipped: `setUrl()`-based layer switching doesn't update the attribution control (see Deviation above) — switched to two persistent layers toggled via add/remove, verified live that attribution text genuinely changes.
- **2026-09-07** — M5.3: `StationMapData` DTO, detail card in the exact poster field order. Covered by a Feature test asserting lat/lon arrive as PHP floats (not formatted strings) while every other field is pre-formatted — the one screen in this app where a DTO deliberately breaks the "always a display string" rule, because Leaflet needs real numbers.
- **2026-09-07** — M5.4: `SlippyMapMath` (standard Web Mercator tile math) + `MapPreviewData`, replacing what M3.6 had left as a gap (Phase 03's overview.tsx shipped with no map preview placeholder at all). Cross-checked the tile arithmetic against an independent Python implementation before trusting a test's expected values — this caught a wrong guess on the first attempt (see Deviation above). Verified live: real satellite imagery, an accurately-positioned pin, and tapping the preview navigates to the full interactive map at the same location.
- **2026-09-07** — M5.5: "Navigate to station" (Apple Maps on iOS, Google Maps otherwise) and "Copy coordinates", plus the safety caution line the plan explicitly asked for ("this monument may sit on a live highway shoulder..."). Built in full rather than skipped, reusing the existing `useClipboard` hook and `sonner` toaster from Phase 04. Verified live in Chrome; the native-app hand-off itself needs a real device to fully confirm (see Definition of Done).
