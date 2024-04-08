<?php

namespace Moloni\Api;

use Moloni\Curl;
use Moloni\Error;
use Moloni\Exceptions\APIException;

class Products
{
    public static function getCount($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/count", $values);
    }

    public static function getAll($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/getAll", $values);
    }

    public static function getAllCategories($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("productCategories/getAll", $values);
    }

    public static function getOne($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/getOne", $values);
    }

    public static function countBySearch($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/countBySearch", $values);
    }

    public static function getBySearch($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/getBySearch", $values);
    }

    public static function countByName($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/countByName", $values);
    }

    public static function getByName($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/getByName", $values);
    }

    public function getReferenceByName($name)
    {
        $values = [];
        $values['name'] = $name;
        $values['exact'] = "1";

        $return = curl::simple("products/getByName", $values);

        if (is_array($return) && count($return) > 0) {
            $reference = $return[0]['reference'];
        } else {
            $reference = "Extra";
        }

        return $reference;
    }

    public static function countByReference($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/countByReference", $values);
    }

    public static function getByReference($reference)
    {
        $values = [];
        $values['reference'] = $reference;
        $values['exact'] = "1";

        $result = Curl::simple("products/getByReference", $values);

        return (is_array($result) && isset($result[0]) ? $result[0] : false);
    }

    public static function countByEAN($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/countByEAN", $values);
    }

    public static function getByEAN($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/getByEAN", $values);
    }

    public static function countModifiedSince($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/countModifiedSince", $values);
    }

    public static function getModifiedSince($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/getModifiedSince", $values);
    }

    /**
     * Create product
     *
     * @throws APIException
     */
    public static function insert($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("products/insert", $values);

        if (isset($result['product_id'])) {
            return $result;
        }

        throw new APIException(
            "Erro ao inserir artigo.",
            [
                'values_sent' => $values,
                'values_receive' => $result
            ],
            "products/insert"
        );
    }

    public static function update($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/insert", $values);
    }

    public static function delete($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("products/insert", $values);
    }
}
