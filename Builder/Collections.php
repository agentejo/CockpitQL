<?php


namespace CockpitQL\Builder;


class Collections
{
    private $app;

    private $collections;

    private $config = [];

    /**
     * Builder constructor.
     * @param $app
     */
    public function __construct($app, $collections)
    {
        $this->app = $app;
        $this->collections = $collections;
    }


}