<?php

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use CockpitQL\Types\JsonType;

$queries['fields']['collection'] = [

    'type' => Type::listOf(JsonType::instance()),
    'args' => [
        'name'  => Type::nonNull(Type::string()),
        '_id'   => Type::string(),
        'limit' => Type::int(),
        'skip'  => Type::int(),
        'sort'  => JsonType::instance(),
        'lang'  => Type::string(),
        'populate'   => ['type' => Type::int(), 'defaultValue' => 0],
        'projection' => ['type' => Type::string(), 'defaultValue' => ''],
        'filter'   => ['type' => JsonType::instance(), 'defaultValue' => '']
    ],
    'resolve' => function ($root, $args) use($app) {

        $collection = $args['name'];

        if (!$app->module('collections')->exists($collection)) {
            return [];
        }

        $collection = $app->module('collections')->collection($collection);
        $user = $app->module('cockpit')->getUser();

        if ($user) {

            if (!$app->module('collections')->hasaccess($collection['name'], 'entries_view')) {
                $app->stop(['error'=> 'Unauthorized'], 401);
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
                $options['sort'] = $args['sort'];
            }

            if ($args['filter']) {
                $options['filter'] = $args['filter'];
            }

            return cockpit('collections')->find($args['name'], $options);
        }

    }
];

$mutations['fields']['saveCollectionItem'] = [
    'args' => [
        'name' => Type::nonNull(Type::string()),
        'data' => Type::nonNull(JsonType::instance()),
        'options' => JsonType::instance(),
    ],
    'type' => new ObjectType([
        'name' => 'saveCollectionItemOutput',
        'fields' => [
            'data' => ['type' => JsonType::instance()]
        ]
    ]),
    'resolve' => function ($root, $args) use($app) {
        
        $name = $args['name'];
        $data = $args['data'];
        $options = $args['options'] ?? [];

        if (!$app->module('collections')->exists($name)) {
            $app->stop(['error'=> "Collection <{$name}> does not exist!"], 404);
        }

        $collection = $app->module('collections')->collection($name);
        $user = $app->module('cockpit')->getUser();

        if ($user) {

            if (!$app->module('collections')->hasaccess($collection['name'], isset($data['_id']) ? 'entries_edit':'entries_create')) {
                $app->stop(['error'=> 'Unauthorized'], 401);
            }

        } else {
            $app->stop(['error'=> 'Unauthorized'], 401);
        }

        $data = $app->module('collections')->save($name, $data, $options);

        return compact('data');
    },
];
