<?php

namespace Dias\Modules\Geo;

use Dias\Services\Modules;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class GeoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @param  \Dias\Services\Modules  $modules
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
            'namespace' => 'Dias\Modules\Geo\Http\Controllers',
            'middleware' => 'web',
        ], function ($router) {
            require __DIR__.'/routes.php';
        });

        $modules->addMixin('geo', 'imagesIndex');
        $modules->addMixin('geo', 'manualTutorial');
        $modules->addMixin('geo', 'transectsMenubar');
        $modules->addMixin('geo', 'transectsScripts');
        $modules->addMixin('geo', 'transectsFilters');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.geo.publish', function ($app) {
            return new \Dias\Modules\Geo\Console\Commands\Publish();
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
