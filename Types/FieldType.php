<?php

namespace CockpitQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class FieldType {

    protected static $types = [];
    protected static $names = [];


    private static function getName($name) {

        if (!isset(self::$names[$name])) {
            self::$names[$name] = 0;
        } else {
            self::$names[$name]++;
            $name .= self::$names[$name];
        }

        return $name;
    }

    public static function buildFieldsDefinitions($meta) {

        $fields = [];

        foreach ($meta['fields'] as $field) {

            $def = self::getType($field);

            if ($def) {
                
                $fields[$field['name']] = $def;

                if ($field['type'] == 'text' && isset($field['options']['slug']) && $field['options']['slug']) {
                    $fields[$field['name'].'_slug'] = Type::string();
                }
            }
        }

        return $fields;
    }

    protected static function collectionLinkFieldType($name, $field, $collection) {

        $typeName = "{$name}CollectionLink";

        if (!isset(self::$types[$typeName])) {

            $linkType = new ObjectType([
                'name' => $typeName,
                'fields' => function() use($collection) {

                    $fields = array_merge([
                        '_id' => Type::nonNull(Type::string()),
                        '_created' => Type::nonNull(Type::int()),
                        '_modified' => Type::nonNull(Type::int())
                    ], FieldType::buildFieldsDefinitions($collection));

                    return $fields;
                }
            ]);

            self::$types[$typeName] = $linkType;
        }

        return self::$types[$typeName];
    }


    protected static function getType($field) {

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

                if ($field['type'] == 'text' && isset($field['options']['type']) && $field['options']['type'] == 'number') {
                    $def['type'] = Type::int();
                } else {
                    $def['type'] = Type::string();
                }

                break;
            case 'boolean':
                $def['type'] = Type::boolean();
                break;
            case 'rating':
                $def['type'] = Type::int();
                break;
            case 'gallery':
                $def['type'] = Type::listOf(new ObjectType([
                    'name' => uniqid('gallery_image'),
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
                    'name' => uniqid('image'),
                    'fields' => [
                        'path' => Type::string(),
                        'meta' => JsonType::instance()
                    ]
                ]);
                break;
            case 'asset':
                $def['type'] = new ObjectType([
                    'name' => uniqid('asset'),
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
                    'name' => uniqid('location'),
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
                    'name' => self::getName('Set'.ucfirst($field['name'])),
                    'fields' => self::buildFieldsDefinitions($field['options'])
                ]);
                break;

            case 'repeater':

                if (isset($field['options']['field'])) {

                    $field['options']['field']['name'] = 'RepeaterItemValue'.ucfirst($field['name']);

                    $typeRepeater = new ObjectType([
                        'name' => self::getName('RepeaterItem'.ucfirst($field['name'])),
                        'fields' => [
                            'value' => self::getType($field['options']['field'])
                        ]
                    ]);

                } else {

                    $typeRepeater = new ObjectType([
                        'name' => self::getName('RepeaterItem'.ucfirst($field['name'])),
                        'fields' => [
                            'value' => JsonType::instance()
                        ]
                    ]);
                }

                $def['type'] = Type::listOf($typeRepeater);
                break;

            case 'collectionlink':

                $collection = cockpit('collections')->collection($field['options']['link']);

                if (!$collection) {
                    break;
                }

                $linkType = self::collectionLinkFieldType($field['options']['link'], $field, $collection);

                if (isset($field['options']['multiple']) && $field['options']['multiple']) {
                    $def['type'] =  Type::listOf($linkType);
                    $def['args'] = [
                        'limit' => Type::int(),
                        'skip' => Type::int(),
                        'sort' => JsonType::instance(),
                    ];
                    $def['resolve'] = function ($root, $args) use ($field) {
                        $options = [
                            'filter' => [
                                '_id' => [
                                    '$in' => array_column($root[$field['name']], '_id')
                                ]
                            ]
                        ];

                        if (isset($args['limit'])) $options['limit'] = $args['limit'];
                        if (isset($args['skip'])) $options['skip'] = $args['skip'];
                        if (isset($args['sort'])) $options['sort'] = $args['sort'];

                        return cockpit('collections')->find($field['options']['link'], $options);
                    };
                } else {
                    $def['type'] = $linkType;
                    $def['resolve'] = function ($root) use ($field) {
                        return cockpit('collections')->findOne($field['options']['link'], [
                            '_id' => $root[$field['name']]['_id']
                        ]);
                    };
                }

                break;
        }

        if (isset($def['type'], $field['required']) && $field['required']) {
            $def['type'] = Type::nonNull($def['type']);
        }

        return count($def) ? $def : null;
    }


    public static function instance($field) {
        self::getType($field);
    }
}
