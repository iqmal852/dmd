# Phase 09 — Hardening & Release

| | |
|---|---|
| **Status** | 🟨 In progress — M9.1–M9.6 done, M9.7 blocked (no real client/devices in this environment) |
| **Depends on** | Phases 00–08 |
| **Estimate** | 3 days |
| **Tag on completion** | `v1.0.0` |

## Goal

Make it fast, safe, observable, and deployable — and write down how to run it so the next
person does not have to reverse-engineer the deployment from the code.

---

## Milestones

| | ID | Deliverable | Date | Evidence |
|---|---|---|---|---|
| [x] | **M9.1** | Security pass: headers, rate limits, dependency audit, `/security-review` on the full diff | 2026-09-08 | `fea6629`, `d6860aa`; `composer audit`/`npm audit --production` clean; `/security-review` skill couldn't run (repo has no `origin` remote to diff against — see note below), manual review done instead |
| [x] | **M9.2** | Performance pass: response caching, query budget, image budget, bundle budget | 2026-09-08 | `fea6629` — `App\Services\StationCache`, `StationCacheTest`, `BundleBudgetTest` (~162KB gzipped, under 180KB budget) |
| [x] | **M9.3** | Accessibility pass: axe on every public route, keyboard-only walkthrough, Lighthouse ≥ 95 | 2026-09-08 | `d6860aa` — `AccessibilityAuditTest` 16/16 (light+dark, every public route + unlock + login); fixed `text-neu-primary-bright` contrast and login.tsx's positive tabindex. Lighthouse itself not run (no staging URL in this environment) |
| [x] | **M9.4** | Observability: structured logging, error tracking, health check, DB backups | 2026-09-08 | `03e96c7` — JSON logs w/ request_id, extended `/up`, `batu:backup`/`batu:restore` proven against a real scratch DB. Error tracking (Sentry) documented but not installed — new Composer dependency needs approval first |
| [x] | **M9.5** | Larastan raised to level 8; baseline empty or explicitly justified | 2026-09-08 | `fea6629` — `phpstan.neon` level 8, 0 errors, no baseline |
| [x] | **M9.6** | Deployment runbook + production `.env` template + zero-downtime deploy script | 2026-09-08 | `8d52ebb` — `docs/DEPLOYMENT.md`, `docs/deploy.sh`, `.env.production.example` |
| [ ] | **M9.7** | Client UAT against `Picture1.png`, panel by panel, on real devices | | **Blocked** — needs a real client, a real iPhone, a real Android device, and a printed plate scanned outdoors in daylight; none available in this environment. Not attempted rather than faked |

---

## Milestone detail

### M9.1 — Security

**Headers** (middleware, applied globally):

```
Content-Security-Policy: default-src 'self';
  img-src 'self' data: blob: https://server.arcgisonline.com https://*.tile.openstreetmap.org;
  script-src 'self'; style-src 'self' 'unsafe-inline'; frame-ancestors 'none';
  base-uri 'self'; form-action 'self'
Strict-Transport-Security: max-age=31536000; includeSubDomains
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(self), camera=(), microphone=()
X-Frame-Options: DENY
```

`geolocation=(self)` is retained deliberately — a later "where am I relative to this
station" feature needs it, and the map screen may use it now.

**Checklist**

- [ ] All the Phase 03 access-gate tests still pass with `APP_ENV=production` + `config:cache`.
- [ ] No media file reachable outside the download controller; private disk verified.
- [ ] Rate limits on unlock, login, and download.
- [ ] `composer audit` and `npm audit --production` clean, or every finding triaged in writing.
- [ ] `APP_DEBUG=false` verified in production config; error pages leak nothing.
- [ ] Telescope removed or disabled in production; `/telescope` returns 404.
- [ ] Session cookies: `secure`, `httpOnly`, `sameSite=lax`.
- [ ] Run the `/security-review` skill over the complete diff and resolve every finding.
- [ ] Confirm no raw IP addresses are stored anywhere (grep the schema and the code).
- [ ] `X-Robots-Tag: noindex` on every dossier route and `/robots.txt` disallows the prefix; confirm with a Google Search Console URL inspection on staging.

### M9.2 — Performance

| Budget | Target | How measured |
|--------|--------|--------------|
| Overview TTFB | < 200 ms cached | Local + staging timing |
| LCP on 4G | < 2.5 s | Lighthouse mobile throttled |
| Entry JS | < 180 KB gzipped | Vite build report, asserted in CI |
| Queries per public route | ≤ 3 | Pest query-count assertion |
| Largest image @390px | < 400 KB | Browser test network assertion |

Work:

- Response caching per station + access state, tagged and busted on any station or media
  write (an observer). Cache only the public read path, never a gated response before
  unlock.
- `Model::preventLazyLoading()` in local/testing — this catches N+1 at development time
  rather than in production.
- `config:cache`, `route:cache`, `view:cache`, `optimize` in the deploy script.
- Vite: manual chunks for `leaflet` and `pannellum`; a CI check that fails the build if the
  entry chunk exceeds the budget.
- HTTP caching headers on media conversions (immutable, 1 year, hashed filenames).

### M9.3 — Accessibility

- axe-core over **every** public route in light and dark: zero serious/critical.
- Full keyboard-only walkthrough of both the dossier and admin: every action reachable,
  focus never lost, focus visible at every step, no keyboard traps outside modals.
- Screen-reader smoke test (VoiceOver on iOS — the actual field device) on Overview,
  Coordinates, Photos.
- Lighthouse Accessibility ≥ 95 on every public route.
- Verify `prefers-contrast: more` and `prefers-reduced-motion` paths still work end to end.

### M9.4 — Observability

- Structured JSON logging in production with a request ID on every line.
- Error tracking (Sentry or equivalent) with releases tagged to the deploy SHA.
- `/up` health check (Laravel's built-in) extended to verify the DB and the storage disk.
- Daily `pg_dump` to off-site storage, with a **documented and actually-tested restore**.
  An untested backup is not a backup.
- Queue worker supervision (Supervisor/systemd) with restart-on-failure and alerting —
  media conversions and download logging both depend on the queue running.
- Scheduler running (`schedule:run` every minute) for `model:prune`.

### M9.5 — Static analysis to level 8

Raise `phpstan.neon` to `level: 8` and fix what surfaces — mostly nullable handling, which
is exactly where this app's incomplete-survey-record cases live. Any baseline entry needs a
one-line comment explaining why it is acceptable.

### M9.6 — Deployment runbook

`docs/DEPLOYMENT.md` covering:

1. Server requirements: PHP 8.4, PostgreSQL 17, Redis, Nginx, Node 22 for building.
2. First deploy: clone, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`,
   `key:generate`, `migrate --force`, `db:seed --class=AdminUserSeeder`, `storage:link`,
   `optimize`.
3. Subsequent deploys: zero-downtime script (build to a new release dir, symlink swap,
   `migrate --force`, `optimize:clear && optimize`, `queue:restart`).
4. `.env.production` template with every key and a note on which are secrets.
5. **The QR domain warning, prominently:** changing `DOSSIER_BASE_URL` or
   `DOSSIER_ROUTE_PREFIX` invalidates every plate already installed. If it must change,
   add a permanent redirect from the old prefix and keep it forever.
6. Rotating the admin password: `php artisan batu:admin-password`.
7. Flipping access mode: change `DOSSIER_ACCESS_MODE`, then `php artisan config:cache`.
   Note that existing unlock sessions survive until they expire.
8. Backup and restore procedure, with the tested restore steps.
9. Rollback procedure.

### M9.7 — Client UAT

Walk `Picture1.png` panel by panel with the client on a real phone and a real desktop:

| Panel | Verify |
|:-----:|--------|
| 1 | Scan a printed plate → Overview matches: title, status, meta row, map preview, Quick View, four tiles |
| 2 | Coordinates, Specs & QC, and Location Map all match the mockups field for field |
| 3 | As-built drawing viewable and downloadable; DWG opens in CAD |
| 4 | All four photo types present; 360° panorama navigable |
| 5 | Landing page reproduces the five steps |
| 6 | Landing page reproduces the five benefits |
| Footer | Branding and the stated facts are correct |

Devices: at minimum one iPhone (Safari), one mid-range Android (Chrome), one desktop.
Test at least one scan **outdoors in daylight** — screen legibility in glare is a real
failure mode for a low-contrast design system, and it is the one thing no automated test
covers.

Log every gap as an issue; fix blockers before tagging `v1.0.0`.

---

## Test Gate

```bash
php artisan test                       # full suite, no filter
./vendor/bin/pint --test
./vendor/bin/phpstan analyse           # level 8
npm run types && npm run lint && npm run build
composer audit && npm audit --production
npx lighthouse <staging-url>/d/<id> --preset=desktop --view
npx lighthouse <staging-url>/d/<id> --form-factor=mobile --throttling.cpuSlowdownMultiplier=4
```

Release criteria — all must be true:

1. Full test suite green on Postgres 17.
2. Larastan level 8, no unjustified baseline entries.
3. Lighthouse mobile: Performance ≥ 90, Accessibility ≥ 95, Best Practices ≥ 95, SEO ≥ 90.
4. Zero serious/critical axe violations on any public route.
5. `composer audit` / `npm audit` clean or triaged in writing.
6. Entry bundle under budget; Leaflet and Pannellum in separate chunks.
7. Backup restore tested end to end on a scratch database.
8. Client UAT signed off against all six poster panels.
9. `DEPLOYMENT.md` followed successfully by someone who did not write it.

---

## Definition of Done

- [ ] Production deployed, `v1.0.0` tagged, QR plates printed from the app.
- [ ] Runbook proven by a fresh deploy following only the document.
- [ ] Monitoring and backups verified live.
- [ ] Client sign-off recorded below.

---

## Post-v1 backlog

Recorded here so it is not lost, and explicitly **not** in scope for v1:

- PWA / offline caching of visited dossiers — genuinely valuable for crews who lose signal,
  and the strongest candidate for v1.1.
- PostGIS + "nearest station to me" search.
- Multi-highway support beyond the `highway` field (E1, E2, other concessions).
- Coordinate transformation on the fly (WGS 84 ↔ GDM 2000) instead of stored values.
- Multiple admin users with roles, once a second person needs access.
- Bahasa Malaysia localisation.
- Scan analytics (which stations get visited, when) beyond download logs.
- Public read-only JSON API for the client's GIS systems.
- An app-side MCP server via `laravel/mcp` so the client's AI agents can query stations,
  coordinates, and documents directly — read-only, token-gated. Additive; needs the
  package to reach 1.0 first.

---

## Sign-Off

| | |
|---|---|
| **Gate run on** | 2026-09-08 — full suite (278/278), Pint, PHPStan level 8, `composer audit`, `npm audit --production` all clean. Lighthouse and a real `/security-review` diff not run (no staging URL, no `origin` remote — see M9.1/M9.3 evidence) |
| **Lighthouse scores** | Not run — no staging URL available in this environment |
| **Security review** | Manual (skill couldn't establish a diff baseline — no git remote); no findings beyond what M9.1/M9.4 already cover |
| **UAT date / attendees** | Not held — see M9.7 |
| **Client sign-off** | Not obtained — blocked on M9.7 |
| **Release tag** | `v1.0.0` — **not tagged**. Tagging this before a real client UAT and a real staging deploy would misrepresent the release; see Phase Log below |

## Phase Log

- 2026-09-08 — M9.1 (security headers, rate limits, private-disk verification test), M9.2 (StationCache response caching, bundle budget), M9.5 (Larastan level 8) landed in `fea6629`.
- 2026-09-08 — M9.3: `AccessibilityAuditTest` found and fixed two real bugs (`text-neu-primary-bright` contrast on 8 text-on-surface usages; positive `tabindex` on the login form). 16/16 accessibility tests green in both themes. `d6860aa`.
- 2026-09-08 — M9.4: request-scoped JSON logging, extended `/up` health check (DB + storage disk), `batu:backup`/`batu:restore` proven against a real scratch Postgres database. `03e96c7`.
- 2026-09-08 — M9.6: `docs/DEPLOYMENT.md`, `docs/deploy.sh`, `.env.production.example`. `8d52ebb`.
- 2026-09-08 — M9.7 and `v1.0.0` deliberately left undone: no real client, no real devices, no staging environment exist in this sandbox. Everything achievable without them is complete; what's left needs a human with a phone standing at a real monument.
