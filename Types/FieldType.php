<?php

namespace CockpitQL\Types;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use CockpitQL\Types\JsonType;

class FieldType {

    protected static $types = [];


    public static function buildFieldsDefinitions($meta) {

        $fields = [];

        foreach ($meta['fields'] as $field) {

            $def = self::getType($field);


            if ($def) {
                $fields[$field['name']] = $def;
            }
        }

        return $fields;
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
                $def['type'] = Type::string();
                break;
            case 'boolean':
                $def['type'] = Type::boolean();
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
                    'name' => uniqid('set_'.$field['name']),
                    'fields' => self::buildFieldsDefinitions($field['options'])
                ]);
                break;

            case 'repeater':

                $def['type'] = Type::listOf(new ObjectType([
                    'name' => uniqid('repeater_item'),
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
                    'name' => uniqid('collection_link_'.$field['options']['link']),
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

        return count($def) ? $def : null;
    }


    public static function instance($field) {
        self::getType($field);
    }
}
