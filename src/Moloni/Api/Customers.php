<?php

namespace Moloni\Api;

use Moloni\Curl;
use Moloni\Error;
use Moloni\Exceptions\APIException;

class Customers
{
    public static function number()
    {
        return Curl::simple("customers/count");
    }

    public static function getAll()
    {
        return Curl::simple("customers/getAll");
    }

    public static function getByVat($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("customers/getByVat", $values);

        foreach ($result as $customer) {
            if (!is_array($customer) || !isset($customer['vat'])) {
                continue;
            }

            if ($values['vat'] == $customer['vat']) {
                return $customer;
            }
        }

        return false;
    }

    public static function getByEmail($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("customers/getByEmail", $values);

        if (!is_array($result)) {
            return false;
        }

        foreach ($result as $customer) {
            if (!is_array($customer) || !isset($customer['email'])) {
                continue;
            }

            if ($values['email'] === $customer['email']) {
                return $customer;
            }
        }

        return false;
    }

    public static function getByNumber($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("customers/getByNumber", $values);

        if (!is_array($result)) {
            return false;
        }

        foreach ($result as $customer) {
            if (!is_array($customer) || !isset($customer['number'])) {
                continue;
            }

            if ($values['number'] == $customer['number']) {
                return $customer;
            }
        }

        return false;
    }

    public static function getNextNumber()
    {
        $result = Curl::simple("customers/getNextNumber");

        return ($result['number']);
    }

    /**
     * Create customer
     *
     * @throws APIException
     */
    public static function insert($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("customers/insert", $values);

        if (isset($result['customer_id'])) {
            return $result;
        }


        throw new APIException(
            "Erro ao inserir cliente.",
            [
                'values_sent' => $values,
                'values_receive' => $result,
            ],
            "customers/insert"
        );
    }

    /**
     * Update customer
     *
     * @throws APIException
     */
    public static function update($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = curl::simple("customers/update", $values);

        if (isset($result['customer_id'])) {
            return $result;
        }

        throw new APIException(
            "Erro ao actualizar cliente.",
            [
                'values_sent' => $values,
                'values_receive' => $result,
            ],
            "customers/update"
        );
    }

    public static function delete()
    {
        return curl::simple("customers/delete");
    }
}
