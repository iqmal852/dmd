<?php

declare(strict_types=1);

return [
    /*
     | The single admin user, seeded by AdminUserSeeder and idempotent across
     | reseeds (matched by email). There is no registration route — see ADR-006
     | in plan/01-architecture.md. Rotate the password with:
     |     php artisan batu:admin-password
     */
    'admin' => [
        'name' => env('ADMIN_NAME', 'MySpatial Admin'),
        'email' => env('ADMIN_EMAIL', 'admin@myspatial.com.my'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
