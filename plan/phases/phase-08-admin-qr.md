# Phase 08 — Admin Console & QR Generation

| | |
|---|---|
| **Status** | ⬜ Not started |
| **Depends on** | Phases 02, 04, 06, 07 |
| **Estimate** | 7 days required work (+1 if M8.8 is built) — the largest phase |
| **Tag on completion** | `phase-08-complete` |

## Goal

Everything the single admin needs to populate and maintain the 25 stations, plus QR code
generation and a printable plate sheet — the artefact that gets physically bolted to a
concrete monument.

Built in Inertia + React with the same Neumorphism system as the public side.

---

## Milestones

| | ID | Deliverable | Date | Evidence |
|---|---|---|---|---|
| [ ] | **M8.1** | Admin auth: login, logout, profile/password change, `batu:admin-password` command, rate limiting | | |
| [ ] | **M8.2** | Station index: search, filter by highway/status, sort by chainage, published toggle | | |
| [ ] | **M8.3** | Station create/edit — details tab (code, highway, section, KM, direction, type, installed, status, per-station password) | | |
| [ ] | **M8.4** | QR generation service + `/admin/stations/{id}/qr` preview and download (PNG + SVG) | | |
| [ ] | **M8.5** | Printable plate sheet — single station and bulk, print-CSS at real plate dimensions | | |
| [ ] | **M8.6** | Coordinates + Specifications forms with survey-grade validation | | |
| [ ] | **M8.7** | Media manager: photo upload with type/bearing/caption, panorama upload with 2:1 validation, document upload with DWG-preview pairing | | |
| [ ] | **M8.8** | `[OPTIONAL]` CSV import/export for stations + coordinates; download audit log viewer | | |

---

## Milestone detail

### M8.1 — Auth

Login only. Rate-limited to 5 attempts/minute per email+IP. `session()->regenerate()` on
success. No registration, no password reset (ADR-006) — instead:

```bash
php artisan batu:admin-password           # prompts, hidden input, confirms
php artisan batu:admin-password --email=admin@myspatial.com.my
```

The admin profile page allows changing name, email, and password with current-password
confirmation.

`EnsureIsAdmin` middleware on the whole `/admin` group. `StationPolicy` gates every write
even though there is one user — because policies are where a second role would land, and
retrofitting them later is how authorisation bugs happen.

### M8.2 — Station index

A `NeuCard` table (cards on mobile, table at ≥ 768px) with: code, highway, KM, section,
status pill, published toggle, media counts, and row actions (Edit · QR · View public).

Server-side search on `code`, filter by highway and status, sort by `km`. All state in the
URL query string via Inertia so it survives a refresh and is shareable.

With 25 rows, pagination is set to 50 — effectively one page — but implemented properly so
it does not break at 250 stations.

### M8.3 — Station details form

Fields per [`../02-data-model.md`](../02-data-model.md) §2. Validation in
`StoreStationRequest` / `UpdateStationRequest`:

| Field | Rule |
|-------|------|
| `code` | required, max 50, **case-insensitive unique** ignoring self |
| `highway` | required, max 50 |
| `km` | required, numeric, 0–2000, 3 decimals |
| `direction` | required, `Rule::enum(Direction::class)` |
| `status` | required, `Rule::enum(StationStatus::class)` |
| `installed_at` | nullable, date, not in the future |
| `access_password` | nullable, min 8 — leaving blank keeps the existing one, a dedicated "clear password" control removes it |

The `access_password` field needs care: a blank input must mean "unchanged", never
"remove", or an admin editing a KM value would silently un-gate a locked station. The
form shows current state ("This station has its own password") and offers an explicit
Clear action. A test covers exactly this.

`public_id` is displayed read-only with a warning: **"Changing this would invalidate every
QR plate already installed in the field."** It is not editable in the UI at all.

### M8.4 — QR generation

```bash
composer require endroid/qr-code
```

`App\Services\QrUrlBuilder` (the single URL source, per
[`../04-env-configuration.md`](../04-env-configuration.md) §5) and
`App\Actions\Qr\GenerateStationQr`:

```php
public function __invoke(Station $station, string $format = 'png'): string
{
    $builder = new Builder(
        writer: $format === 'svg' ? new SvgWriter() : new PngWriter(),
        data: $this->urls->forStation($station),
        errorCorrectionLevel: ErrorCorrectionLevel::High,   // survives a scratched plate
        size: config('dossier.qr.size'),
        margin: config('dossier.qr.margin'),
        logoPath: config('dossier.qr.logo_path'),
        logoResizeToWidth: (int) (config('dossier.qr.size') * 0.18),
    );

    return $builder->build()->getString();
}
```

**Error correction level H (30%)** is not a default worth changing: these codes live
outdoors on a highway shoulder, exposed to grime, sun, and scratches, and a code that fails
to scan means a wasted site visit.

The preview page shows the QR, the exact encoded URL as text, a "Test this URL" link, and
PNG/SVG download buttons. SVG matters — engraving and sign-printing vendors want vectors.

QR images are generated on demand and cached by `public_id` + a config fingerprint, so
changing `DOSSIER_BASE_URL` automatically invalidates them.

### M8.5 — Printable plate sheet

`/admin/stations/{id}/qr/print` and `/admin/qr/sheet` (all stations, or a filtered subset).

Layout per plate, matching the poster's physical plate:

```
┌─────────────────────┐
│      LPT2           │
│    GCP 015          │
│  ┌───────────────┐  │
│  │   [QR CODE]   │  │
│  └───────────────┘  │
│     SCAN QR CODE    │
│   ⬡ MYSPATIAL       │
└─────────────────────┘
```

Print CSS: `@page { size: A4; margin: 10mm }`, plates laid out in a grid at real-world
dimensions (default 50 × 50 mm, configurable), `print-color-adjust: exact`, crop marks,
and **no neumorphic shadows in print** — soft shadows print as grey mud. A dedicated
`@media print` block resets to flat black-on-white.

The QR must be pure black on pure white in print, at ≥ 300 DPI effective resolution.

### M8.6 — Coordinates & specs forms

Separate tabs on the edit screen. Validation:

| Field | Rule |
|-------|------|
| `latitude` | required, numeric, between **-90 and 90**; warn if outside Malaysia (0.5–7.5) |
| `longitude` | required, numeric, between **-180 and 180**; warn if outside Malaysia (99–120) |
| heights | numeric, -100 to 3000 m |
| `easting` / `northing` | numeric, positive, ≤ 10 000 000 |
| `pdop_max` | numeric, 0–99, 2 decimals |
| `elevation_cutoff_deg` | integer, 0–90 |
| `satellite_count` | integer, 0–60 |
| `horizontal_rms_mm` / `vertical_rms_mm` | numeric, 0–10000 |

The Malaysia range check is a **warning, not an error** — the app should not refuse valid
data because someone deployed it on a different project — but a swapped lat/lon is the
single most common survey data-entry error and catching it at entry saves a site visit.

Inputs use `inputMode="decimal"`, preserve full typed precision (no rounding on blur), and
show a live preview of how the value will render on the public screen.

### M8.7 — Media manager

- **Photos**: multi-file drag-and-drop, per-file `photo_type` select, optional `bearing`
  (0–359) and `caption`, drag-to-reorder, delete with confirmation. Client-side downscale
  of anything over 4000px before upload — field phones produce enormous JPEGs and an
  admin on site tethering over 4G should not upload 12 MB.
- **Panorama**: single file, **2:1 aspect ratio validated client-side and server-side**,
  with `initial_yaw` / `initial_pitch` set by dragging a live Pannellum preview.
- **Documents**: file + `document_type` + `title` + `revision` + `is_primary`. Setting
  `is_primary` clears it on the station's other documents (in a transaction). DWG uploads
  require a preview file — enforced by a custom validation rule (Phase 07, M7.1).

Upload limits documented and enforced in both PHP (`upload_max_filesize`,
`post_max_size`) and the web server config; the UI states the limit before the user picks
a file rather than failing after a long upload.

### M8.8 — Import/export and audit `[OPTIONAL]`

If skipped, load the 25 real LPT2 stations with a one-off `database/seeders/Lpt2StationSeeder.php`
built from the client's spreadsheet — a seeder is a perfectly good import for a dataset
that changes once. The download audit remains queryable through Boost's `tinker` MCP tool.

- **CSV export**: all stations with coordinates and specs, one row each, full precision,
  for handover to the client's own systems.
- **CSV import**: dry-run first, showing a per-row diff (create / update / error) before
  anything is written; the actual import runs in a transaction. This is how the 25 real
  stations get in without 25 manual forms.
- **Download audit viewer**: `/admin/downloads` — station, document, timestamp, truncated
  UA. Hashed IPs are shown as a short fingerprint only.

---

## Test Gate

```bash
php artisan test --filter='Admin|Auth|Qr|Import|Export|Media'
php artisan test --filter=Browser
./vendor/bin/pint --test && ./vendor/bin/phpstan analyse
npm run types && npm run lint && npm run build
```

Assertions that must exist:

1. Every `/admin/*` route redirects to login when unauthenticated. **Parametrise this over
   the full route list** so a newly added admin route cannot be left unguarded.
2. 6 failed logins in a minute → 429.
3. Creating a station assigns a ULID `public_id`; `public_id` cannot be changed by
   submitting it in the update payload (mass-assignment test).
4. Case-insensitive uniqueness on `code` is enforced by the form request, not only the DB.
5. **QR payload uses `DOSSIER_BASE_URL`, not the request host** — set the config to
   `https://qr.example.test`, make the request from `http://localhost`, assert the encoded
   string starts with `https://qr.example.test/d/`.
6. Changing `DOSSIER_ROUTE_PREFIX` changes the QR payload **and** the generated URL still
   resolves in the app.
7. A trailing slash in `DOSSIER_BASE_URL` never yields `//`.
8. Decoding the generated PNG returns exactly the expected URL. Use
   `khanamiryan/qrcode-detector-decoder` (dev dependency, pure PHP, needs GD) in the test.
   *This is the test that proves the physical artefact works.*
9. Submitting a blank `access_password` on edit leaves the existing hash unchanged; the
   explicit Clear action nulls it.
10. Setting `is_primary` on a document clears it on the station's others.
11. Uploading a 3:1 panorama is rejected; a 2:1 is accepted.
12. Uploading a `.dwg` without a preview is rejected.
13. *(If M8.8 built)* CSV import dry-run writes nothing; confirmed import writes all rows
    in one transaction and rolls back entirely on a mid-file error.
14. **Browser:** log in, create a station, add coordinates, upload a photo, generate a QR,
    open the public dossier for it — one end-to-end flow.
15. **Browser, print:** the plate sheet renders with `@media print` styles, no shadows.

---

## Definition of Done

- [ ] The admin can take a station from nothing to a complete public dossier without touching the database.
- [ ] A generated QR **scans successfully from a printed sheet with a real phone** — print it, scan it, confirm it opens the right dossier. This is a required manual check.
- [ ] All 25 LPT2 stations are loaded — via CSV import (M8.8) or the one-off seeder.
- [ ] Admin UI uses the same Neumorphism system; no unstyled default form controls.
- [ ] No admin route is reachable without auth.

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | |
| **Result** | |
| **Printed QR scan verified on** | |
| **Screenshots** | `plan/evidence/phase-08/` |
| **Commit / tag** | |

## Phase Log

_Append one dated line per completed milestone._
