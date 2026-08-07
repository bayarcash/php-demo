<?php

/**
 * Copy this file to config.php and fill it in. config.php is gitignored.
 *
 *     cp config.sample.php config.php
 */

return [
    // Public URL of the public/ directory, with a trailing slash. Bayarcash
    // sends callbacks here, so it must be reachable from the internet -- use
    // a tunnel (ngrok, Expose, Herd share) when developing locally.
    //
    // Leave it out entirely and the demo guesses from the current request,
    // which is fine on localhost but not in production.
    'base_url' => 'https://your-domain.com/',

    // Which set of credentials below to use: 'sandbox' or 'production'.
    'environment' => 'sandbox',

    'environments' => [
        'sandbox' => [
            'portal_key'   => 'your_sandbox_portal_key',
            'bearer_token' => 'your_sandbox_bearer_token',
            'secret_key'   => 'your_sandbox_api_secret_key',
        ],

        'production' => [
            'portal_key'   => 'your_production_portal_key',
            'bearer_token' => 'your_production_bearer_token',
            'secret_key'   => 'your_production_api_secret_key',
        ],

        // A custom host, e.g. an internal API. Adding a 'base_uri' points the
        // SDK there instead of the standard sandbox/production URLs.
        //
        // 'dev' => [
        //     'portal_key'   => '',
        //     'bearer_token' => '',
        //     'secret_key'   => '',
        //     'base_uri'     => 'https://your-internal-host/api/v2/',
        // ],
    ],

    // Timestamps are written by PHP in this zone, so they do not depend on
    // whether the database defaults to UTC (SQLite) or server-local (MySQL).
    'timezone' => 'Asia/Kuala_Lumpur',

    // v3 is current and required by the transaction lookup methods.
    // v2 still works for creating payments and verifying callbacks.
    'api_version' => 'v3',

    // SQLite needs no setup: the file is created on first run.
    'db' => [
        'driver' => 'sqlite',
        // 'path' => __DIR__ . '/storage/demo.sqlite',
    ],

    // Switch to MySQL by replacing the block above with this one:
    //
    // 'db' => [
    //     'driver'   => 'mysql',
    //     'host'     => 'localhost',
    //     'dbname'   => 'bayarcash_demo',
    //     'username' => 'root',
    //     'password' => '',
    // ],

];
