<?php

namespace Moloni\Api;

use Moloni\Curl;
use Moloni\Exceptions\APIException;

class Categories
{
    /**
     * Get category by name
     *
     * @throws APIException
     */
    public static function check($name)
    {
        $categories = self::getAll();

        foreach ($categories as $category) {
            if (mb_strtolower($name) == mb_strtolower($category['name'])) {
                return $category['category_id'];
            }
        }

        $values = [];
        $values['parent_id'] = "0";
        $values['name'] = $name;
        $values['description'] = "";
        $values['pos_enabled'] = "1";

        return self::insert($values);
    }

    public static function getAll()
    {
        $values = [];
        $values['parent_id'] = "0";

        return Curl::simple("productCategories/getAll", $values);
    }

    /**
     * Create category
     *
     * @throws APIException
     */
    public static function insert($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("productCategories/insert", $values);

        if (isset($result['category_id'])) {
            return $result['category_id'];
        }

        throw new APIException(
            "Erro ao inserir categoria.",
            [
                'values_sent' => $values,
                'values_receive' => $result,
            ],
            "productCategories/insert"
        );
    }

    public static function update($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("productCategories/update", $values);
    }

    public static function delete($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("productCategories/delete", $values);
    }

}
