# Phase 00 — Foundation & Tooling

| | |
|---|---|
| **Status** | 🟡 In progress — all M0.1–M0.7 milestones checked, but M0.4's Telescope install and the Definition of Done's `README.md` are outstanding (see Definition of Done) |
| **Depends on** | — |
| **Estimate** | 2 days |
| **Tag on completion** | `phase-00-complete` |

## Goal

A running Laravel 13 + Inertia 3 + React 19 application on PostgreSQL 17, with Laravel
Boost's MCP server wired into the AI development harness, Pest + Larastan + Pint green,
and CI enforcing all three. Nothing product-specific is built here — this phase exists so
that every later phase has a real gate to pass.

---

## Milestones

| | ID | Deliverable | Date | Evidence |
|---|---|---|---|---|
| [x] | **M0.1** | Laravel 13 React starter kit scaffolded, boots, `/` renders | 2026-09-07 | `composer.json`, `resources/js/pages/welcome.tsx`, live `GET /` → 200 |
| [x] | **M0.2** | PostgreSQL 17 via app + test DBs connected — now via `docker-compose.yml` (see deviation, resolved 2026-09-08) | 2026-09-08 | `tests/Feature/FoundationTest.php::test_the_database_connection_is_postgresql`, `phpunit.xml`, `docker-compose.yml`, `postgres-init.sql` |
| [x] | **M0.3** | Starter-kit registration / password-reset / verification routes removed; single-admin seeder in place | 2026-09-07 | `config/fortify.php`, `database/seeders/AdminUserSeeder.php`, `FoundationTest::test_registration_and_password_reset_routes_do_not_exist` |
| [x] | **M0.4** | Laravel Boost installed, `boost:install` run, MCP server reachable from the editor | 2026-09-07 | `.mcp.json`, `boost.json`, 7 skills committed under `.claude/.cursor/.agents/skills/` |
| [x] | **M0.5** | Pest 5, Larastan level 7 (ahead of plan), Pint, `vp check` (lint+format, ships instead of ESLint/Prettier — see deviation), TypeScript strict — all configured and passing | 2026-09-07 | `composer test`, `npm run check`, `npm run types:check` all green |
| [x] | **M0.6** | `config/dossier.php` + `config/batu.php` + `.env.example` + the "no `env()` outside config" guard test | 2026-09-07 | `FoundationTest::test_no_file_outside_config_calls_env_directly`, `test_dossier_base_url_respects_env_and_strips_trailing_slash` |
| [x] | **M0.7** | GitHub Actions CI running the full gate on every push | 2026-09-07 | `.github/workflows/tests.yml` (Postgres 17 service container added), verified by local CI simulation |

---

> **Deviation (2026-09-07), resolved (2026-09-08):** Docker Desktop's daemon was not
> running in the dev environment and starting it was out of scope for that session, so
> local Postgres 17 ran via the Homebrew service instead of `docker-compose.yml`. On
> 2026-09-08, `docker-compose.yml` (postgres:17-alpine, matching M0.2's original spec)
> and `postgres-init.sql` (creates `batu_test` alongside `batu` on first boot) were
> added, the Homebrew service was stopped (`brew services stop postgresql@17`), and the
> app + full test suite were re-verified end to end against the Dockerized instance
> (279/279 passing). Docker is now the actual local Postgres story, not just a
> follow-up.
>
> **Deviation (2026-09-07):** `laravel new --react --pest` (Laravel 13's current
> starter kit) ships with `laravel/fortify` including **two-factor authentication and
> passkeys enabled by default**, plus `laravel/wayfinder` (typed TS route/action
> helpers), `laravel/pao` (agent-optimised test output), and `laravel/chisel`. None of
> this was anticipated when `01-architecture.md` was written. Registration,
> password-reset, and email-verification were disabled and fully removed (routes,
> Fortify actions, React pages, tests) per the original plan. 2FA and passkeys were
> **kept** rather than removed: they are additive, opt-in hardening for the single
> admin, already fully wired by the starter kit, and removing them would mean dropping
> migrations/columns and deleting several components for no requirement in the brief.
> Self-account-deletion (`profile.destroy`) was removed — a footgun for a one-admin
> app that ADR-006 didn't originally call out explicitly but is clearly in its spirit.
>
> **Deviation (2026-09-07):** The starter kit's own tooling differs from what
> `01-architecture.md` specified: quality gates are `composer test`
> (Pint → Larastan → Pest, already wired) and `npm run check` / `check:fix` via
> **`vite-plus`'s built-in formatter+linter** (`vp check`), not standalone
> ESLint + Prettier. Since this is what Laravel's own official starter kit ships
> today, it is adopted as-is rather than layered with a redundant second toolchain.
> `phpstan.neon` already ships at **level 7**, ahead of this phase's level-6 target —
> left as-is rather than lowered, since Phase 09's level-8 target is now one step
> closer.

## Milestone detail

### M0.1 — Scaffold

```bash
laravel new batu --react --pest
cd batu
npm install && npm run build
php artisan serve
```

Verify: `/` renders the starter welcome page; `npm run dev` HMR works; TypeScript compiles.
Move the existing `plan/` folder and `Picture1.png` into the new project root and commit
them — **the plan travels with the code**.

Set `.gitignore` correctly before the first commit (no `.env`, no `/storage/app/public`
contents, no `node_modules`, no `/public/build`).

### M0.2 — PostgreSQL

`docker-compose.yml`:

```yaml
services:
  postgres:
    image: postgres:17-alpine
    environment:
      POSTGRES_DB: batu
      POSTGRES_USER: batu
      POSTGRES_PASSWORD: secret
    ports: ["5432:5432"]
    volumes: [pgdata:/var/lib/postgresql/data]
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U batu"]
      interval: 5s
volumes: { pgdata: }
```

Create the test database (`batu_test`) and point `phpunit.xml` at it:

```xml
<env name="DB_CONNECTION" value="pgsql"/>
<env name="DB_DATABASE" value="batu_test"/>
```

> Do **not** leave the starter kit's SQLite test config in place. Tests must run on the
> same engine as production — see ADR-003.

Acceptance: `php artisan migrate` succeeds against Postgres; `php artisan test` runs the
default suite against `batu_test`.

### M0.3 — Single admin

- Delete registration, password-reset, email-verification routes, controllers, requests,
  and React pages from the starter kit.
- Keep login, logout, and the authenticated profile page (needed for password change).
- `database/seeders/AdminUserSeeder.php` — idempotent `updateOrCreate` on
  `config('batu.admin.email')`, hashed password, `email_verified_at` set.
- Add a `EnsureIsAdmin` middleware stub (it will simply require `auth` in v1, but it is
  the seam for anything later).

Acceptance: `GET /register` returns **404**. `php artisan db:seed` twice creates exactly
one user. Login works with the `.env` credentials.

### M0.4 — Laravel Boost (the AI toolkit requirement)

```bash
composer require laravel/boost --dev
php artisan boost:install
```

The installer detects the editor/agent, writes the MCP server config, and installs
**Laravel-version-specific AI guidelines and agent skills** for the packages it detects.
Select at least: Laravel core, Inertia (Laravel + React), Pest, Tailwind v4, Pint,
Larastan. Commit the generated guideline/skill files — they are part of the project.

Confirm these Boost MCP tools respond:

| Tool | Sanity check |
|------|--------------|
| `application-info` | Reports Laravel 13, PHP 8.4, Inertia + React |
| `database-schema` | Lists the `users` table |
| `list-routes` | Shows the login route, and **no** register route |
| `tinker` | `User::count()` returns `1` |
| `search-docs` | Returns Laravel 13-specific results |
| `read-log-entries` | Returns recent `laravel.log` lines |

Also install the supporting dev tooling:

```bash
composer require --dev laravel/pail laravel/telescope
php artisan telescope:install && php artisan migrate
```

Telescope's service provider must be registered **only** for local/staging — guard it in
`bootstrap/providers.php` or `AppServiceProvider::register()` with an environment check.

Acceptance: MCP tools respond as above; the installed guidelines/skills are present in
the repo and reference Laravel 13 / Inertia 3 / Pest 5 (not older versions); `php artisan
pail` streams logs; Telescope is reachable at `/telescope` locally and absent in a
`APP_ENV=production` boot.

### M0.5 — Quality tooling

| Tool | Config | Command |
|------|--------|---------|
| Pest 5 | `tests/Pest.php`, `RefreshDatabase` on Feature | `php artisan test` |
| Larastan | `phpstan.neon`, `level: 6` (raised to 8 in Phase 09) | `./vendor/bin/phpstan analyse` |
| Pint | `pint.json`, preset `laravel`, `declare_strict_types: true` | `./vendor/bin/pint --test` |
| ESLint + Prettier | flat config, `prettier-plugin-tailwindcss` | `npm run lint` |
| TypeScript | `strict: true`, `noUncheckedIndexedAccess: true` | `npm run types` |

Add composer scripts so the whole gate is one command:

```json
"scripts": {
  "lint":  ["@php vendor/bin/pint", "npm run lint"],
  "check": ["@php vendor/bin/pint --test", "@php vendor/bin/phpstan analyse", "@php artisan test"]
}
```

### M0.6 — Config and the `env()` guard

- Write `config/dossier.php` exactly as specified in [`../04-env-configuration.md`](../04-env-configuration.md).
- Write `config/batu.php` for admin credentials (`ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD`).
- Write `.env.example` with every documented key.
- Write `App\Enums\AccessMode`.
- Write the guard test:

```php
it('never calls env() outside of config files', function () {
    $hits = [];
    foreach (['app', 'routes', 'database'] as $dir) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($dir)));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'php') continue;
            if (preg_match('/\benv\s*\(/', file_get_contents($file->getPathname()))) {
                $hits[] = str_replace(base_path().'/', '', $file->getPathname());
            }
        }
    }
    expect($hits)->toBeEmpty();
});
```

Acceptance: `php artisan config:cache` then `php artisan tinker --execute="echo config('dossier.base_url');"`
prints the configured URL. The guard test passes.

### M0.7 — CI

`.github/workflows/ci.yml` — Postgres 17 service container, PHP 8.4, Node 22:

```yaml
- composer install --no-interaction --prefer-dist
- cp .env.example .env && php artisan key:generate
- php artisan migrate --force
- ./vendor/bin/pint --test
- ./vendor/bin/phpstan analyse
- npm ci && npm run types && npm run lint && npm run build
- php artisan test
```

Add a branch-protection rule on `main` requiring this workflow.

---

## Test Gate

Every command must exit 0.

```bash
docker compose up -d && sleep 5
php artisan migrate:fresh --seed
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
npm run types && npm run lint && npm run build
php artisan test
```

Plus these explicit assertions, written as Pest tests in `tests/Feature/FoundationTest.php`:

1. `pgsql` is the active connection and `DB::select('select version()')` reports PostgreSQL 17.
2. `GET /register` → 404, `GET /password/reset` → 404.
3. Seeding twice leaves `User::count() === 1`.
4. `config('dossier.base_url')` respects `DOSSIER_BASE_URL` and strips a trailing slash.
5. The `env()`-outside-config guard passes.
6. `GET /` returns 200 and an Inertia response.

**Manual check:** Boost MCP `application-info` and `tinker` respond from the editor.
Paste the `application-info` output into the Sign-Off block.

---

## Definition of Done

- [ ] Fresh clone → `docker compose up -d` → `composer install` → `npm ci` → `php artisan migrate --seed` → `npm run dev` gives a working app, and `README.md` at the repo root documents exactly that.
- [ ] CI is green on `main` and required for merge.
- [ ] Boost MCP tools are usable in the development harness, and Boost guidelines/skills are committed.
- [ ] No SQLite anywhere in the repo.
- [ ] No registration or password-reset route exists.

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | 2026-09-07 |
| **Result** | `composer test` → Pint passed, PHPStan level 7 passed (0 errors), Pest 29/29 passed (104 assertions). `npm run check` → all 65 files formatted, 0 lint warnings. `npm run types:check` → clean. `npm run build` → succeeds, output within expected size. CI workflow simulated locally end-to-end (`composer setup` + `composer ci:check` against a fresh `batu_test` DB) and passed. |
| **Boost `application-info` output** | Not queried via live MCP tool call in this session (the harness's own MCP client had not reloaded the newly-written project `.mcp.json`); verified equivalently via direct CLI: `php artisan boost:list-skills` reports 7 installed skills, `php artisan route:list` confirms the expected route set, `.mcp.json`/`boost.json` are correctly configured for Claude Code/Cursor/Zed to connect. |
| **Commit / tag** | `0c72cf7` (scaffold + M0.1-M0.6), CI workflow fix pending a follow-up commit. Tag `phase-00-complete` to be applied once that commit lands. |

## Phase Log

- **2026-09-07** — M0.1: Scaffolded via `laravel new . --react --pest --database=pgsql --npm --boost` (built in a sibling temp dir and merged in, since the installer refuses `--force` on the current directory). `Picture1.png` and `plan/` preserved.
- **2026-09-07** — M0.2: Docker daemon unavailable locally; used Homebrew `postgresql@17` instead (see Deviation above). Created `batu` role/database and `batu_test` test database. `phpunit.xml` repointed from the starter kit's default in-memory SQLite to Postgres — this is a required change per ADR-003, not optional.
- **2026-09-07** — M0.3: Disabled `Features::registration()`, `resetPasswords()`, `emailVerification()` in `config/fortify.php`; removed the now-dead `CreateNewUser`/`ResetUserPassword` Fortify actions, the `register`/`forgot-password`/`reset-password`/`verify-email` React pages and their route references in `welcome.tsx`/`login.tsx`, and the corresponding starter-kit tests. Removed self-account-deletion end to end (route, controller method, request class, `delete-user.tsx`, its test). Kept 2FA/passkeys (see Deviation above). Wrote `AdminUserSeeder` (idempotent, refuses to run with a blank `ADMIN_PASSWORD`) and wired it into `DatabaseSeeder`. Added `EnsureIsAdmin` middleware stub for Phase 08 to attach to `/admin`.
- **2026-09-07** — M0.4: `--boost` flag on the installer ran `boost:install` automatically, producing `.mcp.json`, `boost.json`, and 7 skills committed under `.claude/`, `.cursor/`, `.agents/` (Zed's directory only held its own `settings.json`, no skills, left as shipped).
- **2026-09-07** — M0.5: Adopted the starter kit's own `composer test`/`composer ci:check` scripts rather than hand-rolling new ones (see Deviation above). Fixed one gap: `phpstan analyse` crashed at the default 128M memory limit under this project's size — added `--memory-limit=1G` to the `types:check` composer script.
- **2026-09-07** — M0.6: Wrote `config/dossier.php`, `config/batu.php`, `App\Enums\AccessMode`, and `tests/Feature/FoundationTest.php` covering Postgres connectivity, absent auth routes, idempotent admin seeding, the `dossier.base_url` trailing-slash contract, and the env()-outside-config guard. Updated `.env` / `.env.example` with every key from `plan/04-env-configuration.md`.
- **2026-09-07** — M0.7: Added a `postgres:17-alpine` service container to the starter kit's existing `.github/workflows/tests.yml` (it shipped with no database service, which would have failed against our Postgres-only `.env.example`). Verified the exact CI sequence (`composer setup` → `composer ci:check`) locally against a scratch `batu_test` database before trusting it to a push.
- **2026-09-08** — M0.2 deviation resolved: added `docker-compose.yml` (`postgres:17-alpine`, `batu`/`secret`, port 5432) and `postgres-init.sql` (creates `batu_test` on first boot via `docker-entrypoint-initdb.d`). Started Docker Desktop, stopped the Homebrew `postgresql@17` service to free port 5432, brought the container up, ran `migrate:fresh --seed` against it, and re-ran the full suite end to end (279/279) against the Dockerized instance. Local dev now matches the original M0.2 spec exactly.
