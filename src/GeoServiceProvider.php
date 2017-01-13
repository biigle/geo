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

        $this->publishes([
            __DIR__.'/public/assets' => public_path('vendor/geo'),
        ], 'public');

        $router->group([
            'namespace' => 'Biigle\Modules\Geo\Http\Controllers',
            'middleware' => 'web',
        ], function ($router) {
            require __DIR__.'/routes.php';
        });

        $modules->addMixin('geo', 'imagesIndex');
        $modules->addMixin('geo', 'manualTutorial');
        $modules->addMixin('geo', 'volumesMenubar');
        $modules->addMixin('geo', 'volumesScripts');
        $modules->addMixin('geo', 'volumesFilters');
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
