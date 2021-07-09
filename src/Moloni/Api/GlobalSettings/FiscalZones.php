<?php

namespace Moloni\Api\GlobalSettings;

use Moloni\Curl;

class FiscalZones
{
    public static function getAll()
    {
        return Curl::simple("fiscalZones/getAll");
    }
}