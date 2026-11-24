<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Visitor sandboxes
    |--------------------------------------------------------------------------
    |
    | Every visitor works on a private copy of a pre-seeded SQLite template.
    | See App\Support\Sandbox\SandboxManager and App\Http\Middleware\DemoSandbox.
    |
    */

    'sandbox' => [
        'enabled' => (bool) env('DEMO_SANDBOX_ENABLED', true),
        'connection' => 'sandbox',
        'path' => env('DEMO_SANDBOX_PATH', storage_path('demo/sandboxes')),
        'template' => env('DEMO_TEMPLATE_PATH', storage_path('demo/template.sqlite')),
        'cookie' => 'demo_sandbox',
        'idle_minutes' => 180,
        'max_age_minutes' => 1440,
        // Worst case on disk is max_sandboxes × max_bytes, and it must fit the
        // sandbox volume (the 512 MB tmpfs in docker-compose.yml, whose pages
        // also count toward the app container's memory limit).
        'max_sandboxes' => 200,
        'max_bytes' => 2 * 1024 * 1024,
    ],

    /*
    | One-click entry: role => username of the seeded user it logs in as.
    */
    'personas' => [
        'admin' => 'capybara',
        'sales' => 'narwhal',
    ],

    'source_url' => env('DEMO_SOURCE_URL'),
    'portfolio_url' => env('DEMO_PORTFOLIO_URL'),

    /*
    |--------------------------------------------------------------------------
    | The fictional distributor
    |--------------------------------------------------------------------------
    |
    | Printed on quotations, delivery orders and invoices. Every detail here is
    | invented; the `.example` domain is reserved and never resolves.
    |
    */

    'company' => [
        'name' => 'PT Kinetra Conveyor Supply',
        'short_name' => 'Kinetra',
        'address' => ['Demo Industrial Park, Block A-00', 'Jakarta 00000, Indonesia'],
        'phone' => '+62 21 0000 1234',
        'email' => 'sales@kinetra.example',
        'tax_id' => '00.000.000.0-000.000',
        'bank' => 'Bank Contoh · 000-000-0000 · a.n. PT Kinetra Conveyor Supply',
    ],

    'ppn_rate' => 11,

    'max_quotation_lines' => 40,

    'delivery_points' => [
        'North Jakarta', 'East Jakarta', 'West Jakarta', 'South Jakarta', 'Bekasi Regency', 'Bekasi City',
        'Karawang', 'Purwakarta', 'Bogor Regency', 'Tangerang Regency', 'Tangerang City', 'Serang',
        'Cilegon', 'Bandung', 'Semarang', 'Yogyakarta', 'Sleman', 'Surabaya', 'Sidoarjo', 'Gresik',
        'Pasuruan', 'Malang', 'Medan', 'Batam', 'Balikpapan', 'Makassar',
    ],

];
