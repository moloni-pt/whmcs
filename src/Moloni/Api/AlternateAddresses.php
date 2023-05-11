<?php

namespace Moloni\Api;

use Moloni\Curl;
use Moloni\Error;

class AlternateAddresses
{
    public static function search($values)
    {
        $results = self::getAll($values);

        foreach ($results as $result) {
            if (mb_strtolower($result['designation']) == mb_strtolower($values['designation'])) {
                return ($result['address_id']);
            }
        }

        return false;

    }

    public static function getAll($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("customerAlternateAddresses/getAll", $values);

        return ($result);
    }

    public static function insert($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("customerAlternateAddresses/insert", $values);

        if (isset($result['address_id'])) {
            return ($result);
        }

        Error::create("customerAlternateAddresses/insert", "Erro ao inserir morada alternativa", $values, $result);
        return false;
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