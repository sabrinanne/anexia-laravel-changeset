<?php
namespace Anexia\Changeset\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class ChangesetServiceProvider
 * @package Anexia\Changeset\Providers
 */
class ChangesetServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // add additional migration files
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // add additional config files
        $this->publishes([
            __DIR__ . '/../../config/changeset.php' => $this->app['path.config'] . DIRECTORY_SEPARATOR . 'changeset.php',
        ], 'anexia-changeset');
    }

    /**
     * Register the application services
     *
     * @return void
     */
    public function register()
    {
        // add the changeset default database defined in the changeset config to to the applications 'database' configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/changeset.php', 'database'
        );
        // add the database connections defined in the connections config to to the applications 'database' configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/connections.php', 'database.connections'
        );
    }
}