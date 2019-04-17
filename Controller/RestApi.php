<?php

namespace CockpitQL\Controller;

class RestApi extends \LimeExtra\Controller {

    public function query() {

        $query = $this->param('query', '{}');
        $variables = $this->param('variables', null);
    
        return $this->module('cockpitql')->query($query, $variables);
    }
}