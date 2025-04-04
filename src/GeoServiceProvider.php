<?php

namespace Biigle\Modules\Geo;

use Biigle\Services\Modules;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class GeoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @param  \Biigle\Services\Modules  $modules
     * @param  \Illuminate\Routing\Router  $router
     *
     * @return void
     */
    public function boot(Modules $modules, Router $router)
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'geo');
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');

        $this->publishes([
            __DIR__.'/public' => public_path('vendor/geo'),
        ], 'public');

        $this->publishes([
            __DIR__.'/config/geo.php' => config_path('geo.php'),
        ], 'config');

        $router->group([
            'namespace' => 'Biigle\Modules\Geo\Http\Controllers',
            'middleware' => 'web',
        ], function ($router) {
            require __DIR__.'/routes.php';
        });

        $modules->register('geo', [
            'viewMixins' => [
                'imagesIndex',
                'manualTutorial',
                'volumesSidebar',
                'volumesScripts',
                'projectsShowTabs',
                'volumesStyles'
            ],
            'apidoc' => [__DIR__.'/Http/Controllers/Api/'],
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.geo.publish', function ($app) {
            return new \Biigle\Modules\Geo\Console\Commands\Publish();
        });
        $this->commands('command.geo.publish');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.geo.publish',
        ];
    }
}
