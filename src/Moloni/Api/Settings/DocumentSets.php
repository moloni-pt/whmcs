<?php

namespace Moloni\Api\Settings;

use Moloni\Curl;

class DocumentSets
{
    public static function getAll($companyID = COMPANY)
    {
        $values = ["company_id" => $companyID];
        return Curl::simple("documentSets/getAll", $values);
    }

    public static function insert($values, $companyID = COMPANY)
    {
        $values["company_id"] = $companyID;
        return Curl::simple("documentSets/insert", $values);
    }

    public static function update($values, $companyID = COMPANY)
    {
        $values["company_id"] = $companyID;
        return Curl::simple("documentSets/update", $values);
    }

    public static function delete($values, $companyID = COMPANY)
    {
        $values["company_id"] = $companyID;
        return Curl::simple("documentSets/delete", $values);
    }
}