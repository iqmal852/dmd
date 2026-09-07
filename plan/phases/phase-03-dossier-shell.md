# Phase 03 — Dossier Shell & Access Control

| | |
|---|---|
| **Status** | ✅ Complete |
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
| [x] | **M3.1** | Config-driven routing: `/{prefix}/{public_id}` with the prefix read from `config('dossier.route_prefix')` | 2026-09-07 | `routes/dossier.php`, `tests/Feature/Dossier/RoutePrefixTest.php` |
| [x] | **M3.2** | `AccessGate` service + `EnsureDossierUnlocked` middleware implementing the full behaviour matrix | 2026-09-07 | `app/Services/AccessGate.php`, `tests/Feature/Dossier/AccessControlTest.php` (19 tests) |
| [x] | **M3.3** | Unlock screen (`/d/{code}/unlock`), throttled, session-persisted for `DOSSIER_UNLOCK_TTL` | 2026-09-07 | `resources/js/pages/dossier/unlock.tsx`, verified live in Chrome (correct/wrong password, redirect) |
| [x] | **M3.4** | Fail-closed behaviour on misconfiguration (password mode with no password → 503) | 2026-09-07 | `AccessControlTest::test_misconfigured_password_mode_fails_closed_with_503` |
| [x] | **M3.5** | `StationSummaryData` DTO + `DossierOverviewController` | 2026-09-07 | `app/Data/*.php`, `tests/Feature/Dossier/OverviewTest.php` |
| [x] | **M3.6** | Overview React page: title block, status pill, meta row, Quick View, four module tiles | 2026-09-07 | `resources/js/pages/dossier/overview.tsx`, `tests/Browser/DossierOverviewTest.php`, verified live in Chrome |
| [x] | **M3.7** | Landing page `/` — How It Works (5 steps) + Key Benefits (5 items) from poster panels 5 & 6 | 2026-09-07 | `resources/js/pages/welcome.tsx`, `tests/Browser/LandingPageTest.php`, verified live in Chrome |

---

> **Deviation (2026-09-07):** The intended-URL round-trip (middleware redirects to the
> unlock form, form submits back) cannot use `->with('intended_url', ...)` session
> flash as originally sketched — Laravel flash data survives exactly one subsequent
> request, and this flow is GET (show unlock form) *then* POST (submit password), so
> the flash would already be gone by the time the form posts. Fixed by passing the
> target as a `redirect` query parameter carried through as a hidden form field
> instead, with `UnlockSubmitController::safeRedirectTarget()` validating it stays
> within this station's own dossier path (`/{prefix}/{public_id}/...`) before ever
> redirecting to it — otherwise it would be an open redirect, since the value
> round-trips through an unauthenticated GET/POST pair.
>
> **Deviation (2026-09-07):** `GeoFormatter::installedDate()` originally type-hinted
> `Carbon\Carbon|string|null`, but `AppServiceProvider::configureDefaults()` (already
> in the starter kit) calls `Date::use(CarbonImmutable::class)`, so every Eloquent
> date cast actually produces a `CarbonImmutable`, not a `Carbon`. This was a real
> `TypeError` caught immediately by hitting the route manually (`GET /d/{id}` → 500)
> before any automated test was even written — fixed by widening the hint to
> `Carbon\CarbonInterface|string|null`, which both classes implement.
>
> **Deviation (2026-09-07):** Built three M2.3 primitives just-in-time, as the design
> system phase's own rule intended: `NeuTile` (the four module tiles), `MetaChip`
> (the Highway/KM/Section/Direction row), and `DossierLayout` (header shell). None of
> the four module tiles link anywhere yet — `NeuTile` accepts an optional `href` and
> renders as a non-interactive, disabled-looking tile when it's absent, since
> Coordinates/Map/Photos/Files routes don't exist until Phases 04-07. Each of those
> phases will come back and add the real `href`.
>
> **A real bug the manual smoke test caught before any automated test existed:**
> curling `/d/{id}` directly (before `dossier/overview.tsx` was written) surfaced the
> `CarbonImmutable` TypeError above. Curling it again after writing the React page
> surfaced a missing Vite-manifest entry (expected, page didn't exist yet) and nothing
> else — confirming the backend was solid before the frontend was even built.

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

- [!] A real phone scanning a QR pointing at `DOSSIER_BASE_URL/d/{public_id}` opens the Overview screen. **Not verified — no physical phone available in this development environment.** Verified equivalently: a real Chromium browser (via Pest's browser-testing plugin) loading the exact same URL at 390×844 and 1280×800, in light and dark, with the poster's values on screen and no horizontal overflow. Flag this for a manual check before the QR plates are actually printed.
- [!] The password unlock works when the QR is opened from an **in-app browser** (WhatsApp, Telegram, WeChat, Facebook) on both iOS and Android. **Not verified — same constraint.** The unlock flow was verified end-to-end interactively in desktop Chrome (correct password, wrong password, redirect back to the originally-requested URL) and the redirect mechanism deliberately avoids relying on session flash data precisely because in-app browsers are known to handle cookies/sessions inconsistently (see the Deviation above) — but the specific WebView quirks of each app remain unverified.
- [x] Flipping `DOSSIER_ACCESS_MODE` in `.env` (+ `config:clear`) changes behaviour with no code change. Verified live: toggled a station's `access_password` on/off and observed the gate engage/disengage without touching any code.
- [x] No station data of any kind is present in the HTML before unlock. `AccessControlTest::test_password_mode_redirects_to_unlock_and_leaks_no_data` and the browser test `renders the unlock screen with no javascript errors and no station data` both assert this; also confirmed visually — the unlock screen shows only the station code.
- [x] Overview matches poster panel 1 at 390px and at 1280px. Verified both by automated browser test and by interactive Chrome screenshots at 1200px; screenshots saved.

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | 2026-09-07 |
| **Result** | `composer test` → Pint clean, PHPStan level 7 (0 errors), Pest **114/114** passed (448 assertions) — 19 access-control-matrix tests, 4 Overview prop-shape/query-count tests, 2 route-prefix tests (one via a genuine subprocess boot), 2 landing-page tests, and 4 real-Chromium browser tests (poster values at 390×844, tap targets, no JS errors, no data leak pre-unlock, screenshots at both breakpoints). `npm run check`/`types:check`/`build` all clean. Verified `route:cache` and `config:cache` both work correctly against the new routes/config (a closure-based `Route::bind` survives `route:cache` — confirmed by hitting a cached-route server directly). |
| **Real-device scan verified on** | Not tested — no physical device in this environment. Equivalent coverage via real-Chromium browser tests at 390×844 (see above). |
| **In-app browsers tested** | Not tested — no physical device in this environment. |
| **Screenshots** | `plan/evidence/phase-03/phase-03-overview-mobile.png`, `phase-03-overview-desktop.png` |
| **Commit / tag** | Pending commit; tag `phase-03-complete` to follow. |

## Phase Log

- **2026-09-07** — M3.1: `routes/dossier.php` with `Route::bind('station', ...)` scoped to published + non-soft-deleted, and `AddNoindexHeader` middleware on the whole `dossier.` prefix group. Verified `DOSSIER_ROUTE_PREFIX=gcp` actually moves the route via a subprocess boot (config overrides in the current test process can't retroactively re-register already-booted routes, so this needed the same technique as Phase 02's `DevUiRouteTest`).
- **2026-09-07** — M3.2: `AccessGate` service exactly matching the plan's pseudocode, `EnsureDossierUnlocked` middleware. 19 tests covering every row of the access-mode matrix from `04-env-configuration.md` §6, throttling, unlock TTL expiry (via `$this->travel()`), and per-station unlock scoping (unlocking A doesn't unlock B).
- **2026-09-07** — M3.3: Unlock screen built and verified end-to-end interactively in Chrome — wrong password shows a generic error, correct password redirects back to the originally-requested dossier URL. Found and fixed the session-flash timing bug described in the Deviation above before it ever reached a test.
- **2026-09-07** — M3.4: Fail-closed 503 with no data in the response body; the "resolved by a station-specific password" counter-case is also tested (a global misconfiguration doesn't block a station that has its own password).
- **2026-09-07** — M3.5: `StationSummaryData`/`QuickViewData`/`ModuleAvailability` DTOs. `ModuleAvailability`'s photo/panorama/document counts are honestly `0`/`false` until Phase 06/07 install medialibrary — this is the correct state for a station with no media yet, not a stub. Hit and fixed the `CarbonImmutable` TypeError described in the Deviation above.
- **2026-09-07** — M3.6: Built `NeuTile`, `MetaChip`, `DossierLayout` just-in-time (M2.3) alongside the Overview page. Verified live in Chrome in both dark and light mode, confirmed the four module tiles show the correct enabled/disabled state per real seeded data (only "Coordinates" enabled for `LPT2-GCP-015`, since no media exists yet).
- **2026-09-07** — M3.7: Replaced the starter kit's default welcome page entirely with the poster's How-It-Works/Key-Benefits landing page. Verified live in Chrome; matches Picture1.png panels 5 and 6.
