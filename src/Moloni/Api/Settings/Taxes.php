<?php

namespace Moloni\Api\Settings;

use Moloni\Curl;
use Moloni\Error;

class Taxes
{
    public static function getAll($companyID = COMPANY)
    {
        $values['company_id'] = $companyID;
        return Curl::simple("taxes/getAll", $values);
    }

    public static function insert($values, $companyID = COMPANY)
    {
        $values['company_id'] = $companyID;
        $result = Curl::simple("taxes/insert", $values);
        if (isset($result['tax_id'])) {
            return ($result['tax_id']);
        }

        Error::create("taxes/insert", "Erro ao inserir taxa", $values, $result);
        return false;
    }

    public static function update($values, $companyID = COMPANY)
    {
        $values['company_id'] = $companyID;
        return Curl::simple("taxes/update", $values);
    }

    public static function check($rate = 23)
    {
        $taxes = self::getAll();
        foreach ($taxes as $tax) {
            if (round($rate, 2) == round($tax['value'], 2)) {
                return $tax['tax_id'];
            }
        }

        $values = [];
        $values['name'] = "Taxa " . round($rate, 2);
        $values['value'] = $rate;
        $values['type'] = "1";
        $values['saft_type'] = "1";
        $values['vat_type'] = "OUT";
        $values['stamp_tax'] = "0";
        $values['exemption_reason'] = defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '';
        $values['fiscal_zone'] = "PT";
        $values['active_by_default'] = "0";

        return self::insert($values);
    }
}