<?php

use GraphQL\Type\Definition\Type;
use CockpitQL\Types\JsonType;

$queries['fields']['singleton'] =  [
    
    'type' => JsonType::instance(),
    'args' => [
        'name' => Type::nonNull(Type::string()),
        'options' => JsonType::instance(),
    ],
    'resolve' => function ($root, $args) use($app) {

        $name = $args['name'];

        if (!$app->module('singletons')->exists($name)) {
            return '{"error": "Singleton not found"}';
        }

        if ($app->module('cockpit')->getUser()) {
            if (!$app->module('singletons')->hasaccess($name, 'data')) {
                return '{"error": "Unauthorized"}';
            }
        }

        $options = [];

        if (isset($args['options']) && $args['options']) {
            $options = $args['options'];
        }

        return json_encode(cockpit('singletons')->getData($name, $options));
    }
];
