<?php 
namespace Neokike\Redsys\Facades;

use Illuminate\Support\Facades\Facade;

class Redsys extends Facade {
    public static function getFacadeAccessor()
    {
        return 'redsys';
    }
}