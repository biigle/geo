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
