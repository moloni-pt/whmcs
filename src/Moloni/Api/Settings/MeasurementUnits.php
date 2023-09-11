<?php

namespace Moloni\Api\Settings;

use Moloni\Curl;
use Moloni\Error;

class MeasurementUnits
{
    public static function check($name)
    {
        $units = self::getAll();
        foreach ($units as $unit) {
            if (mb_strtolower($name) == mb_strtolower($unit['name'])) {
                return $unit['unit_id'];
            }
        }

        $values['name'] = $name;
        $values['short_name'] = "Uni.";

        return self::insert($values);
    }

    public static function getAll()
    {
        return Curl::simple("measurementUnits/getAll");
    }

    public static function insert($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("measurementUnits/insert", $values);

        if (isset($result['unit_id'])) {
            return ($result['unit_id']);
        }

        Error::create("measurementUnits/insert", "Erro ao inserir unidade de medida", [$values, $result]);
        return false;
    }

    public static function update($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("measurementUnits/update", $values);
    }

    public static function delete($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("measurementUnits/delete", $values);
    }
}
