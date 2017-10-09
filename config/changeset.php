<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel Changeset
    |--------------------------------------------------------------------------
    |
    | This section defines the variables needed for the
    | anexia-laravel-changeset project. This config can be used without change
    | if the application's database is supposed to contain the changeset data.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all changeset database work. Of
    | course you may use many connections at once using the Database library.
    |
    */

    'changeset_default' => env('CHANGESET_DB_CONNECTION', env('DB_CONNECTION', 'changeset_mysql')),

];
