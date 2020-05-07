<?php

$router->group([
    'middleware' => 'auth',
    'namespace' => 'Views',
], function ($router) {
    $router->get('volumes/{id}/geo', [
        'as'   => 'volume-geo',
        'uses' => 'VolumeController@show',
    ]);

    $router->get('projects/{id}/geo', [
        'as'   => 'project-geo',
        'uses' => 'ProjectController@show',
    ]);
});

$router->group([
    'namespace' => 'Api',
    'prefix' => 'api/v1',
    'middleware' => 'auth:web,api',
], function ($router) {
    $router->get('volumes/{id}/geo-overlays', [
        'uses' => 'VolumeGeoOverlayController@index',
    ]);

    $router->post('volumes/{id}/geo-overlays/plain', [
        'uses' => 'VolumeGeoOverlayController@storePlain',
    ]);

    $router->get('geo-overlays/{id}/file', [
        'uses' => 'GeoOverlayController@showFile',
    ]);

    $router->resource('geo-overlays', 'GeoOverlayController', [
        'only' => ['destroy'],
        'parameters' => ['geo-overlays' => 'id'],
    ]);

    $router->get('projects/{id}/images/filter/annotation-label/{id2}', [
        'uses' => 'ProjectImageAnnotationLabelController@index',
    ]);
    $router->group(['namespace' => 'Geojson', 'prefix' => 'geojson'], function($router){
      $router->get('images', ['uses' => 'ImagesController@index']);
    });
});
