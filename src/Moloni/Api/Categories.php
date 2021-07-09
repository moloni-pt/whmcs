<?php

namespace Moloni\Api;

use Moloni\Curl;

class Categories
{
    public static function check($name)
    {
        $categories = self::getAll();
        foreach ($categories as $category) {
            if (mb_strtolower($name) == mb_strtolower($category['name'])) {
                return $category['category_id'];
            }
        }

        $values['parent_id'] = "0";
        $values['name'] = $name;
        $values['description'] = "";
        $values['pos_enabled'] = "1";

        return self::insert($values);

    }

    public static function getAll()
    {
        $values['company_id'] = COMPANY;
        $values['parent_id'] = "0";
        return Curl::simple("productCategories/getAll", $values);
    }

    public static function insert($values)
    {
        $values['company_id'] = COMPANY;
        $result = Curl::simple("productCategories/insert", $values);
        if (isset($result['category_id'])) {
            return $result['category_id'];
        } else {
            return false;
        }
    }

    public static function update($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("productCategories/update", $values);
    }

    public static function delete($values)
    {
        $values['company_id'] = COMPANY;
        return Curl::simple("productCategories/delete", $values);
    }

}