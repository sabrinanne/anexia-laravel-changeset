<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | This package was originally meant to support PosgreSQL connections.
    | But feel free to expand the connections to your liking (while sticking
    | to the laravel way of database connections).
    |
    */

    'changeset_sqlite' => [
        'driver' => 'sqlite',
        'database' => env('CHANGESET_DB_DATABASE', env('DB_DATABASE', database_path('database.sqlite'))),
        'prefix' => '',
    ],

    'changeset_mysql' => [
        'driver' => 'mysql',
        'host' => env('CHANGESET_DB_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => env('CHANGESET_DB_PORT', env('DB_PORT', '3306')),
        'database' => env('CHANGESET_DB_DATABASE', env('DB_DATABASE', 'forge')),
        'username' => env('CHANGESET_DB_USERNAME', env('DB_USERNAME', 'forge')),
        'password' => env('CHANGESET_DB_PASSWORD', env('DB_PASSWORD', '')),
        'unix_socket' => env('CHANGESET_DB_SOCKET', env('DB_SOCKET', '')),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ],

    'changeset_pgsql' => [
        'driver' => 'pgsql',
        'host' => env('CHANGESET_DB_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => env('CHANGESET_DB_PORT', env('DB_PORT', '5432')),
        'database' => env('CHANGESET_DB_DATABASE', env('DB_DATABASE', 'forge')),
        'username' => env('CHANGESET_DB_USERNAME', env('DB_USERNAME', 'forge')),
        'password' => env('CHANGESET_DB_PASSWORD', env('DB_PASSWORD', '')),
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
        'sslmode' => 'prefer',
    ],

    'changeset_sqlsrv' => [
        'driver' => 'sqlsrv',
        'host' => env('CHANGESET_DB_HOST', env('DB_HOST', 'localhost')),
        'port' => env('CHANGESET_DB_PORT', env('DB_PORT', '1433')),
        'database' => env('CHANGESET_DB_DATABASE', env('DB_DATABASE', 'forge')),
        'username' => env('CHANGESET_DB_USERNAME', env('DB_USERNAME', 'forge')),
        'password' => env('CHANGESET_DB_PASSWORD', env('DB_PASSWORD', '')),
        'charset' => 'utf8',
        'prefix' => '',
    ],

];
