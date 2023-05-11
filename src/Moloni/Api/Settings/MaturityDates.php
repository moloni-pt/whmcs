<?php

namespace Moloni\Api\Settings;

use Moloni\Curl;

class MaturityDates
{
    public static function getAll()
    {
        return Curl::simple("maturityDates/getAll");
    }

    public static function insert($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("maturityDates/insert", $values);
    }

    public static function update($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("maturityDates/update", $values);
    }

    public static function delete($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("maturityDates/delete", $values);
    }
}
