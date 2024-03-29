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
});