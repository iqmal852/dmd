# Phase 00 — Foundation & Tooling

| | |
|---|---|
| **Status** | ⬜ Not started |
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
| [ ] | **M0.1** | Laravel 13 React starter kit scaffolded, boots, `/` renders | | |
| [ ] | **M0.2** | PostgreSQL 17 via Docker Compose; app + test DBs connected | | |
| [ ] | **M0.3** | Starter-kit registration / password-reset / verification routes removed; single-admin seeder in place | | |
| [ ] | **M0.4** | Laravel Boost installed, `boost:install` run, MCP server reachable from the editor | | |
| [ ] | **M0.5** | Pest 5, Larastan level 6, Pint, ESLint, Prettier, TypeScript strict — all configured and passing | | |
| [ ] | **M0.6** | `config/dossier.php` + `.env.example` + the "no `env()` outside config" guard test | | |
| [ ] | **M0.7** | GitHub Actions CI running the full gate on every push | | |

---

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
| **Gate run on** | |
| **Result** | |
| **Boost `application-info` output** | |
| **Commit / tag** | |

## Phase Log

_Append one dated line per completed milestone._
