<?php


namespace ZeeshanSaab\ReverbApi\Facades;

use Illuminate\Support\Facades\Facade;

class Reverb extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'reverb.api';
    }
}
