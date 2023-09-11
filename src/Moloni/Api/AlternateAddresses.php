<?php

namespace Moloni\Api;

use Moloni\Curl;
use Moloni\Error;
use Moloni\Exceptions\APIException;

class AlternateAddresses
{
    public static function getAll($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("customerAlternateAddresses/getAll", $values);

        return ($result);
    }

    /**
     * Create alternate addresses
     *
     * @throws APIException
     */
    public static function insert($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("customerAlternateAddresses/insert", $values);

        if (isset($result['address_id'])) {
            return ($result);
        }

        throw new APIException(
            "Erro ao inserir morada alternativa.",
            [
                'values_sent' => $values,
                'values_receive' => $result,
            ],
            "customerAlternateAddresses/insert"
        );
    }

    public static function update()
    {
        return curl::simple("customerAlternateAddresses/update");
    }

    public static function delete()
    {
        return curl::simple("customerAlternateAddresses/delete");
    }
}
