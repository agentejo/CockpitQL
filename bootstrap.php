<?php

spl_autoload_register(function($class){
    $class_path = __DIR__.'/lib/'.str_replace('\\', '/', $class).'.php';
    if(file_exists($class_path)) include_once($class_path);
});

// REST
if (COCKPIT_API_REQUEST) {

    $app->on('cockpit.rest.init', function($routes) use($app) {

        $routes['graphql'] = 'CockpitQL\\Controller\\RestApi';
    });
}
