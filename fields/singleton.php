<?php

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
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

$mutations['fields']['updateSingletonData'] = [
    'args' => [
        'name' => Type::nonNull(Type::string()),
        'data' => Type::nonNull(JsonType::instance()),
        'options' => JsonType::instance(),
    ],
    'type' => new ObjectType([
        'name' => 'updateSingletonDataOutput',
        'fields' => [
            'data' => ['type' => JsonType::instance()]
        ]
    ]),
    'resolve' => function ($root, $args) use($app) {
        
        $name = $args['name'];
        $data = $args['data'];
        $options = $args['options'] ?? [];

        if (!$app->module('singletons')->exists($name)) {
            $app->stop(['error'=> "Singleton <{$name}> does not exist!"], 404);
        }

        $singleton = $app->module('singletons')->singleton($name);
        $user = $app->module('cockpit')->getUser();

        if ($user) {

            if (!$app->module('singletons')->hasaccess($singleton['name'], 'form')) {
                $app->stop(['error'=> 'Unauthorized'], 401);
            }
            
        } else {
            $app->stop(['error'=> 'Unauthorized'], 401);
        }

        $data = $app->module('singletons')->saveData($singleton['name'], $data, $options);

        return compact('data');
    },
];
