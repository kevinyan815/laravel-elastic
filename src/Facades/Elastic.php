<?php

namespace KevinYan\Elastic\Facades;

use Illuminate\Support\Facades\Facade;


class Elastic extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'elastic';
    }
}
