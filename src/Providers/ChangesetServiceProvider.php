<?php
namespace Anexia\Monitoring\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class MonitoringServiceProvider
 * @package Anexia\Monitoring\Providers
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
        $this->publishes([
            __DIR__ . '/../../database/migrations/__create_change_records_table.php' => $this->app['path.database'] . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '__create_change_records_table.php',
        ], 'anexia-change-set');
        $this->publishes([
            __DIR__ . '/../../database/migrations/__create_change_sets_table.php' => $this->app['path.database'] . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '__create_change_sets_table.php',
        ], 'anexia-change-record');
        $this->publishes([
            __DIR__ . '/../../database/migrations/_create_change_set_foreign_table.php' => $this->app['path.database'] . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '_create_change_set_foreign_table.php',
        ], 'anexia-change-set-foreign-keys');
    }

    /**
     * Register the application services
     *
     * @return void
     */
    public function register()
    {
//        include __DIR__ . '/../routes.php';
//        $this->app->make('Anexia\Monitoring\Controllers\VersionMonitoringController');
//        $this->app->make('Anexia\Monitoring\Controllers\UpMonitoringController');

//        $this->mergeConfigFrom(
//            __DIR__ . '/../../config/monitoring.php', 'monitoring'
//        );
    }
}