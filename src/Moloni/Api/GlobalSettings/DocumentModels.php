<?php

namespace Moloni\Api\GlobalSettings;

use Moloni\Curl;

class DocumentModels
{
    public static function getAll()
    {
        return Curl::simple("documentModels/getAll");
    }
}