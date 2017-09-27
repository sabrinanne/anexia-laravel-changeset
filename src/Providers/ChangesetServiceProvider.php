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
    }
}