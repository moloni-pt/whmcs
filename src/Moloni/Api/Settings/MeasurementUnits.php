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
        $values['company_id'] = COMPANY;
        return Curl::simple("measurementUnits/getAll", $values);
    }

    public static function insert($values)
    {
        $values['company_id'] = COMPANY;
        $result = Curl::simple("measurementUnits/insert", $values);
        if (isset($result['unit_id'])) {
            return ($result['unit_id']);
        }

        Error::create("measurementUnits/insert", "Erro ao inserir unidade de medida", $values, $result);
        return false;
    }

    public static function update($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("measurementUnits/update", $values);
    }

    public static function delete($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("measurementUnits/delete", $values);
    }
}