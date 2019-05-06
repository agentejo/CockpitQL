<?php

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use CockpitQL\Types\JsonType;
use CockpitQL\Types\FieldType;

$collections = cockpit('collections')->collections();

foreach ($collections as $name => &$meta) {

    $_name = $name.'Collection';

    $config['fields'][$_name] = [

        'type' => Type::listOf(new ObjectType([
            'name'   => $_name,
            'fields' => function() use($meta, $app, $_name) {

                $fields = array_merge([
                    '_id'       => Type::nonNull(Type::string()),
                    '_created'  => Type::nonNull(Type::int()),
                    '_modified' => Type::nonNull(Type::int())
                ], FieldType::buildFieldsDefinitions($meta));

                $app->trigger("cockpitql.{$_name}.fields", [&$fields]);

                return $fields;
            }
        ])),

        'args' => [
            '_id'   => Type::string(),
            'limit' => Type::int(),
            'skip'  => Type::int(),
            'sort'  => JsonType::instance(),
            'lang'  => Type::string(),
            'populate'   => ['type' => Type::int(), 'defaultValue' => 0],
            'projection' => ['type' => Type::string(), 'defaultValue' => ''],
            'filter'   => ['type' => JsonType::instance(), 'defaultValue' => '']
        ],

        'resolve' => function ($root, $args) use($app, $name) {

            $collection = $app->module('collections')->collection($name);
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

                return cockpit('collections')->find($name, $options);
            }
        }
    ];
}
