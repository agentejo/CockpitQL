<?php

spl_autoload_register(function($class){
    $class_path = __DIR__.'/lib/'.str_replace('\\', '/', $class).'.php';
    if(file_exists($class_path)) include_once($class_path);
});

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

// REST
if (COCKPIT_API_REQUEST) {

    include(__DIR__.'/api.php');
}
