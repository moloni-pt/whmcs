<?php

namespace Moloni\Api\Settings;

use Moloni\Curl;

class MaturityDates
{
    public static function getAll($companyID = COMPANY)
    {
        $values = ["company_id" => $companyID];
        return Curl::simple("maturityDates/getAll", $values);
    }

    public static function insert($values, $companyID = COMPANY)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $values["company_id"] = $companyID;
        return Curl::simple("maturityDates/insert", $values);
    }

    public static function update($values, $companyID = COMPANY)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $values["company_id"] = $companyID;
        return Curl::simple("maturityDates/update", $values);
    }

    public static function delete($values, $companyID = COMPANY)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $values["company_id"] = $companyID;
        return Curl::simple("maturityDates/delete", $values);
    }
}
