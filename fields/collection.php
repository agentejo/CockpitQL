<?php

use GraphQL\Type\Definition\Type;
use CockpitQL\Types\JsonType;


return [
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
                $options['sort'] = $args['sort'];
            }

            if ($args['filter']) {
                $options['filter'] = $args['filter'];
            }

            return cockpit('collections')->find($args['name'], $options);
        }

    }
];
