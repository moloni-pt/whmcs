<?php


namespace Moloni\Api;

use Moloni\Curl;

class Companies
{
    private static $cached = [];

    public static function getAll()
    {
        $companies = Curl::simple("companies/getAll");

        $companyList = [];

        foreach ($companies as $company) {
            if ((int)$company['company_id'] !== 5) {
                $companyList[] = $company;
            }
        }

        return $companyList;
    }

    public static function companyMe()
    {
        if (isset(self::$cached[__FUNCTION__])) {
            return self::$cached[__FUNCTION__];
        }

        self::$cached[__FUNCTION__] = Curl::simple("companies/getOne");

        return self::$cached[__FUNCTION__];
    }
}
