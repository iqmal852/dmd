# Deployment Runbook

See plan/phases/phase-09-hardening-release.md M9.6. This document plus
`docs/deploy.sh` and `.env.production.example` should be everything a
person who did not write this app needs to deploy, back it up, restore it,
and roll it back.

---

## 1. Server requirements

| | |
|---|---|
| PHP | 8.3+ (developed and tested on 8.5) — extensions: `pdo_pgsql`, `gd` or `imagick`, `bcmath`, `intl`, `zip` |
| Database | PostgreSQL 17 (required — see `plan/01-architecture.md` ADR-003; the case-insensitive unique index on `stations.code` and the JSON columns used by the media library are Postgres-specific) |
| Web server | Nginx (or any server that can proxy PHP-FPM and serve `public/` as the document root) |
| Node | 22, for `npm run build` only — not needed at runtime, only at deploy/build time |
| Redis | **Not required.** Cache, session, and queue all deliberately use the `database` driver (`App\Services\StationCache` exists specifically because the database cache store doesn't support tags) — don't provision Redis unless a future feature needs it |
| Binaries on `PATH` | `pg_dump`, `psql`, `gzip`, `gunzip` — used by `batu:backup` / `batu:restore` (M9.4), not by the app itself at request time |

---

## 2. First deploy

```bash
git clone <repo-url> /var/www/batu/releases/initial
cd /var/www/batu/releases/initial

composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

cp .env.production.example .env      # then fill in every SECRET value — see the file's comments
php artisan key:generate

php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan storage:link

php artisan optimize                 # config:cache + route:cache + view:cache + event:cache
```

Then set up the directory layout `docs/deploy.sh` expects for every deploy after this one:

```bash
mkdir -p /var/www/batu/{releases,shared}
mv /var/www/batu/releases/initial/storage /var/www/batu/shared/storage
mv /var/www/batu/releases/initial/.env /var/www/batu/shared/.env
ln -sfn /var/www/batu/shared/storage /var/www/batu/releases/initial/storage
ln -sfn /var/www/batu/shared/.env /var/www/batu/releases/initial/.env
ln -sfn /var/www/batu/releases/initial /var/www/batu/current
```

Point Nginx's document root at `/var/www/batu/current/public` and PHP-FPM's
working directory the same way — never at a specific `releases/<timestamp>`
directory, so the symlink swap in every later deploy is instant and atomic.

**Immediately rotate `ADMIN_PASSWORD`** — it only exists in `.env` to seed
the one admin row once. See §6.

---

## 3. Subsequent deploys — zero-downtime

```bash
docs/deploy.sh /var/www/batu main
```

What it does (full detail in the script's own comments):

1. Clones the given ref into a brand-new `releases/<timestamp>` directory —
   the currently-serving release is untouched the whole time.
2. Symlinks in the shared `.env` and `storage/` (uploaded media, logs).
3. `composer install --no-dev`, `npm ci && npm run build`.
4. Primes `config:cache` / `route:cache` / `view:cache` / `event:cache` on
   the **new** release, before it takes any traffic.
5. Runs `migrate --force` against the shared database.
6. Swaps the `current` symlink — this is the only instant at which traffic
   sees the new code.
7. `queue:restart` so queue workers pick up the new release on their next
   job.
8. Prunes old release directories, keeping the last 5.

Because the migration in step 5 runs while the *old* release is still
serving traffic (right up until step 6), every migration must be additive
or otherwise backward-compatible with the release still live — the same
constraint any zero-downtime deploy imposes. Don't drop or rename a column
a currently-running release still reads in the same deploy that removes the
code that used it; do it as two deploys.

---

## 4. `.env.production` template

`.env.production.example` (repo root) lists every key, with inline comments
marking which are secrets (`APP_KEY`, `DB_PASSWORD`, `ADMIN_PASSWORD`,
`DOSSIER_ACCESS_PASSWORD`) versus plain configuration. Copy it to the
server's `shared/.env` (that exact filename is gitignored — never commit
real secrets) and fill in the blanks.

---

## 5. ⚠ The QR domain warning

`DOSSIER_BASE_URL` and `DOSSIER_ROUTE_PREFIX` are baked into the literal
bytes of every QR code already printed onto a physical concrete monument
plate. **Changing either invalidates every plate installed in the field.**

If a change is truly unavoidable (domain migration, rebrand):

1. Keep serving the **old** domain/prefix indefinitely with a permanent
   (301) redirect to the new one — add this at the web server level
   (Nginx `return 301`), not inside the Laravel app, so it survives even if
   the app itself is later decommissioned.
2. Never remove that redirect. There is no inventory of which plates are
   still in the field pointing at the old URL.
3. Regenerate and reprint plates for stations as budget/schedule allows,
   but the redirect is what makes an unreprinted plate keep working.

---

## 6. Rotating the admin password

There is deliberately no self-service password reset (ADR-006 — no email
infrastructure for a single seeded admin). The only way to change it:

```bash
php artisan batu:admin-password
```

Prompts for a new password (min 8 characters) and a confirmation. Do this
immediately after first deploy, since `ADMIN_PASSWORD` in `.env` was only
ever a seed value.

---

## 7. Flipping access mode

```bash
# .env
DOSSIER_ACCESS_MODE=password
DOSSIER_ACCESS_PASSWORD=<the new deployment-wide password>
```

```bash
php artisan config:cache
```

Existing unlock sessions (cookie-based, `DOSSIER_UNLOCK_TTL` minutes) are
**not** invalidated by this change — a field crew already past the gate
keeps their access until that session naturally expires. A per-station
password (`stations.access_password`) always overrides this and forces the
gate for that one station regardless of the deployment-wide setting.

---

## 8. Backup and restore

### Backup

```bash
php artisan batu:backup
```

`pg_dump`s the configured database connection, gzips it, and stores it on
`config('backup.disk')` (`BACKUP_DISK` in `.env`) under `config('backup.path')`,
pruning anything older than `BACKUP_KEEP_DAYS`. Scheduled daily already
(`routes/console.php`) — this only fires if the server's cron actually
calls `php artisan schedule:run` every minute:

```cron
* * * * * cd /var/www/batu/current && php artisan schedule:run >> /dev/null 2>&1
```

**`BACKUP_DISK=local` is a placeholder, not a production configuration.**
A backup on the same disk as the database it protects against survives
nothing that actually takes the server down. Before going live, wire up an
off-site disk (S3 or equivalent) in `config/filesystems.php` — this needs
`league/flysystem-aws-s3-v3`, a new Composer dependency, approved before
install per this repo's CLAUDE.md.

### Restore — the tested procedure

An untested backup is not a backup. This is the drill, and it's exactly
what `tests/Feature/DatabaseBackupRestoreTest.php` automates against a
scratch database on every test run:

```bash
# 1. List available backups
php artisan tinker --execute='dd(Illuminate\Support\Facades\Storage::disk(config("backup.disk"))->files(config("backup.path")));'

# 2. Restore into a SCRATCH connection first, never straight into production.
#    Add a temporary connection to config/database.php (or set these as env
#    overrides), e.g. `restore_scratch` pointing at a throwaway database,
#    then:
php artisan batu:restore backup-2026-09-08-030000.sql.gz --connection=restore_scratch

# 3. Verify the scratch database looks right — row counts, spot-check a
#    known station — before trusting the backup at all.

# 4. Only once verified, restore into the real connection. This requires
#    --force precisely because it is destructive and irreversible:
php artisan batu:restore backup-2026-09-08-030000.sql.gz --force
```

Restoring into the app's default connection replaces its entire contents
with the backup's — there is no merge. Take the application into
maintenance mode first (`php artisan down`) so nothing writes to the
database mid-restore, and bring it back up (`php artisan up`) once
`batu:restore` reports success.

---

## 9. Rollback procedure

Because `docs/deploy.sh` keeps the last 5 release directories:

```bash
ls /var/www/batu/releases              # find the last-known-good timestamp
ln -sfn /var/www/batu/releases/<timestamp> /var/www/batu/current
php artisan queue:restart
```

That's the whole rollback for a code-only issue — the symlink swap is
instant, same as a forward deploy.

If the bad deploy's migration needs to be undone too (only relevant if it
wasn't additive/backward-compatible, which it should have been — see §3):

```bash
cd /var/www/batu/current
php artisan migrate:rollback --step=1 --force
```

Roll back the symlink **before** rolling back the migration if the old
release's code can't run against the new schema — otherwise the still-live
old release will error against a schema it doesn't recognize for however
long the rollback takes.

If a migration rollback itself is unsafe (data loss), restore from the most
recent backup instead (§8) and accept the data since that backup as lost —
this is why a *tested*, *frequent* (daily) backup matters more than a
theoretically-reversible migration.

---

## Observability quick reference (M9.4)

- `GET /up` — extended health check; verifies the database connection and
  a real write/read/delete round trip against the `local` storage disk
  (where every station's photos/panoramas/documents live). Returns 500 if
  either fails — point an uptime monitor at this.
- `LOG_CHANNEL=json` — one JSON object per log line, `request_id` present
  on every entry (via `Illuminate\Support\Facades\Context`, set by
  `App\Http\Middleware\AssignRequestId`), for point log-aggregator
  ingestion without a custom parser.
- Error tracking (Sentry or equivalent) is **not wired up** — it needs a
  new Composer dependency, which needs approval before install. See the
  commented block at the bottom of `.env.production.example` for the exact
  steps once approved.
- Queue workers: `QUEUE_CONNECTION=database` — media conversions
  (Spatie Media Library) and download-log writes both go through the
  queue. Run under Supervisor or systemd with restart-on-failure:

  ```ini
  # /etc/systemd/system/batu-queue.service
  [Unit]
  Description=Batu queue worker
  After=network.target postgresql.service

  [Service]
  User=www-data
  WorkingDirectory=/var/www/batu/current
  ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
  Restart=always
  RestartSec=5

  [Install]
  WantedBy=multi-user.target
  ```

  `--max-time=3600` makes the worker exit and get restarted by systemd
  hourly, which is what actually picks up a new release's code (paired
  with `queue:restart` in `docs/deploy.sh`, which signals a graceful
  restart after the current job finishes rather than waiting the full
  hour).
