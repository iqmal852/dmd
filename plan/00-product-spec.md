# 00 — Product Specification

**Source of truth:** `../Picture1.png` (the only requirements artefact for this project).
Everything below is transcribed or directly inferred from that image. Anything marked
**[ASSUMPTION]** is an inference that should be confirmed with the client but does not
block the build.

---

## 1. What this is

A physical Ground Control Point (GCP) monument on the LPT2 highway carries a QR plate.
A surveyor in the field scans it with a phone. The QR encodes a URL. Opening that URL
shows a complete, **read-only** digital dossier for that exact monument: its coordinates
in three reference systems, GNSS observation specs and QC results, its position on a map,
site context photography including a 360° panorama, and the official as-built drawing.

**Physical → Digital.** The monument stops being an anonymous concrete block and becomes
a queryable record.

### 1.1 Primary user

A survey/engineering field crew member, standing at the roadside, on a phone, possibly on
a weak 4G connection, wearing gloves, in daylight glare. This drives three hard
constraints:

- **Mobile-first.** Design at 390 × 844. Desktop is the secondary layout.
- **Fast.** Aggressive image optimisation, lazy modules, small JS payload.
- **Legible.** High contrast values, large numeric type, generous tap targets (≥ 44px).

### 1.2 Scale

The poster states **25 GCP** for LPT2 coverage. This is a small dataset. It means:
pagination is a nicety not a necessity, full-text search is unnecessary, and we can afford
generous per-station media without a CDN in v1.

### 1.3 Out of scope

- Native iOS/Android apps.
- Public write access of any kind. The dossier is strictly read-only — this is a stated
  *benefit* on the poster ("Secure & Read-Only"), not an omission.
- Multi-tenant / multi-highway administration UI beyond a `highway` field on a station.
- Coordinate transformation maths. Values are entered/imported already computed.
- User accounts for field crews. There is one admin, and everyone else is anonymous.

---

## 2. Screen-by-screen transcription

### Panel 1 — The Context: "Scan. Connect. Access."

The hero dossier view. Header bar reads **MYSPATIAL | DIGITAL MONUMENT DOSSIER** with a
shield icon on the right (the security affordance).

**Title block**

| Element | Example value |
|---------|---------------|
| Title | `GCP STATION: LPT2-GCP-015` |
| Status badge | `ACTIVE` (green pill) |

**Meta row** — four icon + label + value cells, horizontally scrollable on mobile:

| Icon | Label | Value |
|------|-------|-------|
| Highway | Highway | `LPT2` |
| Target | KM | `318.200` |
| Section | Section | `E1` |
| Direction arrow | Direction | `Westbound` |

**Map preview** — satellite imagery, a large blue pin at the station, chainage callouts
`KM 318.0` and `KM 318.4` flanking it, and a `LPT2` route shield. Tapping opens the full
Location Map module (Phase 05).

**Quick View card** — the three coordinate systems at a glance:

```
WGS 84
  Lat   4.27412582
  Lon   103.43658211
  h     128.346 m

GDM 2000
  E     428,765.212
  N     472,318.678

MyGEOID (Ortho)
  H     112.436 m
```

**Module tiles** — four large touch targets in a 4-up row (2×2 on mobile), each a
distinct colour, each with an icon and label:

| Tile | Colour | Opens |
|------|--------|-------|
| Coordinates | Blue | Phase 04 |
| As-Built | Green | Phase 07 |
| Site Photos | Cyan | Phase 06 |
| 360° View | Purple | Phase 06 |

Caption underneath: *"Tap any module for full details"*.

---

### Panel 2 — Detailed Coordinates & Specifications: "Accurate. Verified. Reliable."

Three phone screens. All share a persistent bottom tab bar:
**Overview · Coordinates · Files · Photos** — this is the app's primary navigation and
must be implemented as a fixed bottom bar on mobile, and a horizontal tab strip or
sidebar on desktop.

#### 2a — "Coordinates" screen

Station selector row at top showing `LPT2-GCP-015` with a chevron (a station switcher).

| Group | Field | Example |
|-------|-------|---------|
| **WGS 84 (GPS)** | Latitude | `4.27412582 °` |
| | Longitude | `103.43658211 °` |
| | Ellipsoidal Height (h) | `128.346 m` |
| **GDM 2000 (TM)** | Easting (E) | `428,765.212 m` |
| | Northing (N) | `472,318.678 m` |
| **MyGEOID Height (Orthometric)** | *(single value, no label)* | `112.436 m` |

Note the precision: **8 decimal places** on lat/lon, **3** on all metric values, and
thousands separators on easting/northing. Storage and formatting must preserve this
exactly — see `02-data-model.md`.

#### 2b — "SPECS & QC" screen

| Group | Field | Example |
|-------|-------|---------|
| **GNSS OBSERVATION** | Method | `Static / RTK` |
| | Observation Time | `120 min` |
| | No. of Satellites | `18` |
| | PDOP (Max) | `1.6` |
| | Elevation Cut-off | `15 °` |
| | Antenna Type | `Geodetic L1/L2` |
| | Antenna Height | `1.532 m` |
| | Antenna Point | `Bottom of ARP` |
| **ACCURACY (RMS)** | Horizontal (XY) | `≤ 10 mm` |
| | Vertical (Z) | `≤ 15 mm` |
| | Status | `VERIFIED` (green pill) |

#### 2c — "Location Map" screen

Full-bleed satellite map with the station pin, zoom `+` / `−` controls, and a layer
toggle in the header. Below the map, a 2-column detail card:

| | |
|---|---|
| Highway `LPT2` | KM `318.200` |
| Direction `Westbound` | Section `E1` |
| Monument Type `Concrete Block` | Installed `15/03/2025` |

---

### Panel 3 — Digital As-Built Drawing: "Refer to official As-Built drawings instantly."

A tablet showing the engineering drawing **"GCP MONUMENT – TYPICAL DETAIL (As per LPT2 ToR)"**
with Top View and Section A-A, dimensioned in millimetres. Callouts visible in the drawing:

- `50mm x 3mm Galvanized Iron Pipe @ Centre`
- `Concrete Block 300mm x 300mm x 300mm`
- `Galvanized Iron Pipe Internal ⌀ 50mm`
- `5cm x 3cm Metal Plate`
- `LPT2 GCP XXX`
- Footer: *All dimensions in millimetres (mm)*

Alongside: a **DWG** file badge and a **Download As-Built Drawing** call to action.

**Implication:** the app must present a *viewable* rendition (PDF or image, since browsers
cannot render DWG) **and** offer the original file for download. See Phase 07.

---

### Panel 4 — Site Context Photos: "Assess environment. Reduce risk."

A large hero photograph labelled **EYE-LEVEL APPROACH** — the roadside view a crew sees
when walking up to the monument — overlaid with a **compass rose (N/S/E/W)** indicating
shot bearing.

Below it, a carousel with `‹` / `›` arrows and three thumbnails:

| Photo type | Caption |
|-----------|---------|
| Top-down | `TOP-DOWN (Sky Visibility)` |
| 360° panorama | `360° PANORAMA (Full Surrounding)` |
| Close-up | `CLOSE-UP (Monument)` |

Plus the eye-level shot itself, giving **four photo types**. "Sky Visibility" is a
functional label — the top-down shot exists so a crew can judge GNSS sky obstruction
before setting up. Photo type is therefore a meaningful enum, not a caption.

---

### Panel 5 — How It Works

The canonical user journey, to be reproduced verbatim on a public landing page:

| Step | Label | Sub-label |
|:----:|-------|-----------|
| 1 | SCAN | QR Code |
| 2 | CONNECT | Secure Link |
| 3 | ACCESS | Digital Dossier |
| 4 | VIEW | Data & Photos |
| 5 | DOWNLOAD | As-Built / Reports |

---

### Panel 6 — Key Benefits

| Benefit | Sub-label |
|---------|-----------|
| Faster | Field Verification |
| Accurate | & Consistent |
| Secure | & Read-Only |
| Always | Accessible |
| Supports | HAIM Vision |

---

### Footer strip

`SMART MONUMENT — PHYSICAL → DIGITAL` · `25 GCP LPT2 COVERAGE` ·
`WGS 84 / GDM 2000 / MyGEOID` · `≤10mm XY ACCURACY, ≤15mm Z` · `AS-BUILT DRAWINGS` ·
`SITE PHOTOS 360° CONTEXT` · `SECURE & PASSWORD PROTECTED` · `POWERED BY MYSPATIAL`

Branding present in the image: **MySpatial** (hexagonal green/blue mark) and **PLUS**
(the highway concessionaire, on the crew member's helmet). `SECURE & PASSWORD PROTECTED`
is the stated basis for the `.env`-driven access gate in Phase 03.

---

## 3. Information architecture

```
/                             Landing page (How It Works + Key Benefits + brand)
/d/{code}                     Dossier — Overview          ← what the QR encodes
/d/{code}/coordinates         Coordinates + Specs & QC
/d/{code}/map                 Location Map
/d/{code}/photos              Site Photos + 360°
/d/{code}/files               As-Built drawings & documents
/d/{code}/unlock              Password gate (only when DOSSIER_ACCESS_MODE=password)
/d/{code}/files/{id}/download Streamed download (audited)

/admin/login                  Single admin sign-in
/admin                        Station index
/admin/stations/create        Create
/admin/stations/{id}/edit     Edit — details, coordinates, specs, media, files
/admin/stations/{id}/qr       QR preview + printable plate sheet
/admin/qr/sheet               Bulk printable QR sheet for all stations
/admin/downloads              Download audit log
```

The four module tiles on Overview map 1:1 onto the four sub-routes. The bottom tab bar
(Overview · Coordinates · Files · Photos) maps onto the same routes, with Map reachable
from the Overview map preview and from the Coordinates screen.

---

## 4. Non-functional requirements

| Area | Target |
|------|--------|
| First Contentful Paint | < 1.8 s on simulated 4G, mid-tier Android |
| Largest Contentful Paint | < 2.5 s |
| Initial JS bundle (public dossier) | < 180 KB gzipped; Leaflet and Pannellum lazy-loaded per route |
| Lighthouse Accessibility | ≥ 95 |
| Colour contrast | WCAG AA (4.5:1) for all text — **critical**, Neumorphism tends to fail this |
| Tap targets | ≥ 44 × 44 px |
| Browser support | Last 2 versions of Chrome/Safari/Edge, iOS Safari 16+, Android Chrome 110+ |
| Uptime posture | Static-ish read path; heavy caching, works if the admin side is down |

**[ASSUMPTION]** No offline/PWA requirement in v1. Worth revisiting — field crews lose
signal — but it is not in the image, so it is deferred to a post-v1 note in Phase 09.
