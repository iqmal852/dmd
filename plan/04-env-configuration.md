# 04 — Environment Configuration

Two hard requirements from the brief live here:

1. **"The URL domain must be configurable on `.env` file."**
2. **"Make the QR data configurable — public or password gated based on `.env`."**

Both are satisfied by `config/dossier.php`, read through `config()` only.

---

## 1. The golden rule

> `env()` is called **only** inside files in `config/`. Nowhere else. Ever.

Once `php artisan config:cache` runs in production, `env()` returns `null` outside config
files. A single stray `env('DOSSIER_BASE_URL')` in a service class produces QR codes
pointing at `http:///d/01J...` in production and nowhere else — a bug that ships silently.
Larastan plus a Pint rule enforce this; a test in Phase 00 (M0.6) greps the app directory
for `env(` and fails if it finds one.

---

## 2. `config/dossier.php`

```php
<?php

declare(strict_types=1);

use App\Enums\AccessMode;

return [
    /*
     | The absolute base URL baked into every generated QR code.
     | Deliberately separate from APP_URL: QR plates are bolted to concrete and are
     | effectively permanent, so the printed domain must be pinnable independently of
     | whatever host the app happens to answer on (staging, preview, internal LB).
     */
    'base_url' => rtrim((string) env('DOSSIER_BASE_URL', env('APP_URL', 'http://localhost')), '/'),

    // URL segment before the station identifier: /d/{public_id}
    'route_prefix' => trim((string) env('DOSSIER_ROUTE_PREFIX', 'd'), '/'),

    /*
     | Access mode for the whole deployment.
     |   public   → anyone with the link sees the dossier
     |   password → a password gate is shown before any dossier content
     | A per-station password (stations.access_password) always forces the gate for
     | that station regardless of this setting.
     */
    'access_mode' => AccessMode::tryFrom((string) env('DOSSIER_ACCESS_MODE', 'public'))
        ?? AccessMode::Public,

    // Deployment-wide password used when access_mode=password and the station has no override.
    'access_password' => env('DOSSIER_ACCESS_PASSWORD'),

    // How long an unlock lasts, in minutes. 720 = 12h ≈ one field working day.
    'unlock_ttl' => (int) env('DOSSIER_UNLOCK_TTL', 720),

    // Failed unlock attempts per minute, per IP, per station.
    'unlock_throttle' => (int) env('DOSSIER_UNLOCK_THROTTLE', 5),

    'qr' => [
        'size'             => (int) env('QR_SIZE', 512),
        'margin'           => (int) env('QR_MARGIN', 16),
        'error_correction' => env('QR_ERROR_CORRECTION', 'high'), // high: survives a scratched roadside plate
        'format'           => env('QR_FORMAT', 'png'),            // png | svg
        'logo_path'        => env('QR_LOGO_PATH'),                // optional MySpatial mark in the centre
    ],

    'map' => [
        'satellite_url'  => env('MAP_SATELLITE_URL', 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'),
        'satellite_attr' => env('MAP_SATELLITE_ATTRIBUTION', 'Tiles © Esri'),
        'street_url'     => env('MAP_STREET_URL', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'street_attr'    => env('MAP_STREET_ATTRIBUTION', '© OpenStreetMap contributors'),
        'default_zoom'   => (int) env('MAP_DEFAULT_ZOOM', 17),
        'max_zoom'       => (int) env('MAP_MAX_ZOOM', 19),
    ],

    'branding' => [
        'operator' => env('BRAND_OPERATOR', 'MySpatial'),
        'client'   => env('BRAND_CLIENT', 'PLUS'),
        'title'    => env('BRAND_TITLE', 'Digital Monument Dossier'),
    ],

    'downloads' => [
        'log_enabled'    => (bool) env('DOWNLOAD_LOG_ENABLED', true),
        'retention_days' => (int) env('DOWNLOAD_LOG_RETENTION_DAYS', 365),
    ],
];
```

---

## 3. `.env.example` (committed)

```dotenv
APP_NAME="MySpatial Digital Monument Dossier"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Kuala_Lumpur
APP_LOCALE=en

# ── Database (PostgreSQL only) ─────────────────────────────────────
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=batu
DB_USERNAME=batu
DB_PASSWORD=secret

# ── Single admin user (seeded by AdminUserSeeder) ──────────────────
ADMIN_NAME="MySpatial Admin"
ADMIN_EMAIL=admin@myspatial.com.my
ADMIN_PASSWORD=change-me-before-deploy

# ── Dossier: the QR domain and access model ────────────────────────
# The domain printed into every QR code. Change this and regenerate the plates.
DOSSIER_BASE_URL=http://localhost:8000
DOSSIER_ROUTE_PREFIX=d

# public | password
DOSSIER_ACCESS_MODE=public
DOSSIER_ACCESS_PASSWORD=
DOSSIER_UNLOCK_TTL=720
DOSSIER_UNLOCK_THROTTLE=5

# ── QR generation ──────────────────────────────────────────────────
QR_SIZE=512
QR_MARGIN=16
QR_ERROR_CORRECTION=high
QR_FORMAT=png
QR_LOGO_PATH=

# ── Map tiles ──────────────────────────────────────────────────────
MAP_DEFAULT_ZOOM=17
MAP_MAX_ZOOM=19

# ── Branding ───────────────────────────────────────────────────────
BRAND_OPERATOR=MySpatial
BRAND_CLIENT=PLUS
BRAND_TITLE="Digital Monument Dossier"

# ── Storage / media ────────────────────────────────────────────────
FILESYSTEM_DISK=local
# For production S3-compatible storage:
# FILESYSTEM_DISK=s3
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=ap-southeast-1
# AWS_BUCKET=
# AWS_USE_PATH_STYLE_ENDPOINT=false

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

---

## 4. Production overrides

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gcp.myspatial.com.my
DOSSIER_BASE_URL=https://gcp.myspatial.com.my

DOSSIER_ACCESS_MODE=password
DOSSIER_ACCESS_PASSWORD=<32+ chars, from the secret manager, never committed>

SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
FILESYSTEM_DISK=s3
QUEUE_CONNECTION=redis
CACHE_STORE=redis
LOG_CHANNEL=stack
LOG_LEVEL=warning
```

---

## 5. The QR domain contract

`App\Services\QrUrlBuilder` is the **only** place a dossier URL is constructed:

```php
public function forStation(Station $station): string
{
    return sprintf(
        '%s/%s/%s',
        config('dossier.base_url'),
        config('dossier.route_prefix'),
        $station->public_id,
    );
}
```

Enforced by tests (Phase 08, M8.4):

- Setting `DOSSIER_BASE_URL=https://qr.example.test` makes the generated payload start
  with exactly that, even when the request arrives on a different host.
- Setting `DOSSIER_ROUTE_PREFIX=gcp` produces `https://qr.example.test/gcp/{public_id}`
  **and** the application still routes that prefix correctly (the route definition reads
  the same config value).
- A trailing slash in `DOSSIER_BASE_URL` never produces a double slash.

**Operational warning to put in the deploy runbook:** changing `DOSSIER_BASE_URL` or
`DOSSIER_ROUTE_PREFIX` after plates are installed **invalidates every QR code already in
the field**. Keep a permanent redirect from the old prefix, or budget a re-plating trip.

---

## 6. Access-mode behaviour matrix

| `DOSSIER_ACCESS_MODE` | `stations.access_password` | Result |
|---|---|---|
| `public` | `null` | Dossier opens immediately |
| `public` | set | Gate shown; station's own password required |
| `password` | `null` | Gate shown; `DOSSIER_ACCESS_PASSWORD` required |
| `password` | set | Gate shown; station's own password required (override wins) |

Additional rule: if `DOSSIER_ACCESS_MODE=password` and `DOSSIER_ACCESS_PASSWORD` is empty
while a station has no override, the app **fails closed** — it returns 503 with a clear
operator message rather than silently serving the dossier publicly. A misconfiguration
must never quietly become a data leak. Covered by a test in Phase 03 (M3.4).
