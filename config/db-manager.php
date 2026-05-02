<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable / Disable
    |--------------------------------------------------------------------------
    | Quickly toggle the package on or off. When disabled every route returns 404.
    */
    'enabled' => env('DB_MANAGER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | URL Prefix
    |--------------------------------------------------------------------------
    | The URI segment that prefixes all DB Manager routes.
    | Accessible at: APP_URL/{prefix}
    */
    'prefix' => env('DB_MANAGER_PREFIX', 'db-manager'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    | Applied to every DB Manager route. By default the user must be logged in
    | via Laravel's standard web + auth middleware stack.
    | Add any additional middleware here (e.g. 'role:admin').
    */
    'middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Connection Mode
    |--------------------------------------------------------------------------
    | Controls how DB Manager connects to MySQL:
    |
    |  'laravel'  — uses the app's configured DB connection automatically.
    |               Only the app's own database is accessible.
    |
    |  'all'      — same credentials as 'laravel' but all databases on the
    |               MySQL server are listed and accessible.
    |
    |  'custom'   — presents a login form so any MySQL user/host can be used.
    |               Credentials are stored in the session only.
    */
    'connection_mode' => env('DB_MANAGER_CONNECTION_MODE', 'laravel'),

    /*
    |--------------------------------------------------------------------------
    | Laravel Connection Name
    |--------------------------------------------------------------------------
    | Which Laravel database connection to use when connection_mode is
    | 'laravel' or 'all'. Leave null to use the default connection.
    */
    'connection' => env('DB_MANAGER_CONNECTION', null),

];
