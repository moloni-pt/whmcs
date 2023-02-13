<?php

namespace Moloni\Api;

use Moloni\Curl;
use Moloni\Error;

class Products
{
    public static function getCount($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/count", $values);
    }

    public static function getAll($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/getAll", $values);
    }

    public static function getAllCategories($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("productCategories/getAll", $values);
    }

    public static function getOne($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/getOne", $values);
    }

    public static function countBySearch($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/countBySearch", $values);
    }

    public static function getBySearch($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/getBySearch", $values);
    }

    public static function countByName($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/countByName", $values);
    }

    public static function getByName($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/getByName", $values);
    }

    public function getReferenceByName($name)
    {
        $values['company_id'] = COMPANY;
        $values['name'] = $name;
        $values['exact'] = "1";

        $return = curl::simple("products/getByName", $values);

        if (count($return) > 0) {
            $reference = $return[0]['reference'];
        } else {
            $reference = "Extra";
        }

        return $reference;
    }

    public static function countByReference($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/countByReference", $values);
    }

    public static function getByReference($reference)
    {
        $values = [];
        $values['company_id'] = COMPANY;
        $values['reference'] = $reference;
        $values['exact'] = "1";
        $result = Curl::simple("products/getByReference", $values);

        return (is_array($result) && isset($result[0]) ? $result[0] : false);
    }

    public static function countByEAN($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/countByEAN", $values);
    }

    public static function getByEAN($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/getByEAN", $values);
    }

    public static function countModifiedSince($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/countModifiedSince", $values);
    }

    public static function getModifiedSince($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/getModifiedSince", $values);
    }

    public static function insert($values, $rawValues = [])
    {
        $values['company_id'] = COMPANY;

        $result = Curl::simple("products/insert", $values);
        if (isset($result['product_id'])) {
            return $result['product_id'];
        }

        Error::create("products/insert", "Erro ao inserir artigo", array_merge($values, $rawValues), $result);
        return false;
    }

    public static function update($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/insert", $values);
    }

    public static function delete($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("products/insert", $values);
    }
}
