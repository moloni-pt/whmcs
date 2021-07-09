<?php

namespace Moloni\Api;

use Moloni\Curl;
use Moloni\Error;

class Customers
{
    public static function number($companyID = COMPANY)
    {
        return Curl::simple("customers/count");
    }

    public static function getAll($companyID = COMPANY)
    {
        return Curl::simple("customers/getAll");
    }

    public static function getOne($companyID = COMPANY)
    {
        return Curl::simple("customers/getOne");
    }

    public static function countBySearch($companyID = COMPANY)
    {
        return Curl::simple("customers/countBySearch");
    }

    public static function getBySearch($companyID = COMPANY)
    {
        return Curl::simple("customers/getBySearch");
    }

    public static function countByVat($companyID = COMPANY)
    {
        return Curl::simple("customers/countByVat");
    }

    public static function getByVat($values, $companyID = COMPANY)
    {
        $values['company_id'] = $companyID;
        $result = Curl::simple("customers/getByVat", $values);
        foreach ($result as $customer) {
            if ($values['vat'] == $customer['vat']) {
                return ($customer);
            }
        }
        return ($result[0]);
    }

    public static function getByEmail($values, $companyID = COMPANY)
    {
        $values['company_id'] = $companyID;
        $result = Curl::simple("customers/getByEmail", $values);
        foreach ($result as $customer) {
            if ($values['email'] == $customer['email']) {
                return ($customer);
            }
        }
        return ($result[0]);
    }

    public static function getByNumber($values, $companyID = COMPANY)
    {
        $values['company_id'] = $companyID;
        $result = Curl::simple("customers/getByNumber", $values);
        foreach ($result as $customer) {
            if ($values['number'] == $customer['number']) {
                return ($customer);
            }
        }
        return ($result[0]);
    }

    public static function countByNumber($companyID = COMPANY)
    {
        return Curl::simple("customers/countByNumber");
    }

    public static function countByName($companyID = COMPANY)
    {
        return Curl::simple("customers/countByName");
    }

    public static function getByName($companyID = COMPANY)
    {
        return Curl::simple("customers/getByName");
    }

    public static function getLastNumber($companyID = COMPANY)
    {
        return Curl::simple("customers/getLastNumber");
    }

    public static function getNextNumber($companyID = COMPANY)
    {
        $values['company_id'] = $companyID;
        $result = Curl::simple("customers/getNextNumber", $values);

        return ($result['number']);
    }

    public static function insert($values, $companyID = COMPANY)
    {
        $values['company_id'] = $companyID;
        $result = Curl::simple("customers/insert", $values);

        if (isset($result['customer_id'])) {
            return $result;
        }

        Error::create("customers/insert", "Erro ao inserir cliente", $values, $result);
        return false;
    }

    public static function update($values, $companyID = COMPANY)
    {
        $values['company_id'] = $companyID;
        $result = curl::simple("customers/update", $values);
        if (isset($result['customer_id'])) {
            return ($result);
        }

        Error::create("customers/update", "Erro ao actualizar cliente", $values, $result);
        return false;
    }

    public static function delete($companyID = COMPANY)
    {
        return curl::simple("customers/delete");
    }
}