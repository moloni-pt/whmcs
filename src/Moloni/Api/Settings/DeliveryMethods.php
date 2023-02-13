<?php

namespace Moloni\Api\Settings;

use Moloni\Curl;

class DeliveryMethods
{
    public static function getAll($companyID = COMPANY)
    {
        $values = ["company_id" => $companyID];
        return Curl::simple("deliveryMethods/getAll", $values);
    }

    public static function insert($values, $companyID = COMPANY)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $values["company_id"] = $companyID;
        return Curl::simple("deliveryMethods/insert", $values);
    }

    public static function update($values, $companyID = COMPANY)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $values["company_id"] = $companyID;
        return Curl::simple("deliveryMethods/update", $values);
    }

    public static function delete($values, $companyID = COMPANY)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $values["company_id"] = $companyID;
        return Curl::simple("deliveryMethods/delete", $values);
    }
}
