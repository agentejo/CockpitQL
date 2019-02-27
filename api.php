<?php

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;


$app->on('cockpit.rest.init', function($routes) use($app) {

    $routes['graphql'] = function() use($app) {

        $query = $app->param('query', '{}');
        $variableValues = $app->param('variables', null);

        $config = new ArrayObject([
            'name' => 'Query',
            'fields' => []
        ]);

        // load field schema defenitions
        foreach ([
            'region', // deprecated and will be removed in the future
            'collection',
            'singleton',
            'dynamic_collections'
        ] as $fieldSchemaFile) {
            include(__DIR__."/fields/{$fieldSchemaFile}.php");
        }

        $app->trigger('cockpitql.config', [$config]);

        $queryType = new ObjectType($config->getArrayCopy());
        $schema = new Schema([
            'query' => $queryType
        ]);

        try {

            $rootValue = [];
            $result = GraphQL::executeQuery($schema, $query, $rootValue, null, $variableValues)->toArray();

            if (isset($result['data'])) {

                foreach ($result['data'] as $key => $value) {

                    if ($value && is_string($value)) {

                        $start = substr($value,0,1);
                        $end   = substr($value,-1,1);

                        if (($start == '[' && $end == ']') || ($start == '{' && $end == '}')) {
                            $result['data'][$key] = json_decode($value);
                        } elseif ($value == 'null') {
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
