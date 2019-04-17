<?php

namespace CockpitQL\Controller;


class Admin extends \Cockpit\AuthController {


    public function playground() {

        return $this->render('cockpitql:views/playground.php');
    }

    public function graphiql() {

        $this->layout = false;

        return $this->render('cockpitql:views/graphiql.php');
    }

    public function query() {

        $query = $this->param('query', '{}');
        $variables = $this->param('variables', null);
    
        return $this->module('cockpitql')->query($query, $variables);
    }
}