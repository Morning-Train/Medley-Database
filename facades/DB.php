<?php

namespace MorningMedley\Facades;

use Illuminate\Support\Facades\Facade;

/**
 */
class DB extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'db.connection';
    }
}
