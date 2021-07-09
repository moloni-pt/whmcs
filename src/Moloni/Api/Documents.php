<?php

namespace Moloni\Api;

use Moloni\Curl;
use Moloni\Error;

class Documents
{

    /**
     * @param $values
     * @param bool $type
     * @param mixed $companyID
     * @return bool|mixed
     */
    public static function insertInvoice($values, $type = false, $companyID = COMPANY)
    {
        if (!$type && defined('DOCUMENT_TYPE')) {
            $type = DOCUMENT_TYPE;
        }

        $values['company_id'] = $companyID;
        $result = Curl::simple("$type/insert", $values);

        if (isset($result['document_id'])) {
            return ($result['document_id']);
        } else {
            Error::create("$type/insert", "Erro ao inserir documento", $values, $result);
            return false;
        }
    }

    /**
     * @param $document_id
     * @param mixed $companyID
     * @param bool $your_reference
     * * @param bool $our_reference
     * @return mixed
     */
    public static function getOneInfo($document_id = false, $your_reference = false, $companyID = COMPANY)
    {
        $values['company_id'] = $companyID;
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
     * @param $values
     * @param bool $type
     * @param mixed $companyID
     * @return mixed
     */
    public static function update($values, $type = false, $companyID = COMPANY)
    {
        if (!$type && defined('DOCUMENT_TYPE')) {
            $type = DOCUMENT_TYPE;
        }

        $values['company_id'] = $companyID;
        $result = Curl::simple($type . "/update", $values);

        if (isset($result['document_id'])) {
            return ($result['document_id']);
        } else {
            Error::create("$type/update", "Erro ao actualizar documento", $values, $result);
            return false;
        }
    }

    /**
     * @param $values
     * @param int $max
     * @return array|bool
     */
    public static function getAll($values, $max = 120)
    {
        $values['company_id'] = COMPANY;

        $total = 0;
        $offset = (isset($values['offset']) ? $values['offset'] : "0");
        $results = array();

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
        $values = array();
        $values['company_id'] = COMPANY;
        $values['document_id'] = $document_id;

        $result = Curl::simple("documents/getPDFLink", $values);
        return (isset($result['url'])) ? $result['url'] : false;
    }

    public static function getDocumentType($documentId = 0)
    {
        if(!empty($documentId)){
            $type = self::getOneInfo($documentId)['document_type']['saft_code'];
        } elseif(defined('DOCUMENT_TYPE')){
            $type = DOCUMENT_TYPE;
        } else {
            Error::create('Document type', 'Tem que selecionar um tipo de documento');
            return false;
        }

        switch ($type) {
            default:
            case "FT" :
            case "invoices" :
                return "Faturas";
                break;
            case "FR" :
            case "invoiceReceipts" :
                return "FaturasRecibo";
                break;
            case "GT" :
            case "billsOfLading" :
                return "GuiasTransporte";
                break;
            case "OR" :
            case "estimates" :
                return "Orcamentos";
                break;
        }
    }
}
