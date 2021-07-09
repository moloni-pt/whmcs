<?php

namespace Moloni\Api\GlobalSettings;

use Moloni\Curl;

class Currencies
{
    public static function getAll()
    {
        return Curl::simple("currencies/getAll");
    }

    public static function getAllCurrencyExchange()
    {
        return Curl::simple("currencyExchange/getAll");
    }
}