<?php


namespace Moloni\Api;


use Moloni\Curl;

class Companies
{
    public static function getAll()
    {
        $companies = Curl::simple("companies/getAll");

        $companyList = [];

        foreach ($companies as $company) {
            if ((int)$company['company_id'] !== 5) {
                $companyList[] = $company;
            }
        }

        return $companyList;
    }

    public static function companyMe($companyID = COMPANY)
    {
        $values = array();
        $values['company_id'] = $companyID;
        return Curl::simple("companies/getOne", $values);
    }
}