<?php

spl_autoload_register(function($class){
    $class_path = __DIR__.'/lib/'.str_replace('\\', '/', $class).'.php';
    if(file_exists($class_path)) include_once($class_path);
});

use GraphQL\GraphQL;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

// REST
if (COCKPIT_API_REQUEST) {

    $app->on('cockpit.rest.init', function($routes) use($app) {

        $routes['graphql'] = function() use($app) {

            $config = new ArrayObject([
                'name' => 'Query',
                'fields' => [
                    'region' => [
                        'type' => Type::string(),
                        'args' => [
                            'name' => Type::nonNull(Type::string()),
                            'params' => Type::string(),
                            'render' => [
                                'type' => Type::boolean(),
                                'defaultValue' => false
                            ]
                        ],
                        'resolve' => function ($root, $args) use($app) {
                            
                            $name = $args['name'];

                            if (!$app->module('regions')->exists($name)) {
                                return '{"error": "Region not found"}';
                            }

                            if ($app->module('cockpit')->getUser()) {
                                if (!$app->module('regions')->hasaccess($name, 'form')) {
                                    return '{"error": "Unauthorized"}';
                                }
                            }

                            $params = [];

                            if (isset($args['params']) && $args['params']) {
                                parse_str($args['params'], $params);
                            }

                            if ($args['render']) {
                                return cockpit('regions')->render($name, $params);
                            } else {
                                return json_encode(cockpit('regions')->region($name));
                            }

                        }
                    ],
                    'collection' => [
                        'type' => Type::string(),
                        'args' => [
                            'name'  => Type::nonNull(Type::string()),
                            '_id'   => Type::string(),
                            'limit' => Type::int(),
                            'skip'  => Type::int(),
                            'sort'  => Type::string(),
                            'lang'  => Type::string(),
                            'populate'   => ['type' => Type::int(), 'defaultValue' => 0],
                            'projection' => ['type' => Type::string(), 'defaultValue' => ''],
                            'filter'   => ['type' => Type::string(), 'defaultValue' => '']
                        ],
                        'resolve' => function ($root, $args) use($app) {

                            $collection = $args['name'];

                            if (!$app->module('collections')->exists($collection)) {
                                return '{"error": "Collection not found"}';
                            }

                            $collection = $app->module('collections')->collection($collection);
                            $user = $app->module('cockpit')->getUser();
                            
                            if ($user) {
                                
                                if (!$app->module('collections')->hasaccess($collection['name'], 'entries_view')) {
                                    return '{"error": "Unauthorized"}';
                                }
                            }

                            $options  = [];
                            $filter   = [];
                            $populate = $args['populate'];

                            if (isset($args['lang']) && $args['lang']) {
                                $filter['lang'] = $args['lang'];
                            }

                            if ($user) {
                                $filter['user'] = $user;
                            }

                            if (isset($args['_id']) && $args['_id']) {
                                return json_encode(cockpit('collections')->findOne($args['name'], [
                                    '_id' => $args['_id']
                                ], null, $populate, $filter));
                            } else {

                                $options['populate'] = $populate;

                                if (isset($args['limit'])) $options['limit'] = $args['limit'];
                                if (isset($args['skip'])) $options['skip'] = $args['skip'];
                                if (isset($args['sort'])) {
                                    parse_str($args['sort'], $sort);
                                    $options['sort'] = [];
                                    foreach ($sort as $key => &$value) {
                                        $options['sort'][$key]= intval($value);
                                    }
                                }

                                if ($args['filter']) {
                                    parse_str($args['filter'], $filter);
                                    $options['filter'] = $filter;
                                }

                                return json_encode(cockpit('collections')->find($args['name'], $options));
                            }

                        }
                    ]
                ]
            ]);

            $app->trigger('cockpitql.config', [$config]);

            $queryType = new ObjectType($config->getArrayCopy());
            $schema = new Schema([
                'query' => $queryType
            ]);

            $query = $app->param('query', '{}');
            $variableValues = null;

            try {

                $rootValue = [];
                $result = GraphQL::execute($schema, $query, $rootValue, null, null);

                if (isset($result['data'])) {

                    foreach($result['data'] as $key => $value) {

                        if ($value) {
                            if (substr($value,0,1) == '[' && substr($value,-1,1) == ']') {
                                $result['data'][$key] = json_decode($value);
                            } elseif (substr($value,0,1) == '{' && substr($value,-1,1) == '}') {
                                $result['data'][$key] = json_decode($value);
                            }elseif ($value == 'null') {
                                $result['data'][$key] = null;
                            }
                        }
                    }
                }

            } catch (\Exception $e) {
                return $app->stop(json_encode(['error' => [ 'message' => $e->getMessage() ]]), 400);
            }

            return $result;
        };
    });
}