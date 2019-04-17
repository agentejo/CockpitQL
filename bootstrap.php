<?php

spl_autoload_register(function($class){
    $class_path = __DIR__.'/lib/'.str_replace('\\', '/', $class).'.php';
    if(file_exists($class_path)) include_once($class_path);
});


$this->module('cockpitql')->extend([

    'query' => function($query = '{}', $variables = null) {
        return \CockpitQL\Query::process($query, $variables);
    }
]);

// REST
if (COCKPIT_API_REQUEST) {

    $app->on('cockpit.rest.init', function($routes) use($app) {

        $routes['graphql'] = 'CockpitQL\\Controller\\RestApi';
    });

    // allow access to public graphql query
    $app->on('cockpit.api.authenticate', function($data) {
        
        if ($data['user'] || $data['resource'] != 'graphql') return;

        if ($this->retrieve('config/cockpitql/publicAccess', false)) {
            $data['authenticated'] = true;
            $data['user'] = ['_id' => null, 'group' => 'public'];
        }
    });
}
