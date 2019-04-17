<?php

namespace CockpitQL;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class Query {

    public static function process($query = '{}', $variables = null) {

        $app = cockpit();
        $config = new \ArrayObject([
            'name' => 'Query',
            'fields' => []
        ]);

        // load field schema defenitions
        foreach ([
            'region', // deprecated and will be removed in the future
            'collection',
            'singleton',
            'dynamic_collections',
            'dynamic_singletons',
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
            $result = GraphQL::executeQuery($schema, $query, $rootValue, null, $variables)->toArray();

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
    }
}