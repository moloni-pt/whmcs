<?php

namespace Moloni\Api\GlobalSettings;

use Moloni\Curl;

class Countries
{
    public static function getAll()
    {
        return Curl::simple("countries/getAll");
    }
}
