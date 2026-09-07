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
        'size' => (int) env('QR_SIZE', 512),
        'margin' => (int) env('QR_MARGIN', 16),
        'error_correction' => env('QR_ERROR_CORRECTION', 'high'), // high: survives a scratched roadside plate
        'format' => env('QR_FORMAT', 'png'), // png | svg
        'logo_path' => env('QR_LOGO_PATH'), // optional MySpatial mark in the centre
    ],

    'map' => [
        'satellite_url' => env('MAP_SATELLITE_URL', 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'),
        'satellite_attr' => env('MAP_SATELLITE_ATTRIBUTION', 'Tiles © Esri'),
        'street_url' => env('MAP_STREET_URL', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'street_attr' => env('MAP_STREET_ATTRIBUTION', '© OpenStreetMap contributors'),
        'default_zoom' => (int) env('MAP_DEFAULT_ZOOM', 17),
        'max_zoom' => (int) env('MAP_MAX_ZOOM', 19),
    ],

    'branding' => [
        'operator' => env('BRAND_OPERATOR', 'MySpatial'),
        'client' => env('BRAND_CLIENT', 'PLUS'),
        'title' => env('BRAND_TITLE', 'Digital Monument Dossier'),
    ],

    'downloads' => [
        'log_enabled' => (bool) env('DOWNLOAD_LOG_ENABLED', true),
        'retention_days' => (int) env('DOWNLOAD_LOG_RETENTION_DAYS', 365),
    ],
];
