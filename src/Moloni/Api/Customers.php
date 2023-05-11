<?php

namespace Moloni\Api;

use Moloni\Curl;
use Moloni\Error;

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

    public static function getOne()
    {
        return Curl::simple("customers/getOne");
    }

    public static function countBySearch()
    {
        return Curl::simple("customers/countBySearch");
    }

    public static function getBySearch()
    {
        return Curl::simple("customers/getBySearch");
    }

    public static function countByVat()
    {
        return Curl::simple("customers/countByVat");
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
                return ($customer);
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
                return ($customer);
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
                return ($customer);
            }
        }

        return false;
    }

    public static function countByNumber()
    {
        return Curl::simple("customers/countByNumber");
    }

    public static function countByName()
    {
        return Curl::simple("customers/countByName");
    }

    public static function getByName()
    {
        return Curl::simple("customers/getByName");
    }

    public static function getLastNumber()
    {
        return Curl::simple("customers/getLastNumber");
    }

    public static function getNextNumber()
    {
        $result = Curl::simple("customers/getNextNumber");

        return ($result['number']);
    }

    public static function insert($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("customers/insert", $values);

        if (isset($result['customer_id'])) {
            return $result;
        }

        Error::create("customers/insert", "Erro ao inserir cliente", $values, $result);
        return false;
    }

    public static function update($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = curl::simple("customers/update", $values);

        if (isset($result['customer_id'])) {
            return ($result);
        }

        Error::create("customers/update", "Erro ao actualizar cliente", $values, $result);

        return false;
    }

    public static function delete()
    {
        return curl::simple("customers/delete");
    }
}
