# Phase 05 — Location Map

| | |
|---|---|
| **Status** | ⬜ Not started |
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
| [ ] | **M5.1** | Leaflet lazy-loaded on the map route only; tile config read from `config('dossier.map')` | | |
| [ ] | **M5.2** | `/d/{code}/map` — full-bleed satellite map, station pin, zoom controls, layer toggle | | |
| [ ] | **M5.3** | Detail card: Highway, KM, Direction, Section, Monument Type, Installed | | |
| [ ] | **M5.4** | Overview map preview (non-interactive, cheap) replacing the Phase 03 placeholder | | |
| [ ] | **M5.5** | `[OPTIONAL]` "Navigate here" hand-off (Apple Maps / Google Maps) + copy coordinates | | |

---

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

- [ ] Map renders satellite imagery at the correct location for `LPT2-GCP-015`.
- [ ] Leaflet loads only on the map route.
- [ ] Attribution is present and correct for both layers.
- [ ] *(Optional)* "Navigate here" opens the correct app on real iOS and real Android devices.
- [ ] Overview preview adds < 30 KB and no blocking JS.

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | |
| **Result** | |
| **Preview approach used** | |
| **Bundle report** | |
| **Screenshots** | `plan/evidence/phase-05/` |
| **Commit / tag** | |

## Phase Log

_Append one dated line per completed milestone._
