<?php

$router->group([
        'middleware' => 'auth',
        'namespace' => 'Views',
    ], function ($router) {

    $router->get('transects/{id}/geo', [
        'as'   => 'transect-geo',
        'uses' => 'TransectController@show',
    ]);
});
