<?php

namespace Moloni\Api\GlobalSettings;

use Moloni\Curl;

class TaxExemptions
{
    /**
     * @return array
     */
    public static function getAll()
    {
        $exemptions = Curl::simple("taxExemptions/getAll");

        return $exemptions && is_array($exemptions) ? $exemptions : [];
    }
}
