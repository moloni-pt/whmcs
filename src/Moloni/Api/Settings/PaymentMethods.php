<?php

namespace Moloni\Api\Settings;

use Moloni\Curl;

class PaymentMethods
{
    public static function getAll($companyID = COMPANY)
    {
        $values = ["company_id" => $companyID];
        return Curl::simple("paymentMethods/getAll", $values);
    }

    public static function insert($values, $companyID = COMPANY)
    {
        $values = ["company_id" => $companyID];
        return Curl::simple("paymentMethods/insert", $values);
    }

    public static function update($values, $companyID = COMPANY)
    {
        $values = ["company_id" => $companyID];
        return Curl::simple("paymentMethods/update", $values);
    }

    public static function delete($values, $companyID = COMPANY)
    {
        $values = ["company_id" => $companyID];
        return Curl::simple("paymentMethods/delete", $values);
    }
}