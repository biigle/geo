<?php

$router->group([
        'middleware' => 'auth',
        'namespace' => 'Views',
    ], function ($router) {

    $router->get('volumes/{id}/geo', [
        'as'   => 'volume-geo',
        'uses' => 'VolumeController@show',
    ]);
});

$router->group([
    'namespace' => 'Api',
    'prefix' => 'api/v1',
    'middleware' => 'auth.api',
], function ($router) {
    $router->get('volumes/{id}/geo-overlays', [
        'uses' => 'VolumeGeoOverlayController@index',
    ]);
    $router->get('geo-overlays/{id}/file', [
        'uses' => 'GeoOverlayController@showFile',
    ]);

    $router->resource('geo-overlays', 'GeoOverlayController', [
        'only' => ['destroy'],
        'parameters' => ['geo-overlays' => 'id'],
    ]);
});
