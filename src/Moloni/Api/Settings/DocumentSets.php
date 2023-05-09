<?php

namespace Moloni\Api\Settings;

use Moloni\Curl;

class DocumentSets
{
    public static function getAll()
    {
        return Curl::simple("documentSets/getAll");
    }

    public static function insert($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("documentSets/insert", $values);
    }

    public static function update($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("documentSets/update", $values);
    }

    public static function delete($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("documentSets/delete", $values);
    }
}