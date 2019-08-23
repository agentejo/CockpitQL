<?php

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use CockpitQL\Types\JsonType;
use CockpitQL\Types\FieldType;


$singletons = cockpit('singletons')->singletons();

foreach ($singletons as $name => &$meta) {

    $_name = $name.'Singleton';

    $queries['fields'][$_name] = [

        'type' => new ObjectType([
            'name'   => $_name,
            'fields' => function() use($meta, $app, $_name) {

                $fields = array_merge([
                    '_id' => Type::string(),
                    '_created' => Type::int(),
                    '_modified' =>Type::int()
                ], FieldType::buildFieldsDefinitions($meta));

                $app->trigger("cockpitql.{$_name}.fields", [&$fields]);

                return $fields;
            }
        ]),

        'args' => [
            'lang'  => Type::string(),
            'populate'   => ['type' => Type::int(), 'defaultValue' => 0],
        ],

        'resolve' => function ($root, $args) use($app, $name) {

            $singleton = $app->module('singletons')->singleton($name);
            $user = $app->module('cockpit')->getUser();

            if ($user) {

                if (!$app->module('singletons')->hasaccess($singleton['name'], 'data')) {
                    return '{"error": "Unauthorized"}';
                }
            }

            $options  = [];

            if (isset($args['lang']) && $args['lang']) {
                $options['lang'] = $args['lang'];
            }

            if (isset($args['populate']) && $args['populate']) {
                $options['populate'] = $args['populate'];
            }

            if ($user) {
                $options['user'] = $user;
            }

            return cockpit('singletons')->getData($name, $options);
        }
    ];
}
