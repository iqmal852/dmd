# Phase 01 — Data Model & Domain

| | |
|---|---|
| **Status** | ✅ Complete |
| **Depends on** | Phase 00 |
| **Estimate** | 2 days |
| **Tag on completion** | `phase-01-complete` |

## Goal

The full PostgreSQL schema from [`../02-data-model.md`](../02-data-model.md), the Eloquent
models with correct casts and precision, enums, factories, and a seeder producing the 25
LPT2 stations — including `LPT2-GCP-015` with the exact values from `Picture1.png`.

No UI in this phase. Everything is verified through tests and Boost's `tinker` and
`database-schema` MCP tools.

---

## Milestones

| | ID | Deliverable | Date | Evidence |
|---|---|---|---|---|
| [x] | **M1.1** | Enums: `StationStatus`, `Direction`, `QcStatus`, `PhotoType`, `DocumentType`, `AccessMode` — `label()` on all six, `color()` on the two rendered as pills (see deviation) | 2026-09-07 | `tests/Unit/Enums/EnumContractsTest.php` (5 tests, 44 assertions) |
| [x] | **M1.2** | `stations` migration + model: ULID `public_id`, route binding, soft deletes, scopes | 2026-09-07 | `tests/Feature/Models/StationTest.php` (10 tests) |
| [x] | **M1.3** | `coordinate_sets` migration + model with exact `numeric` precision and decimal casts | 2026-09-07 | `tests/Feature/Models/CoordinateSetTest.php`, incl. `information_schema` precision check |
| [x] | **M1.4** | `specifications` migration + model | 2026-09-07 | `tests/Feature/Models/SpecificationTest.php` |
| [x] | **M1.5** | `download_logs` migration + model with prunable trait | 2026-09-07 | `tests/Feature/Models/DownloadLogTest.php` (5 tests) |
| [x] | **M1.6** | Factories + `DemoStationSeeder` (25 stations, GCP-015 = poster values) + `GeoFormatter` service | 2026-09-07 | `tests/Feature/Seeders/DemoStationSeederTest.php`, `tests/Unit/Services/GeoFormatterTest.php` |

---

> **Deviation (2026-09-07):** `02-data-model.md` §8 says color() applies "where the UI
> shows a coloured pill" (i.e. `StationStatus` and `QcStatus` only), while this phase's
> milestone table originally said "each with label() and color()" for all six enums.
> The two docs disagreed; `02-data-model.md` is more precise (only the poster's two
> pills — `ACTIVE`, `VERIFIED` — carry colour), so that's what was implemented.
> `Direction`, `PhotoType`, `DocumentType`, `AccessMode` implement `HasLabel` only.
> Introduced `App\Contracts\HasLabel`/`HasColor` interfaces (not originally specified)
> so a future status-pill component can accept any `HasLabel&HasColor` enum rather than
> being hard-coded to one.
>
> **Deviation (2026-09-07):** `download_logs.media_id` has no foreign-key constraint
> yet — `spatie/laravel-medialibrary`'s `media` table doesn't exist until Phase 06/07.
> It's a plain indexed `unsignedBigInteger` for now; a follow-up migration in Phase 06
> adds the FK (`set null on delete`) once the package is installed.
>
> **Deviation (2026-09-07):** `DatabaseSeeder` originally used Laravel's
> `WithoutModelEvents` trait (shipped by the starter kit for speed). Removed it — it
> silently suppressed the `Station::booted()` `creating` event that assigns the ULID
> `public_id`, which surfaced immediately as a `NOT NULL` violation on the very first
> seed run. Model events are cheap at 25 rows; the trait is the wrong optimisation here.

## Milestone detail

### M1.1 — Enums

`app/Enums/*.php`, all string-backed, `declare(strict_types=1)`.

```php
enum StationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Damaged = 'damaged';
    case Destroyed = 'destroyed';
    case Pending = 'pending';

    public function label(): string { /* 'Active', … */ }
    public function color(): string { /* 'accent' | 'ink-muted' | 'warning' | 'danger' */ }
}
```

`color()` returns a **design token name**, not a hex value — so the green `ACTIVE` pill in
the poster has exactly one definition and the theme owns the colour.

Acceptance: a unit test iterates every case of every enum and asserts `label()` and
`color()` are non-empty and that `color()` names a token declared in `app.css`.

### M1.2 — `stations`

Migration per [`../02-data-model.md`](../02-data-model.md) §2, including the raw-SQL
functional unique index on `lower(code)` and the partial index on `is_published`.

Model:

```php
final class Station extends Model
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    public function getRouteKeyName(): string { return 'public_id'; }

    protected function casts(): array
    {
        return [
            'km'           => 'decimal:3',
            'installed_at' => 'date',
            'status'       => StationStatus::class,
            'direction'    => Direction::class,
            'is_published' => 'boolean',
            'access_password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Station $s) => $s->public_id ??= (string) Str::ulid());
    }

    public function scopePublished(Builder $q): void { $q->where('is_published', true); }
    public function scopeForHighway(Builder $q, string $h): void { $q->where('highway', $h); }
}
```

The `'hashed'` cast means `$station->access_password = 'plain'` hashes automatically and
a plaintext password can never be persisted by accident.

Acceptance tests:
- Creating a station without `public_id` assigns a 26-char ULID.
- `route('dossier.show', $station)` uses `public_id`, not `id`.
- Two stations with codes `LPT2-GCP-001` and `lpt2-gcp-001` cannot both exist.
- Setting `access_password` stores a bcrypt hash, and `Hash::check()` verifies it.

### M1.3 — `coordinate_sets`

**Precision is the point of this milestone.** Column types must be exactly
`numeric(11,8)`, `numeric(12,8)`, `numeric(12,3)`, `numeric(10,3)` and casts exactly
`decimal:8` / `decimal:3`.

Acceptance test — this is the one that protects the poster's values:

```php
it('stores survey coordinates without losing precision', function () {
    $c = CoordinateSet::factory()->create([
        'latitude'  => '4.27412582',
        'longitude' => '103.43658211',
        'easting'   => '428765.212',
        'northing'  => '472318.678',
        'ellipsoidal_height'  => '128.346',
        'orthometric_height'  => '112.436',
    ]);

    expect($c->fresh())
        ->latitude->toBe('4.27412582')
        ->longitude->toBe('103.43658211')
        ->easting->toBe('428765.212')
        ->northing->toBe('472318.678');
});
```

Plus a schema test asserting the actual Postgres column types via
`information_schema.columns` — because a migration that silently used `double precision`
would pass the round-trip test on small values and fail in the field.

### M1.4 — `specifications`

Per [`../02-data-model.md`](../02-data-model.md) §4. All GNSS fields nullable except
`station_id` and `qc_status` — real survey records arrive incomplete, and the UI must be
able to render partial data (Phase 04 handles the empty states).

Acceptance: a `Specification` with only `qc_status` set saves and loads cleanly.

### M1.5 — `download_logs`

```php
final class DownloadLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;   // append-only

    public function prunable(): Builder
    {
        return static::where('downloaded_at', '<',
            now()->subDays(config('dossier.downloads.retention_days')));
    }
}
```

IP hashing helper: `hash_hmac('sha256', $ip, config('app.key'))`. **Never store a raw IP.**

Acceptance: a unit test asserts `ip_hash` is 64 hex chars, differs per IP, is stable for
the same IP, and that no column holds the original address.

### M1.6 — Factories, seeder, formatter

**`StationFactory`** with states: `active()`, `damaged()`, `unpublished()`,
`withoutCoordinates()`, `withoutSpecification()` — the incomplete states exist so
empty-state UI gets exercised.

**`DemoStationSeeder`** — 25 stations `LPT2-GCP-001` … `LPT2-GCP-025`, chainages spread
across KM 300–340, mixed directions and sections, statuses weighted heavily to `active`,
**two deliberately incomplete records**, and `LPT2-GCP-015` hard-coded to the poster:

```php
Station::create([
    'code' => 'LPT2-GCP-015', 'highway' => 'LPT2', 'section' => 'E1',
    'km' => '318.200', 'direction' => Direction::Westbound,
    'monument_type' => 'Concrete Block', 'installed_at' => '2025-03-15',
    'status' => StationStatus::Active, 'is_published' => true,
])->coordinateSet()->create([
    'latitude' => '4.27412582', 'longitude' => '103.43658211',
    'ellipsoidal_height' => '128.346',
    'easting' => '428765.212', 'northing' => '472318.678',
    'orthometric_height' => '112.436', 'geoid_model' => 'MyGEOID',
]);
// specification: Static / RTK, 120, 18, 1.60, 15, 'Geodetic L1/L2',
//                1.532, 'Bottom of ARP', 10.00, 15.00, QcStatus::Verified
```

**`App\Services\GeoFormatter`** — the single place any survey number becomes a string:

| Method | Input | Output |
|--------|-------|--------|
| `latitude()` | `4.27412582` | `4.27412582 °` |
| `longitude()` | `103.43658211` | `103.43658211 °` |
| `metres()` | `128.346` | `128.346 m` |
| `metresGrouped()` | `428765.212` | `428,765.212 m` |
| `tolerance()` | `10.00` | `≤ 10 mm` |
| `km()` | `318.200` | `KM 318.200` |
| `installedDate()` | `2025-03-15` | `15/03/2025` |

Acceptance: a unit test asserts each method against the **literal string from the poster**.
This test is the contract between the data model and the screens.

---

## Test Gate

```bash
php artisan migrate:fresh --seed
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
php artisan test --filter='Station|Coordinate|Specification|DownloadLog|GeoFormatter|Enum'
```

Assertions that must exist:

1. Seeding produces exactly **25** published-or-not stations, all with unique `public_id`s.
2. `LPT2-GCP-015` matches every poster value, field by field.
3. `GeoFormatter` output matches the poster strings character for character.
4. Postgres column types are `numeric` with the specified precision (queried from
   `information_schema`).
5. Soft-deleting a station removes it from `Station::published()` but keeps the row.
6. Deleting a station cascades to `coordinate_sets` and `specifications`.
7. `DownloadLog::prunable()` selects only rows past the retention window.

**Boost verification:** run `database-schema` via MCP and confirm all five tables, their
columns, and their indexes are as documented. Paste the table list into Sign-Off.

---

## Definition of Done

- [ ] `php artisan migrate:fresh --seed` produces the full demo dataset from clean.
- [ ] Every migration has a working `down()` and `migrate:rollback` is clean.
- [ ] No `float`/`double` column anywhere in the schema.
- [ ] All models are `final`, use `declare(strict_types=1)`, and have typed relations.
- [ ] Larastan level 6 passes with no baseline entries.

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | 2026-09-07 |
| **Result** | `php artisan migrate:fresh --seed` succeeds from clean. `composer test` → Pint passed, PHPStan level 7 passed (0 errors), Pest **66/66** passed (247 assertions) — includes all of Phase 00's 29 plus 37 new tests for enums, all four models, `GeoFormatter`, and `DemoStationSeeder`. `migrate:rollback --step=4` then `migrate` round-trips cleanly. No `float`/`double` column anywhere in the schema (verified via `information_schema` in `CoordinateSetTest`). No `env()` outside `config/`. |
| **`database-schema` output** | Not queried via a live Boost MCP call in this session (see Phase 00 sign-off note on the harness's MCP client); verified equivalently via `psql \d` on `stations` and `coordinate_sets` directly against the real Postgres 17 database — confirmed `numeric(11,8)`/`numeric(12,8)`/`numeric(12,3)`/`numeric(10,3)` precision, the `stations_code_lower_unique` functional index, the `stations_published_idx` partial index, and cascading FKs from `coordinate_sets`/`specifications`/`download_logs` to `stations`. |
| **Commit / tag** | Pending commit; tag `phase-01-complete` to follow. |

## Phase Log

- **2026-09-07** — M1.1: Added `App\Contracts\HasLabel`/`HasColor` and all six enums. Resolved a plan inconsistency in favour of `02-data-model.md` (see Deviation above) — only `StationStatus`/`QcStatus` implement `HasColor`.
- **2026-09-07** — M1.2: `stations` migration with the ULID `public_id`, case-insensitive functional unique index on `code`, and a partial index on `is_published` (raw `DB::statement`, not expressible via the fluent Blueprint API). `Station` model: `getRouteKeyName() = 'public_id'`, ULID assigned in `booted()`, `'access_password' => 'hashed'` cast, `published`/`forHighway` scopes. Deferred `InteractsWithMedia` to Phase 06 (package not installed yet).
- **2026-09-07** — M1.3: `coordinate_sets` migration/model at the poster's exact precision (`decimal:8` lat/lon, `decimal:3` everything else). Added a schema-level test against `information_schema.columns` specifically to catch a future migration accidentally using `double precision`, which would pass a naive round-trip test on small numbers.
- **2026-09-07** — M1.4: `specifications` migration/model, all GNSS fields nullable except `station_id`/`qc_status` per the "real survey records arrive incomplete" requirement.
- **2026-09-07** — M1.5: `download_logs` migration/model, `MassPrunable`, `CREATED_AT`/`UPDATED_AT` both `null` (append-only, single `downloaded_at` timestamp — matches the column list in `02-data-model.md` §6 exactly, which does not include Laravel's default timestamp pair). Noted the deferred FK on `media_id` above.
- **2026-09-07** — M1.6: Four factories (`StationFactory` with `active()`/`damaged()`/`unpublished()` states — `withoutCoordinates()`/`withoutSpecification()` weren't implemented as named states since a bare `Station::factory()->create()` already has neither relation attached by default; the seeder instead uses `CoordinateSet::factory()->for($station)->create()` explicitly where it wants one, which reads clearer than a "without" state whose name describes the default), `GeoFormatter` with every method asserted against a literal poster string, and `DemoStationSeeder` producing exactly 25 stations with `LPT2-GCP-015` as the byte-for-byte reference fixture and two deliberately incomplete records (station #7, #22) for empty-state coverage. Hit and fixed a real bug: `DatabaseSeeder`'s inherited `WithoutModelEvents` trait was silently breaking ULID assignment (see Deviation above).
