<?php

namespace Moloni\Api\GlobalSettings;

use Moloni\Curl;

class Languages
{
    public static function getAll()
    {
        return Curl::simple("languages/getAll");
    }
}