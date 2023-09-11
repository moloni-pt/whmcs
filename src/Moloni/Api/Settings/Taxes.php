<?php

namespace Moloni\Api\Settings;

use Moloni\Curl;
use Moloni\Error;
use Moloni\Exceptions\APIException;

class Taxes
{
    public static function getAll()
    {
        return Curl::simple("taxes/getAll");
    }

    /**
     * Create tax
     *
     * @throws APIException
     */
    public static function insert($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $result = Curl::simple("taxes/insert", $values);

        if (isset($result['tax_id'])) {
            return $result['tax_id'];
        }

        throw new APIException(
            "Erro ao inserir taxa.",
            [
                'values_sent' => $values,
                'values_receive' => $result,

            ],
            "taxes/insert"
        );
    }

    public static function update($values)
    {
        if (!is_array($values)) {
            $values = [];
        }

        return Curl::simple("taxes/update", $values);
    }

    /**
     * Get product tax
     *
     * @throws APIException
     */
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
