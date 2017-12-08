<?php

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use CockpitQL\Types\JsonType;

$collections = cockpit('collections')->collections();

foreach ($collections as $name => $meta) {

    $gqlName = "all".ucfirst($name);

    if (strpos($query, $gqlName) === false) continue;

    $config['fields'][$gqlName] = [

        'type' => Type::listOf(new ObjectType([
            'name'   => $name,
            'fields' => function() use($meta) {

                return array_merge([
                    '_id' => Type::string(),
                    '_created' => Type::int(),
                    '_modified' =>Type::int()
                ], _cockpitQLbuildFieldsDefinition($meta));
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


function _cockpitQLbuildFieldsDefinition($meta) {

    $fields = [];

    foreach ($meta['fields'] as $field) {

        $def = [];

        switch ($field['type']) {
            case 'text':
            case 'textarea':
            case 'code':
            case 'code':
            case 'password':
            case 'wysiwyg':
            case 'markdown':
            case 'date':
            case 'file':
            case 'time':
            case 'color':
            case 'colortag':
            case 'select':
                $def['type'] = Type::string();
                break;
            case 'boolean':
                $def['type'] = Type::boolean();
                break;
            case 'gallery':
                $def['type'] = Type::listOf(new ObjectType([
                    'name' => 'gallery_image',
                    'fields' => [
                        'path' => Type::string(),
                        'meta' => JsonType::instance()
                    ]
                ]));
                break;
            case 'multipleselect':
            case 'access-list':
            case 'tags':
                $def['type'] = Type::listOf(Type::string());
                break;
            case 'image':
                $def['type'] = new ObjectType([
                    'name' => 'image',
                    'fields' => [
                        'path' => Type::string(),
                        'meta' => JsonType::instance()
                    ]
                ]);
                break;
            case 'asset':
                $def['type'] = new ObjectType([
                    'name' => 'asset',
                    'fields' => [
                        '_id' => Type::string(),
                        'title' => Type::string(),
                        'path' => Type::string(),
                        'mime' => Type::string(),
                        'tags' => Type::listOf(Type::string()),
                        'colors' => Type::listOf(Type::string()),
                    ]
                ]);
                break;

            case 'location':
                $def['type'] = new ObjectType([
                    'name' => 'location',
                    'fields' => [
                        'address' => Type::string(),
                        'lat' => Type::float(),
                        'lng' => Type::float()
                    ]
                ]);
                break;

            case 'layout':
            case 'layout-grid':
                $def['type'] = JsonType::instance();
                break;

            case 'set':
                $def['type'] = new ObjectType([
                    'name' => 'set_'.$field['name'],
                    'fields' => _cockpitQLbuildFieldsDefinition($field['options'])
                ]);
                break;

            case 'repeater':

                $def['type'] = Type::listOf(new ObjectType([
                    'name' => 'repeater_item',
                    'fields' => [
                        'value' => JsonType::instance()
                    ]
                ]));
                break;

            case 'collectionlink':

                $collection = cockpit('collections')->collection($field['options']['link']);

                if (!$collection) {
                    continue;
                }

                $linkType = new ObjectType([
                    'name' => 'collection_link_'.$field['options']['link'],
                    'fields' => function() use($collection) {

                        $fields = [
                            '_id' => Type::string(),
                            '_created' => Type::int(),
                            '_modified' =>Type::int()
                        ];

                        foreach ($collection['fields'] as &$field) {
                            $fields[$field['name']] = JsonType::instance();
                        }

                        return $fields;
                    }
                ]);

                if (isset($field['options']['multiple']) && $field['options']['multiple']) {
                    $def['type'] = Type::listOf($linkType);
                } else {
                    $def['type'] = $linkType;
                }

                break;
        }

        if (!empty($def)) {
            $fields[$field['name']] = $def;
        }
    }

    return $fields;
}
