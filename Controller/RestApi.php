<?php

namespace CockpitQL\Controller;

class RestApi extends \LimeExtra\Controller {

    public function query() {

        if (stripos($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'], 'multipart/form-data') !== false) {
            
            /**
             * Query with upload
             * https://github.com/jaydenseric/graphql-multipart-request-spec
             */

            $map = $this->param('map', '{}');
            $operations = $this->param('operations');

            $result = json_decode($operations, true);
            $map = json_decode($map, true);

            if (isset($result['operationName'])) {
                $result['operation'] = $result['operationName'];
                unset($result['operationName']);
            }

            $query = $result['query'] ?? '';
            $variables = $result['variables'] ?? [];

            foreach ($map as $fileKey => $locations) {
                
                foreach ($locations as $location) {

                    $parts = explode('.', $location);

                    if ($parts[0] == 'variables') {
                        array_shift($parts);
                    }

                    $v = &$variables;

                    foreach ($parts as $key) {
                        if (!isset($v[$key]) || !is_array($v[$key])) {
                            $v[$key] = [];
                        }
                        $v = &$v[$key];
                    }

                    $v = $_FILES[$fileKey];
                }
            }

        } else {

            $query = $this->param('query', '{}');
            $variables = $this->param('variables', null);
        }

        return $this->module('cockpitql')->query($query, $variables);
    }
}