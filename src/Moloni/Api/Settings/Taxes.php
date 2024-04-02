<?php

namespace Moloni\Api\Settings;

use Moloni\Curl;
use Moloni\Enums\SaftType;
use Moloni\Enums\TaxType;
use Moloni\Exceptions\APIException;
use Moloni\Facades\LoggerFacade;

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
            LoggerFacade::info('Taxa criada no Moloni', [
                'tag' => 'service:tax:create',
                'data' => $values
            ]);

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
    public static function check($rate = 23, $fiscalCountry = [])
    {
        $countryId = (int)$fiscalCountry['country_id'];
        $countryCode = strtoupper($fiscalCountry['country_code']);
        $targetRate = round($rate, 2);

        $taxes = self::getAll();

        foreach ($taxes as $tax) {
            if (strtoupper($tax['fiscal_zone']) !== $countryCode) {
                continue;
            }

            if ((int)$tax['country_id'] !== $countryId) {
                continue;
            }

            if ((int)$tax['saft_type'] !== SaftType::IVA) {
                continue;
            }

            if ((int)$tax['type'] !== TaxType::PERCENTAGE) {
                continue;
            }

            if ($targetRate == round($tax['value'], 2)) {
                return $tax['tax_id'];
            }
        }

        $values = [];
        $values['name'] = "Tax $countryCode - $targetRate";
        $values['value'] = $rate;
        $values['type'] = TaxType::PERCENTAGE;
        $values['saft_type'] = SaftType::IVA;
        $values['vat_type'] = "OUT";
        $values['stamp_tax'] = "0";
        $values['exemption_reason'] = defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '';
        $values['fiscal_zone'] = $countryCode;
        $values['country_id'] = $countryId;
        $values['active_by_default'] = "0";

        return self::insert($values);
    }
}
