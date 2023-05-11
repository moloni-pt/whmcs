<?php

namespace Moloni\Api\Settings;

use Moloni\Curl;

class DeliveryMethods
{
    public static function getAll()
    {
        return Curl::simple("deliveryMethods/getAll");
    }

    public static function insert($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("deliveryMethods/insert", $values);
    }

    public static function update($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("deliveryMethods/update", $values);
    }

    public static function delete($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("deliveryMethods/delete", $values);
    }
}
