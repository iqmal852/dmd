# Phase 03 — Dossier Shell & Access Control

| | |
|---|---|
| **Status** | ⬜ Not started |
| **Depends on** | Phases 01, 02 |
| **Estimate** | 3 days |
| **Tag on completion** | `phase-03-complete` |

## Goal

**This is the phase that makes the QR code work.** Scanning a plate opens `/d/{public_id}`,
passes the `.env`-driven access gate, and renders the Overview screen from panel 1 of the
poster: title block, meta row, map preview, Quick View card, and the four module tiles.

Every later module phase hangs off this shell.

---

## Milestones

| | ID | Deliverable | Date | Evidence |
|---|---|---|---|---|
| [ ] | **M3.1** | Config-driven routing: `/{prefix}/{public_id}` with the prefix read from `config('dossier.route_prefix')` | | |
| [ ] | **M3.2** | `AccessGate` service + `EnsureDossierUnlocked` middleware implementing the full behaviour matrix | | |
| [ ] | **M3.3** | Unlock screen (`/d/{code}/unlock`), throttled, session-persisted for `DOSSIER_UNLOCK_TTL` | | |
| [ ] | **M3.4** | Fail-closed behaviour on misconfiguration (password mode with no password → 503) | | |
| [ ] | **M3.5** | `StationSummaryData` DTO + `DossierOverviewController` | | |
| [ ] | **M3.6** | Overview React page: title block, status pill, meta row, Quick View, four module tiles | | |
| [ ] | **M3.7** | Landing page `/` — How It Works (5 steps) + Key Benefits (5 items) from poster panels 5 & 6 | | |

---

## Milestone detail

### M3.1 — Config-driven routes

```php
// routes/web.php
Route::prefix(config('dossier.route_prefix'))
    ->name('dossier.')
    ->group(function (): void {
        Route::get('{station}/unlock', UnlockFormController::class)->name('unlock');
        Route::post('{station}/unlock', UnlockSubmitController::class)
            ->middleware('throttle:dossier-unlock')->name('unlock.submit');

        Route::middleware(EnsureDossierUnlocked::class)->group(function (): void {
            Route::get('{station}', DossierOverviewController::class)->name('show');
            // coordinates / map / photos / files added in phases 04–07
        });
    });
```

Route-model binding resolves `{station}` on `public_id` **and scopes to published**:

```php
Route::bind('station', fn (string $value) => Station::query()
    ->where('public_id', $value)
    ->where('is_published', true)
    ->firstOrFail());
```

An unpublished or soft-deleted station gives a **404**, never a "this exists but you can't
see it" — a QR plate should not confirm the existence of an unpublished record.

**Every dossier response carries `X-Robots-Tag: noindex, nofollow`** and the layout emits
`<meta name="robots" content="noindex">`. These pages are reachable by anyone holding the
link, but they are not for search engines — a survey monument's coordinates should not
turn up in a Google result. `/robots.txt` disallows the dossier prefix as well.

Acceptance: setting `DOSSIER_ROUTE_PREFIX=gcp` makes `/gcp/{id}` resolve **and** `/d/{id}`
404. This is asserted by a test that swaps config and re-registers routes.

### M3.2 — The access gate

`App\Services\AccessGate`:

```php
public function isRequired(Station $station): bool
{
    return $station->access_password !== null
        || config('dossier.access_mode') === AccessMode::Password;
}

public function isMisconfigured(Station $station): bool
{
    return config('dossier.access_mode') === AccessMode::Password
        && $station->access_password === null
        && blank(config('dossier.access_password'));
}

public function check(Station $station, string $candidate): bool
{
    $hash = $station->access_password;

    return $hash !== null
        ? Hash::check($candidate, $hash)
        : hash_equals((string) config('dossier.access_password'), $candidate);
}

public function unlock(Station $station): void
{
    session()->put("dossier_unlocked.{$station->public_id}",
        now()->addMinutes(config('dossier.unlock_ttl'))->timestamp);
}

public function isUnlocked(Station $station): bool
{
    $exp = session("dossier_unlocked.{$station->public_id}");
    return $exp !== null && $exp > now()->timestamp;
}
```

Note the global-password comparison uses `hash_equals` (constant time). The per-station
path uses `Hash::check`, which is already constant time.

`EnsureDossierUnlocked` middleware: misconfigured → abort 503; not required → next;
unlocked → next; otherwise → redirect to the unlock route with the intended URL stashed.

Full matrix in [`../04-env-configuration.md`](../04-env-configuration.md) §6 — implement it
exactly, and write one test per row.

### M3.3 — Unlock screen

`GuestLayout` + a single `NeuCard`: MySpatial lockup, a lock icon, the station **code**
(showing `LPT2-GCP-015` is fine — it is printed on the plate the user is standing at), a
password field, and a submit button. Nothing else — no coordinates, no photos, no map, no
metadata leaked before unlock.

Throttling registered in `AppServiceProvider`:

```php
RateLimiter::for('dossier-unlock', fn (Request $r) => Limit::perMinute(
    config('dossier.unlock_throttle')
)->by($r->ip().'|'.$r->route('station')));
```

On success: `session()->regenerate()` (session fixation defence), then redirect to the
intended dossier URL. On failure: a generic "Incorrect password" — never distinguish
between "wrong password" and "this station uses a different password".

### M3.4 — Fail closed

If `DOSSIER_ACCESS_MODE=password` and no password is resolvable, return **503** with an
operator-facing message, logged at `error` level. It must never fall through to serving the
dossier openly.

Test: set the config to that state and assert 503 plus a log entry — and assert the
response body contains none of the station's data.

### M3.5 — DTO and controller

```php
final readonly class StationSummaryData
{
    public function __construct(
        public string $publicId,
        public string $code,
        public string $highway,
        public ?string $section,
        public string $km,             // pre-formatted "KM 318.200"
        public string $direction,      // pre-formatted "Westbound"
        public string $monumentType,
        public ?string $installedAt,   // pre-formatted "15/03/2025"
        public string $status,         // "Active"
        public string $statusColor,    // token name
        public ?QuickViewData $quickView,
        public ModuleAvailability $modules,   // counts: photos, panoramas, documents
    ) {}

    public static function from(Station $station): self { /* … */ }
}
```

**Formatting happens server-side, in `GeoFormatter`, once.** The React layer receives
display-ready strings. This avoids duplicating locale and precision rules in TypeScript,
where a `parseFloat` would quietly destroy the 8th decimal place.

`ModuleAvailability` carries counts so the four tiles can show a badge and can render
disabled when a station has no photos or no documents.

The controller eager-loads `coordinateSet` and uses `withCount` for media — one query for
the station, one for the counts. No N+1. Asserted by a test using
`DB::listen`/`assertQueryCount`.

### M3.6 — Overview page

`resources/js/pages/dossier/overview.tsx`, reproducing panel 1:

| Region | Component |
|--------|-----------|
| Header | `DossierLayout` — brand lockup + shield |
| Title block | `GCP STATION: {code}` + `NeuPill` status |
| Meta row | 4 × `MetaChip` — Highway, KM, Section, Direction |
| Map preview | Static image (Phase 05 replaces with the real preview), tappable → `/map` |
| Quick View | `NeuCard` with three `NeuGroup`s: WGS 84, GDM 2000, MyGEOID (Ortho) |
| Modules | 2×2 (mobile) / 4×1 (desktop) `NeuTile` grid — blue, green, cyan, violet |
| Caption | "Tap any module for full details" |

Desktop ≥ 1024px puts map preview and Quick View side by side, as the poster does.

**A prop-shape test enforces ADR-010:**

```php
$response->assertInertia(fn (Assert $page) => $page
    ->component('dossier/overview')
    ->has('station', fn (Assert $s) => $s
        ->hasAll(['publicId','code','highway','section','km','direction',
                  'monumentType','installedAt','status','statusColor','quickView','modules'])
        ->missing('id')            // internal PK must never reach the client
        ->missing('accessPassword')
        ->missing('access_password')
        ->etc(false)               // no extra keys at all
    ));
```

### M3.7 — Landing page

`/` reproduces poster panels 5 and 6: the 5-step "How It Works" strip and the 5 "Key
Benefits" cards, plus the brand lockup and the footer facts. Static, no data. It is what
someone sees if they type the bare domain instead of scanning.

---

## Test Gate

```bash
php artisan migrate:fresh --seed
./vendor/bin/pint --test && ./vendor/bin/phpstan analyse
npm run types && npm run lint && npm run build
php artisan test --filter='Dossier|Access|Unlock|Overview|Landing'
php artisan test --filter=Browser
```

Assertions that must exist:

1. **Public mode:** `GET /d/{public_id}` → 200, Inertia component `dossier/overview`.
2. **Password mode:** the same request → 302 to the unlock route; the response body
   contains no coordinate values.
3. Correct password → 302 to the intended URL, and the follow-up request → 200.
4. Wrong password → back with a generic error; **6 attempts in a minute → 429**.
5. Per-station password overrides the global mode in **all four** matrix rows.
6. Unlock expires after `DOSSIER_UNLOCK_TTL` minutes (travel the clock).
7. Unlocking station A does **not** unlock station B.
8. Misconfigured password mode → 503, no data leaked.
9. `DOSSIER_ROUTE_PREFIX=gcp` moves the route; the old prefix 404s.
10. Unpublished / soft-deleted station → 404.
10a. Every `/d/*` response carries `X-Robots-Tag: noindex, nofollow`; `/` does not.
11. Overview prop shape is exactly the DTO — no `id`, no `access_password`, no extra keys.
12. Overview issues ≤ 3 queries.
13. **Browser, 390×844:** the poster's values are visible on screen —
    `LPT2-GCP-015`, `ACTIVE`, `KM 318.200`, `Westbound`, `4.27412582`, `128.346 m`,
    `428,765.212 m`, `112.436 m` — the four tiles are present and ≥ 44px, and
    `document.body.scrollWidth <= window.innerWidth`.

---

## Definition of Done

- [ ] A real phone scanning a QR pointing at `DOSSIER_BASE_URL/d/{public_id}` opens the Overview screen. **Test this with an actual phone, not a simulator.**
- [ ] The password unlock works when the QR is opened from an **in-app browser** (WhatsApp, Telegram, WeChat, Facebook) on both iOS and Android — these webviews handle session cookies differently from Safari/Chrome and are how a shared link is most often opened. Record which were tested in Sign-Off.
- [ ] Flipping `DOSSIER_ACCESS_MODE` in `.env` (+ `config:clear`) changes behaviour with no code change.
- [ ] No station data of any kind is present in the HTML before unlock.
- [ ] Overview matches poster panel 1 at 390px and at 1280px.

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | |
| **Result** | |
| **Real-device scan verified on** | |
| **In-app browsers tested** | |
| **Screenshots** | `plan/evidence/phase-03/` |
| **Commit / tag** | |

## Phase Log

_Append one dated line per completed milestone._
