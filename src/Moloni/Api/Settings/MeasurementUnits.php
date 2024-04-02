<?php

namespace Moloni\Api\Settings;

use Moloni\Curl;
use Moloni\Error;
use Moloni\Exceptions\APIException;

class MeasurementUnits
{
    public static function getAll()
    {
        return Curl::simple("measurementUnits/getAll");
    }

    /**
     * Create measurement unit
     *
     * @throws APIException
     */
    public static function insert($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("measurementUnits/insert", $values);

        if (isset($result['unit_id'])) {
            return $result['unit_id'];
        }

        throw new APIException(
            "Erro ao inserir unidade de medida.",
            [
                'values_sent' => $values,
                'values_receive' => $result,
            ],
            "measurementUnits/insert"
        );
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
