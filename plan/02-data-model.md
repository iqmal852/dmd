# 02 — Data Model (PostgreSQL 17)

Every field traces to something visible in `Picture1.png`. Nothing speculative.

---

## 1. Entity overview

```
users (exactly 1 row)

stations ─┬─1:1─ coordinate_sets
          ├─1:1─ specifications
          ├─1:N─ media            (spatie/laravel-medialibrary: photos, panoramas, documents)
          └─1:N─ download_logs
```

`stations` is the aggregate root. `coordinate_sets` and `specifications` are split out
rather than flattened into `stations` because they are (a) large, (b) independently
edited by different people at different times, and (c) each maps to its own screen in the
poster. Splitting also keeps the Overview query narrow.

---

## 2. `stations`

The physical monument.

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigserial` PK | |
| `public_id` | `char(26)` unique | ULID. **This is what the QR encodes.** Non-enumerable, so nobody can walk `/d/1`, `/d/2`. |
| `code` | `varchar(50)` unique | `LPT2-GCP-015`. Human identifier shown as the page title. Case-insensitive unique via a functional index. |
| `highway` | `varchar(50)` | `LPT2` |
| `section` | `varchar(20)` nullable | `E1` |
| `km` | `numeric(10,3)` | `318.200` — displayed as `KM 318.200` |
| `direction` | `varchar(20)` | enum-backed: `northbound`, `southbound`, `eastbound`, `westbound`, `both` |
| `monument_type` | `varchar(50)` | `Concrete Block` |
| `installed_at` | `date` nullable | `2025-03-15`, displayed `15/03/2025` |
| `status` | `varchar(20)` | enum-backed: `active`, `inactive`, `damaged`, `destroyed`, `pending` — poster shows `ACTIVE` |
| `description` | `text` nullable | Free notes, admin-only |
| `access_password` | `varchar(255)` nullable | Hashed. Per-station override; when null the global `.env` password applies. See §7. |
| `is_published` | `boolean` default `false` | Unpublished stations 404 on the public route |
| `created_at` `updated_at` | `timestamptz` | |
| `deleted_at` | `timestamptz` nullable | Soft deletes — a monument record should never be hard-deleted |

**Indexes**
```sql
CREATE UNIQUE INDEX stations_public_id_unique ON stations (public_id);
CREATE UNIQUE INDEX stations_code_lower_unique ON stations (lower(code));
CREATE INDEX stations_highway_km_idx ON stations (highway, km);
CREATE INDEX stations_published_idx ON stations (is_published) WHERE deleted_at IS NULL;
```

**Route binding.** `Station::getRouteKeyName()` returns `public_id`. The QR URL is
`/d/{public_id}`. `code` is *displayed* but never routed on — so renaming a station never
invalidates a QR plate already bolted to a concrete block in the field. This is a
deliberate and important property: **QR codes are physically permanent, so the identifier
they encode must be immutable.**

---

## 3. `coordinate_sets`

Panel 2a of the poster. One row per station.

| Column | Type | Poster value |
|--------|------|--------------|
| `id` | `bigserial` PK | |
| `station_id` | `bigint` unique FK → stations, cascade | |
| **WGS 84** | | |
| `latitude` | `numeric(11,8)` | `4.27412582` |
| `longitude` | `numeric(12,8)` | `103.43658211` |
| `ellipsoidal_height` | `numeric(10,3)` | `128.346` m |
| **GDM 2000 (TM)** | | |
| `easting` | `numeric(12,3)` | `428765.212` m |
| `northing` | `numeric(12,3)` | `472318.678` m |
| `zone` | `varchar(20)` nullable | TM projection zone label |
| **MyGEOID** | | |
| `orthometric_height` | `numeric(10,3)` | `112.436` m |
| `geoid_model` | `varchar(50)` default `MyGEOID` | |
| `epoch` | `varchar(20)` nullable | e.g. `2020.0` |
| `computed_at` | `date` nullable | |
| timestamps | | |

**Precision is a requirement, not a detail.** The poster shows 8 decimal places on lat/lon
(≈1 mm at the equator) and 3 on all metric values. Therefore:

- **Never use `float`/`double`.** `numeric` with explicit scale, always.
- Cast in Eloquent to `decimal:8` / `decimal:3` so PHP does not silently reformat.
- Format for display in **one place** — `App\Services\GeoFormatter` — which produces
  `4.27412582 °`, `128.346 m`, and `428,765.212 m` (thousands separators on
  easting/northing, none on lat/lon, exactly as the poster shows).
- A unit test asserts every format helper against the poster's literal strings.

---

## 4. `specifications`

Panel 2b — GNSS Observation and Accuracy (RMS). One row per station.

| Column | Type | Poster value |
|--------|------|--------------|
| `id` | `bigserial` PK | |
| `station_id` | `bigint` unique FK → stations, cascade | |
| **GNSS observation** | | |
| `observation_method` | `varchar(50)` | `Static / RTK` |
| `observation_minutes` | `smallint` nullable | `120` → rendered `120 min` |
| `satellite_count` | `smallint` nullable | `18` |
| `pdop_max` | `numeric(4,2)` nullable | `1.60` → rendered `1.6` |
| `elevation_cutoff_deg` | `smallint` nullable | `15` → rendered `15 °` |
| `antenna_type` | `varchar(100)` nullable | `Geodetic L1/L2` |
| `antenna_height` | `numeric(6,3)` nullable | `1.532` m |
| `antenna_reference_point` | `varchar(50)` nullable | `Bottom of ARP` |
| **Accuracy (RMS)** | | |
| `horizontal_rms_mm` | `numeric(6,2)` nullable | `10.00` → rendered `≤ 10 mm` |
| `vertical_rms_mm` | `numeric(6,2)` nullable | `15.00` → rendered `≤ 15 mm` |
| `qc_status` | `varchar(20)` | enum: `verified`, `pending`, `rejected` — poster shows `VERIFIED` |
| `verified_at` | `date` nullable | |
| `verified_by` | `varchar(100)` nullable | |
| `remarks` | `text` nullable | |
| timestamps | | |

The `≤` prefix is part of how accuracy is *presented* (a tolerance, not a measurement),
so it belongs in the formatter, not the column. Store the number.

---

## 5. Media (`spatie/laravel-medialibrary`)

Three collections on `Station`:

### 5.1 `photos`

Custom properties on each media row:

| Property | Values |
|----------|--------|
| `photo_type` | `eye_level` · `top_down` · `close_up` · `context` |
| `bearing` | `0–359` degrees, nullable — drives the compass rose overlay in panel 4 |
| `captured_at` | ISO date, nullable |
| `caption` | Free text — defaults to the poster's captions per type |

Default captions, taken from the poster:

| type | caption |
|------|---------|
| `eye_level` | Eye-Level Approach |
| `top_down` | Top-Down (Sky Visibility) |
| `close_up` | Close-Up (Monument) |
| `context` | Site Context |

Conversions: `thumb` 200×200 (queued), `preview` 800px wide WebP, plus medialibrary
responsive images for `srcset`. Field crews on 4G must not download 4 MB JPEGs.

### 5.2 `panoramas`

Equirectangular 360° images for Pannellum (2:1 aspect ratio). Custom properties:
`initial_yaw`, `initial_pitch`, `hfov`, `caption`. Single-file collection in v1 —
one panorama per station, matching `360° PANORAMA (Full Surrounding)`.

**No conversions.** Downscaling an equirectangular image breaks the projection; instead,
validate on upload that width = 2 × height and warn if the file exceeds 8 MB.

### 5.3 `documents`

As-built drawings and reports (panel 3). Custom properties:

| Property | Values |
|----------|--------|
| `document_type` | `as_built` · `report` · `certificate` · `other` |
| `title` | `GCP Monument – Typical Detail (As per LPT2 ToR)` |
| `revision` | e.g. `Rev A` |
| `is_primary` | boolean — the one the big "Download As-Built Drawing" button targets |

**The DWG problem.** Browsers cannot render `.dwg`. Every as-built document therefore has
two artefacts: the **original** (`.dwg`, downloadable) and a **preview** (`.pdf` or
`.png`, viewable inline). Modelled as a media row for the original plus a `preview_media_id`
custom property pointing at the companion row. The admin UI enforces "upload a preview
alongside a DWG" at validation time (Phase 07/08).

---

## 6. `download_logs`

Backs the "Secure & Read-Only" claim with an actual audit trail, and answers "is anyone
using this?".

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigserial` PK | |
| `station_id` | `bigint` FK → stations, cascade | |
| `media_id` | `bigint` FK → media, set null on delete | |
| `ip_hash` | `char(64)` nullable | **SHA-256 of IP + app key.** Never store raw IPs — no consent flow exists for that. |
| `user_agent` | `varchar(255)` nullable | Truncated |
| `downloaded_at` | `timestamptz` | |

Written by a queued listener so the download response is never delayed by a DB write.
Retention: 12 months, pruned by `model:prune` on a scheduled task.

---

## 7. Access control model

Two layers, resolved in `EnsureDossierUnlocked`:

```
if (config('dossier.access_mode') === 'public'
    && $station->access_password === null) → allow

password required when:
    config('dossier.access_mode') === 'password'   (global switch, .env)
 OR $station->access_password !== null             (per-station override)

the password to check is:
    $station->access_password ?? config('dossier.access_password')
```

This satisfies the requirement literally — flipping one `.env` key gates the entire
deployment — while still allowing a single sensitive station to be locked in an otherwise
open deployment. Unlocking stores `dossier_unlocked.{public_id} => expires_at` in the
session, valid for `DOSSIER_UNLOCK_TTL` minutes (default 720 = 12 h, one working day).

Attempts are rate-limited: 5 per minute per IP per station, then a 429 with a
`Retry-After`. Passwords are stored with `Hash::make()` and compared with `Hash::check()`.

---

## 8. Enums

All in `app/Enums`, all backed by string, all implementing a `label(): string` method for
display and `HasColor` where the UI shows a coloured pill.

| Enum | Cases |
|------|-------|
| `StationStatus` | `Active` `Inactive` `Damaged` `Destroyed` `Pending` |
| `Direction` | `Northbound` `Southbound` `Eastbound` `Westbound` `Both` |
| `QcStatus` | `Verified` `Pending` `Rejected` |
| `PhotoType` | `EyeLevel` `TopDown` `CloseUp` `Context` |
| `DocumentType` | `AsBuilt` `Report` `Certificate` `Other` |
| `AccessMode` | `Public` `Password` |

Enums are the display vocabulary too: `StationStatus::Active->label()` returns `Active`
and `->color()` returns the `success` token, so the green `ACTIVE` pill in the poster has
exactly one definition.

---

## 9. Seed data

`DemoStationSeeder` creates **25 LPT2 stations** (`LPT2-GCP-001` … `LPT2-GCP-025`), the
coverage number stated on the poster. `LPT2-GCP-015` is seeded with the **exact values
from `Picture1.png`** and is the reference fixture used by browser tests and demos — so
any regression against the poster is caught by comparing to a known-good record.

The other 24 are generated from factories with plausible chainages along LPT2 between
KM 300 and KM 340, varied statuses, and a mix of complete and incomplete records (so
empty-state rendering is exercised, not just the happy path).

`AdminUserSeeder` creates the single admin from `ADMIN_EMAIL` / `ADMIN_PASSWORD` and is
idempotent (`updateOrCreate` on email) so it is safe to re-run on deploy.
