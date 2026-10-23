<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded Accounts
    |--------------------------------------------------------------------------
    |
    | Credentials for the accounts created by the database seeder. Development
    | defaults to "password"; production must provide real values in the env.
    |
    */

    'admin' => [
        'name' => env('SEED_ADMIN_NAME', 'Admin'),
        'email' => env('SEED_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],

    'staff' => [
        'name' => env('SEED_STAFF_NAME', 'Field Staff'),
        'email' => env('SEED_STAFF_EMAIL', 'staff@example.com'),
        'password' => env('SEED_STAFF_PASSWORD'),
    ],

    'viewer' => [
        'name' => env('SEED_VIEWER_NAME', 'Guest Viewer'),
        'email' => env('SEED_VIEWER_EMAIL', 'viewer@example.com'),
        'password' => env('SEED_VIEWER_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo Data
    |--------------------------------------------------------------------------
    |
    | When enabled, the seeder fills twelve months of sample orchard data so the
    | dashboard and reports have something to show.
    |
    */

    'demo_data' => (bool) env('SEED_DEMO_DATA', true),

];
