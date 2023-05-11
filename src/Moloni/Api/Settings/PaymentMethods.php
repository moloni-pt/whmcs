<?php

namespace Moloni\Api\Settings;

use Moloni\Curl;

class PaymentMethods
{
    public static function getAll()
    {
        return Curl::simple("paymentMethods/getAll");
    }

    public static function insert($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("paymentMethods/insert", $values);
    }

    public static function update($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("paymentMethods/update", $values);
    }

    public static function delete($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("paymentMethods/delete", $values);
    }
}
