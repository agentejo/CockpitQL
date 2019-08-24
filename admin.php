<?php


$app->on('admin.init', function() {

    if (!$this->module('cockpit')->isSuperAdmin()) {

        $this->bind('/cockpitql/*', function() {
            return $this('admin')->denyRequest();
        });

        return;
    }

    // bind admin routes /singleton/*
    $this->bindClass('CockpitQL\\Controller\\Admin', 'cockpitql');

    $this->on(['cockpit.menu', 'cockpit.menu.aside'], function() {
        $this->renderView("cockpitql:views/partials/menu.php");
    });

});