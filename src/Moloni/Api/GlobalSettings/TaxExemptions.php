<?php

namespace Moloni\Api\GlobalSettings;

use Moloni\Curl;

class TaxExemptions
{
    public static function getAll()
    {
        return Curl::simple("taxExemptions/getAll");
    }
}