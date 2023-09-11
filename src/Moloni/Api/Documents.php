<?php

namespace Moloni\Api;

use Moloni\Curl;
use Moloni\Error;
use Moloni\Exceptions\APIException;

class Documents
{

    /**
     * Create document
     *
     * @param $values
     * @param bool $type
     *
     * @return bool|mixed
     *
     * @throws APIException
     */
    public static function insertInvoice($values, $type = false)
    {
        if (!is_array($values)) {
            $values = [];
        }

        if (!$type && defined('DOCUMENT_TYPE')) {
            $type = DOCUMENT_TYPE;
        }

        $result = Curl::simple("$type/insert", $values);

        if (isset($result['document_id'])) {
            return ($result['document_id']);
        }

        throw new APIException(
            "Erro ao inserir documento.",
            [
                'values_sent' => $values,
                'values_receive' => $result,

            ],
            "$type/insert"
        );
    }

    /**
     * @param bool $document_id
     * @param bool $your_reference
     *
     * @return mixed
     */
    public static function getOneInfo($document_id = false, $your_reference = false)
    {
        $values = [];

        if ($document_id) {
            $values['document_id'] = $document_id;
        }

        if ($your_reference) {
            $values['your_reference'] = $your_reference;
        }

        $result = curl::simple("documents/getOne", $values);

        if (isset($result['document_id'])) {
            return ($result);
        }

        return false;
    }

    /**
     * Update document
     *
     * @param $values
     * @param bool $type
     *
     * @return mixed
     *
     * @throws APIException
     */
    public static function update($values, $type = false)
    {
        if (!is_array($values)) {
            $values = [];
        }

        if (!$type && defined('DOCUMENT_TYPE')) {
            $type = DOCUMENT_TYPE;
        }

        $result = Curl::simple($type . "/update", $values);

        if (isset($result['document_id'])) {
            return ($result['document_id']);
        }

        throw new APIException(
            "Erro ao actualizar documento.",
            [
                'values_sent' => $values,
                'values_receive' => $result,

            ],
            "$type/update"
        );
    }

    /**
     * @param $values
     * @param int $max
     * @return array|bool
     */
    public static function getAll($values, $max = 120)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $total = 0;
        $offset = $values['offset'] ?? "0";
        $results = [];

        while ($total < $max) {
            $values['offset'] = $offset;
            $result = Curl::simple("documents/getAll", $values);
            $results = array_merge($results, $result);
            $total += count($result);
            $offset += 50;

            if (count($result) < 50) {
                break;
            }
        }

        return (count($results) > 0) ? $results : false;
    }

    /**
     * @param $document_id
     * @return bool|mixed
     */
    public static function getPDFLink($document_id)
    {
        $values = [];
        $values['document_id'] = $document_id;

        $result = Curl::simple("documents/getPDFLink", $values);

        return (isset($result['url'])) ? $result['url'] : false;
    }

    public static function getDocumentType($documentId = 0)
    {
        if (!empty($documentId)) {
            $type = self::getOneInfo($documentId)['document_type']['saft_code'];
        } elseif (defined('DOCUMENT_TYPE')) {
            $type = DOCUMENT_TYPE;
        } else {
            Error::create('Document type', 'Tem que selecionar um tipo de documento');
            return false;
        }

        switch ($type) {
            default:
            case "FT":
            case "invoices":
                return "Faturas";

            case "FR":
            case "invoiceReceipts":
                return "FaturasRecibo";

            case "GT":
            case "billsOfLading":
                return "GuiasTransporte";

            case "OR":
            case "estimates":
                return "Orcamentos";
        }
    }
}
