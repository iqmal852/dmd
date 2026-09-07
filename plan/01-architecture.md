# 01 — Architecture & Stack Decisions

Written ADR-style: each decision states the choice, the reasoning, and what was rejected.
Versions are the current stable releases as of **2026-09-07**; pin them in
`composer.json` / `package.json` and let Dependabot move them.

---

## 1. The stack at a glance

| Layer | Choice | Version |
|-------|--------|---------|
| Framework | Laravel | 13.x |
| Language | PHP | 8.4+ |
| Frontend bridge | Inertia.js (Laravel adapter + React adapter) | 3.x |
| UI | React + TypeScript | 19.x / 5.x |
| Build | Vite (via `laravel-vite-plugin`) | 7.x |
| Styling | Tailwind CSS v4 + custom Neumorphism token layer | 4.x |
| Database | PostgreSQL | 17 |
| Test runner | Pest (incl. browser testing) | 5.x |
| Static analysis | Larastan / PHPStan | 3.x, level 6 → 8 |
| Formatting | Laravel Pint (PHP), Prettier + ESLint (TS) | latest |
| AI harness | **Laravel Boost** (MCP server + Laravel-specific guidelines) | 2.x |
| Maps | Leaflet + Esri World Imagery tiles | 1.9.x |
| 360° viewer | Pannellum | 2.5.x |
| QR generation | `endroid/qr-code` | 6.x |
| Media | `spatie/laravel-medialibrary` | 11.x |

---

## ADR-001 — Laravel 13 with the official React starter kit

**Decision.** Scaffold with `laravel new batu --react`, which produces Laravel 13 +
Inertia 3 + React 19 + TypeScript + Tailwind 4 + Pest, with authentication scaffolding
already wired.

**Why.** The user asked explicitly for Laravel + React *using Laravel best practice*.
The officially maintained React starter kit **is** the best practice — it is what Laravel
ships, it is what Laravel's own docs assume, and it means the auth scaffolding, Vite
config, SSR entrypoint, and TypeScript setup are all Laravel-blessed rather than
hand-rolled.

**Rejected.**
- *Separate SPA + JSON API.* Doubles the auth surface, needs CORS and token handling, and
  buys nothing — there is no second client. Inertia gives us React without the API tax.
- *Blade + Livewire.* The user asked for React.
- *Laravel 12.* Laravel 13 is current stable; starting a greenfield project one major
  behind creates an immediate upgrade chore.

**Consequence.** Strip the starter kit's registration, password-reset, and email-
verification routes in Phase 00 — we have exactly one admin (see ADR-006).

---

## ADR-002 — Inertia over a REST/JSON API

**Decision.** Server-rendered props via Inertia. No public JSON API in v1.

**Why.** One source of truth for routing, validation, and authorisation. Form Requests
validate; controllers return `Inertia::render()` with typed props; no serialisation layer
to keep in sync. Inertia 3's deferred props let the Overview screen paint immediately
while photo and file metadata stream in — which directly serves the 4G-field-crew case.

**Consequence.** If a native app is ever wanted, an API is additive work. Given "web only,
no mobile" is explicit in the brief, that is the correct trade.

---

## ADR-003 — PostgreSQL 17, in development too

**Decision.** Postgres everywhere. No SQLite for tests.

**Why.** The user specified Postgres. Testing on SQLite while running Postgres in
production is the classic way to ship a bug that only appears in prod — and this schema
uses `numeric` with explicit precision, `jsonb`, generated columns, and case-insensitive
uniqueness, which behave differently on SQLite.

**How.** `docker-compose.yml` provides `postgres:17-alpine` for local dev, and a separate
`batu_test` database for the test suite via `phpunit.xml`. CI runs a Postgres service
container.

**PostGIS?** Not in v1. We store latitude/longitude as `numeric(11,8)` / `numeric(12,8)`
and never do spatial queries — there are 25 stations. If proximity search ("nearest GCP to
me") is ever wanted, add the extension and a generated `geography(Point,4326)` column
then. Noted in Phase 09's post-v1 list.

---

## ADR-004 — Laravel Boost as the AI development harness

**Decision.** Install `laravel/boost` as a dev dependency and run `php artisan boost:install`
in Phase 00, wiring its **MCP server** into the editor/agent used to build this project.

**Why.** The user asked to use Laravel's AI tooling, toolkit, and MCP. Boost is Laravel's
first-party answer: it exposes MCP tools that let an AI agent query *this* application's
real state rather than guessing — `database-schema`, `tinker` (execute code in the app
context), `read-log-entries`, `browser-logs`, `get-absolute-url`, `list-routes`,
`application-info` — and it installs version-specific guidelines so generated code matches
Laravel 13 / Inertia 3 / Pest 5 idioms instead of Laravel 9 idioms.

**Skills and guidelines.** `boost:install` also installs Laravel's curated **AI
guidelines** and **agent skills** into the project (Laravel core, Inertia + React, Pest,
Tailwind v4, Pint, and so on — selected to match the installed packages). These are
committed to the repo so every developer's agent works from the same rules. Re-run
`boost:update` after adding a major package so its skill is picked up.

**Practical effect on this build.** During Phases 03–08, verify behaviour by asking Boost's
`tinker` and `database-schema` tools rather than writing throwaway debug routes; use
`search-docs` for version-correct API usage before writing a feature; let the installed
skills drive idiom choices (Form Requests, enums, Pest style) instead of memory.

**Scope boundary.** This is development tooling only. The product itself ships no AI
features and does not depend on `laravel/ai` or `laravel/mcp` in v1 (both are pre-1.0 at
the time of writing). If the client later wants agents to query station data, `laravel/mcp`
is the additive path — noted in the Phase 09 backlog.

**Also install.**
- `laravel/pail` — readable, filterable live logs during development.
- `laravel/telescope` — request/query/job inspection, **local and staging only**, never
  enabled in production.

---

## ADR-005 — Neumorphism as a token layer on Tailwind v4, not a component library

**Decision.** Express Neumorphism as CSS custom properties in Tailwind v4's `@theme`
block, and build a small set of primitives (`<NeuCard>`, `<NeuTile>`, `<NeuStat>`,
`<NeuPill>`, `<NeuButton>`, `<NeuInput>`) on top. No off-the-shelf neumorphic library.

**Why.** Neumorphism is fundamentally *two shadows and one background* — a light shadow
up-left, a dark shadow down-right, on a surface that matches the page background. That is
a handful of tokens, not a dependency. Existing neumorphic React libraries are
unmaintained and would fight Tailwind v4.

**The accessibility problem, stated up front.** Neumorphism's defining trait — low
contrast between element and background — is also its defining accessibility failure. The
rule for this project: **the *surface* may be soft; the *content* may not.** All text,
all numeric values, and all interactive labels must hit WCAG AA 4.5:1. Depth is carried
by shadow, never by colour contrast alone. Every interactive element gets a visible focus
ring that does *not* rely on shadow. This is enforced by an automated contrast test in
Phase 02 (M2.5) — it is not left to judgement.

**Rejected.** shadcn/ui as the base. It is excellent, but its entire visual identity is
flat/bordered; we would be overriding every component. Instead we borrow shadcn's
*patterns* (composition, `cva` variants, Radix primitives for accessible behaviour) and
skin them ourselves. Radix UI primitives **are** used for dialog/tabs/dropdown behaviour,
because accessible focus trapping is not worth re-implementing.

---

## ADR-006 — Single admin, seeded from `.env`, no registration

**Decision.** One `users` row, created by `AdminUserSeeder` from `ADMIN_EMAIL` /
`ADMIN_PASSWORD`. The registration, password-reset, and verification routes shipped by the
starter kit are deleted. Login is rate-limited and the admin area sits behind
`auth` + a `EnsureIsAdmin` middleware.

**Why.** Explicit requirement. Deleting the routes rather than hiding them means there is
no dormant registration endpoint to be found later.

**Consequence.** Password rotation is `php artisan batu:admin-password` (an artisan
command written in Phase 08, M8.1), not a UI flow. Documented in the deploy runbook.

---

## ADR-007 — Leaflet + Esri World Imagery; Pannellum for 360°

**Decision.** Leaflet 1.9 with the free Esri World Imagery tile layer for satellite, and
OpenStreetMap as a switchable base layer. Pannellum for equirectangular panoramas.

**Why.** No API key, no billing account, no per-view cost, no vendor lock-in, both
MIT/BSD-licensed, both small. The Esri World Imagery layer gives the satellite look the
poster mockups show. Attribution requirements for both layers are respected in the UI.

**Both are lazy-loaded** via dynamic `import()` on their respective routes, so the Overview
screen never pays for them. The Overview's map *preview* is a static image (a cached tile
screenshot or a lightweight non-interactive Leaflet instance) — decided in Phase 05, M5.4.

**Rejected.** Google Maps (needs a billed key in `.env`, adds cost per map load, and the
brief gives no budget signal), MapLibre + MapTiler (still needs a key).

**Configurable anyway.** Tile URL templates live in `config/dossier.php` and are
`.env`-overridable, so swapping to a paid provider later is a config change.

---

## ADR-008 — Media via spatie/laravel-medialibrary

**Decision.** `spatie/laravel-medialibrary` manages station photos, panoramas, as-built
drawings, and documents. Three media collections per station: `photos`, `panoramas`,
`documents`.

**Why.** It gives us automatic conversions (thumbnail / preview / responsive `srcset`),
disk abstraction, ordering, custom properties per file (photo type, bearing, captured-at),
and safe filename handling — all things we would otherwise hand-roll badly. Responsive
image generation matters a lot for the 4G-field-crew constraint.

**Note.** Verify Laravel 13 compatibility at install time; if the stable line lags,
either pin the `12.x` branch or briefly hold on `11.23.x` with Laravel 13 — record the
outcome as a deviation in Phase 06.

**Original files are never served directly.** All downloads route through a controller
that checks the access gate and writes an audit row (Phase 07).

---

## ADR-009 — Configurable QR domain and access mode

**Decision.** A dedicated `config/dossier.php` holds every dossier-facing knob, and
nothing reads `env()` outside config files (so `config:cache` is safe in production).

```php
// config/dossier.php
return [
    'base_url'      => env('DOSSIER_BASE_URL', env('APP_URL')),
    'route_prefix'  => env('DOSSIER_ROUTE_PREFIX', 'd'),
    'access_mode'   => env('DOSSIER_ACCESS_MODE', 'public'),   // public | password
    'access_password' => env('DOSSIER_ACCESS_PASSWORD'),
    'unlock_ttl'    => (int) env('DOSSIER_UNLOCK_TTL', 720),   // minutes
    // ...
];
```

The QR payload is always built by a single `QrUrlBuilder` service from
`config('dossier.base_url')` — **never** from `url()`, `route()`, or a hard-coded string.
That is what makes the domain switchable: print QR plates pointing at
`https://gcp.myspatial.com.my` while the app also answers on a staging host.

Full key list and semantics: [`04-env-configuration.md`](04-env-configuration.md).

---

## ADR-010 — Layered application structure

**Decision.** Keep it boring and Laravel-shaped. No hexagonal architecture, no CQRS, no
repository interfaces over Eloquent.

```
app/
├── Actions/                    Single-purpose invokable classes for real operations
│   ├── Stations/CreateStation.php
│   ├── Stations/UpdateStationCoordinates.php
│   └── Qr/GenerateStationQr.php
├── Data/                       Readonly DTOs shaped for Inertia props
│   ├── StationSummaryData.php
│   ├── CoordinateSetData.php
│   └── SpecificationData.php
├── Enums/                      StationStatus, PhotoType, DocumentType, AccessMode…
├── Http/
│   ├── Controllers/
│   │   ├── Dossier/            Public, read-only
│   │   └── Admin/              Auth-gated
│   ├── Middleware/EnsureDossierUnlocked.php
│   └── Requests/               One per write operation
├── Models/                     Station, CoordinateSet, Specification, DownloadLog
├── Policies/StationPolicy.php
├── Services/QrUrlBuilder.php, GeoFormatter.php
└── Support/
```

**Why.** 25 stations, one admin, five read screens. The cost of ceremony here exceeds its
benefit. The one place we *do* invest in structure is **DTOs for Inertia props** — because
leaking Eloquent models straight into `Inertia::render()` is how a read-only public page
accidentally exposes an internal column. Every public prop passes through an explicit DTO
with an explicit field list.

**Rule:** the public dossier controllers must never pass a model or a `->toArray()` to
Inertia. A test in Phase 03 (M3.6) asserts the exact prop shape to enforce this.

---

## 2. Request lifecycle for the core read path

```
QR scan
  → GET https://{DOSSIER_BASE_URL}/d/{code}
  → EnsureDossierUnlocked middleware
        access_mode=public   → pass through
        access_mode=password → session has valid unlock? pass : redirect /d/{code}/unlock
  → DossierOverviewController::__invoke(Station $station)
        Station resolved by route-model binding on `code` (or `public_id`)
        eager-loads coordinateSet + specification; photo/file counts via withCount
  → StationSummaryData::from($station)      ← explicit DTO
  → Inertia::render('dossier/overview', [...])
  → React page renders inside DossierLayout (bottom tab bar + header)
```

Response caching: the read path is cached per station + access state with a cache tag
busted on any station write. Detailed in Phase 09, M9.2.

---

## 3. Environments

| Env | Purpose | Notes |
|-----|---------|-------|
| local | Development | `docker-compose` Postgres 17, `php artisan serve` + `npm run dev`, Telescope + Pail on, `DOSSIER_ACCESS_MODE=public` |
| testing | Pest | Separate `batu_test` Postgres DB, `RefreshDatabase`, media faked to a temp disk |
| staging | Client review | Mirrors production, seeded demo data, `noindex` headers, both access modes exercised |
| production | Live | `config:cache` + `route:cache` + `view:cache`, Telescope removed, HTTPS enforced, daily DB backup |

---

## 4. Repository layout

```
batu/
├── app/ bootstrap/ config/ database/ routes/ storage/   Laravel
├── resources/
│   ├── css/app.css                Tailwind v4 + @theme Neumorphism tokens
│   └── js/
│       ├── app.tsx ssr.tsx
│       ├── layouts/               DossierLayout, AdminLayout, GuestLayout
│       ├── pages/dossier/*        overview, coordinates, map, photos, files, unlock
│       ├── pages/admin/*
│       ├── components/ui/         Neumorphism primitives
│       ├── components/dossier/    Feature components
│       ├── hooks/  lib/  types/
├── tests/{Feature,Unit,Browser}/
├── plan/                          ← this folder; the plan travels with the code
└── docker-compose.yml
```
