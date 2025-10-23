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
    'middleware' => ['api', 'auth:web,api'],
], function ($router) {
    $router->get('projects/{id}/images/filter/annotation-label/{id2}', [
        'uses' => 'ProjectImageAnnotationLabelController@index',
    ]);

    $router->get('volumes/{id}/coordinates', [
        'uses' => 'FileCoordinatesController@index',
    ]);
    
    // GeoTIFF
    $router->post('volumes/{id}/geo-overlays/geotiff', [
        'uses' => 'GeoTiffOverlayController@storeGeoTiff',
    ]);

    // WebMap
    $router->post('volumes/{id}/geo-overlays/webmap', [
        'uses' => 'WebMapOverlayController@store',
    ]);
    
    // GeoOverlays
    $router->get('volumes/{id}/geo-overlays/{layer_type?}', [
        'uses' => 'GeoOverlayController@index',
    ]);

    $router->get('volumes/{id}/geo-overlay', [
        'uses' => 'GeoOverlayController@getOverlay',
    ]);

    $router->resource('geo-overlays', 'GeoOverlayController', [
        'only' => ['destroy'],
        'parameters' => ['geo-overlays' => 'id'],
    ]);
    
    $router->put('volumes/{id}/geo-overlays/{geo_overlay_id}', [
        'uses' => 'GeoOverlayController@updateGeoOverlay',
    ]);

    $router->get('geo-overlays/{id}/file', [
        'uses' => 'GeoOverlayController@showFile',
    ]);
});