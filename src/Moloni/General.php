<?php

namespace Moloni;

use Moloni\Api\Categories;
use Moloni\Api\Companies;
use Moloni\Api\Customers;
use Moloni\Api\Documents;
use Moloni\Api\GlobalSettings\Countries;
use Moloni\Api\GlobalSettings\Currencies;
use Moloni\Api\Products;
use Moloni\Api\Settings\Taxes;
use Moloni\Model\WhmcsDB;

class General
{
    public function createInvoice($invoiceID)
    {
        $hasMassPay = false;
        $forceDraft = false;

        $invoiceInfo = WhmcsDB::getInvoice($invoiceID);

        if (!$invoiceInfo) {
            Error::create("Documento não existe", "Documento não foi encontrado/já foi gerado");
        } else {
            $invoiceItems = WhmcsDB::getInvoiceItems($invoiceInfo->id);

            $invoice['company_id'] = COMPANY;
            $invoice['date'] = date('Y-m-d');
            $invoice['expiration_date'] = date('Y-m-d');
            if (defined('DOCUMENT_SET') && !empty(DOCUMENT_SET)) {
                $invoice['document_set_id'] = DOCUMENT_SET;
            } else {
                Error::create("Série Documento", "Série de documento não selecionada");
                return false;
            }

            $client = $this->verifyCustomer($invoiceInfo->userid);

            $fullCurrency = $this->getCurrencyCode($client['currency_code']->code);

            $invoice['customer_id'] = $client['customer_id'];

            $invoice['our_reference'] = $invoiceInfo->id;
            $invoice['your_reference'] = (!empty($invoiceInfo->invoicenum)) ? $invoiceInfo->invoicenum : '';

            $invoice['financial_discount'] = "";
            $invoice['special_discount'] = "";
            $invoice['maturity_date_id'] = defined('MATURITY_DATE') && !empty(MATURITY_DATE) ? MATURITY_DATE : null;
            $invoice['payment_method_id'] = defined('PAYMENT_METHOD') && !empty(PAYMENT_METHOD) ? PAYMENT_METHOD : null;

            if (!empty($fullCurrency['whmcs_curr'])) {
                if (!($fullCurrency['same_curr'])) {
                    $invoice['exchange_currency_id'] = $fullCurrency['whmcs_curr'];
                    $invoice['exchange_rate'] = $fullCurrency['exchange_value'];
                }
            } else {
                Error::create('Moeda', 'Moeda usada por cliente não existe no Moloni');
                return false;
            }

            $invoice['products'] = array();
            $x = 0;

            foreach ($invoiceItems as $item) {
                $settingsProducts = new Settings($item, $invoiceID);
                $invoicedItem = $settingsProducts->buildProduct();

                if (isset($invoicedItem['skip']) && $invoicedItem['skip'] == true) {
                    $invoicedItem['skip'] = false;
                } elseif (isset($invoicedItem['massPay']) && $invoicedItem['massPay'] == true) {
                    $hasMassPay = true;
                } else {
                    $invoice['products'][$x]['product_id'] = $this->product($invoicedItem, $item, $invoiceInfo, $fullCurrency);
                    $invoice['products'][$x]['name'] = $invoicedItem['name'];
                    $invoice['products'][$x]['summary'] = $invoicedItem['summary'];
                    $invoice['products'][$x]['discount'] = ($invoicedItem['discount'] > 0) ? $invoicedItem['discount'] : "";
                    $invoice['products'][$x]['qty'] = "1";
                    $invoiceTaxRate = ($invoiceInfo->taxrate == 0) ? $invoiceInfo->taxrate2 : $invoiceInfo->taxrate;
                    $productPrice = (!($fullCurrency['same_curr'])) ? $item->amount * $fullCurrency['exchange_value_product'] : $item->amount;
                    if (defined('REMOVE_TAX') && REMOVE_TAX) {
                        $invoice['products'][$x]['price'] = $productPrice - (($productPrice / (100 + $invoiceTaxRate)) * $invoiceTaxRate);
                    } else {
                        $invoice['products'][$x]['price'] = $productPrice;
                    }
                    $invoice['products'][$x]['order'] = $x;
                    if ($item->taxed == 1 && !empty((float)$invoiceTaxRate)) {
                        $invoice['products'][$x]['taxes'][0]['tax_id'] = Taxes::check($invoiceTaxRate);
                        $invoice['products'][$x]['taxes'][0]['value'] = round($productPrice * ($invoiceTaxRate / 100));
                    } elseif (defined('EXEMPTION_REASON') && !empty(EXEMPTION_REASON)) {
                        $invoice['products'][$x]['exemption_reason'] = EXEMPTION_REASON;
                        $forceDraft = true;
                    } else {
                        Error::create('Produtos', 'Não existe razão de isenção selecionada');
                        return false;
                    }
                    unset($invoicedItem);
                    $x++;
                }
            }

            $invoice['status'] = "0";

            $me = Companies::companyMe();

            if ($hasMassPay && empty($invoice['products'])) {
                $invoice['net_value'] = 0;
                $invoice['document_id'] = -1;
                WhmcsDB::insertMoloniInvoice($invoiceInfo, $invoice, '-1');
                Error::success('Encomenda guardada mas não gerada pois é junção de outras faturas');
                return false;
            }

            if (!Error::$exists) {
                if (!empty($invoice['your_reference']) &&
                    $documentExist = Documents::getOneInfo(false, $invoice['your_reference'])) {
                    if ((int)$documentExist['customer_id'] > 0) {
                        $value = ((int)$documentExist['status'] === 1) ? 2 : 0;
                        WhmcsDB::insertMoloniInvoice($invoiceInfo, $documentExist, $value);
                        $downloadURL = null;
                        if ((int)$documentExist['status'] === 1) {
                            $downloadURL = Documents::getPDFLink($documentExist['document_id']);
                        }

                        Error::success(
                            "Documento já se encontra gerado no Moloni!",
                            "https://www.moloni.pt/" . $me['slug'] . "/" .
                            Documents::getDocumentType() . "/showDetail/" . $documentExist['document_id'] . "/",
                            $downloadURL
                        );
                        return false;
                    }
                }

                $documentID = Documents::insertInvoice($invoice);

                if ($documentID) {
                    $documentInfo = Documents::getOneInfo($documentID);

                    $roundedTotal = round($invoiceInfo->total, 2);
                    if ($hasMassPay ||
                        round($documentInfo['net_value'], 2) == $roundedTotal ||
                        round($documentInfo['exchange_total_value'], 2) == $roundedTotal
                    ) {
                        $insertValue = 0;
                        $insertMessage = "Documento inserido como rascunho com sucesso!";

                        if (defined('DOCUMENT_STATUS') && DOCUMENT_STATUS == 1 && !$forceDraft && !$hasMassPay) {
                            $update = array();
                            $update['document_id'] = $documentID;
                            $update['status'] = 1;
                            $insertValue = 2;
                            $insertMessage = "Documento inserido e fechado com sucesso!";

                            if (defined('EMAIL_SEND') && EMAIL_SEND && !empty($client['email'])) {
                                $update['send_email'] = [];
                                $update['send_email'][] = [
                                    'email' => $client['email'],
                                    'name' => $client['name'],
                                    'msg' => ''
                                ];
                                $insertValue = 1;
                                $insertMessage = "Documento inserido, fechado e enviado por email";
                            }

                            #Inserir documento fechado
                            Documents::update($update);
                            $documentInfo = Documents::getOneInfo($documentID);
                        }

                        #Inserir documento como rascunho/fechado
                        $downloadURL = ($documentInfo['status'] == 1) ? Documents::getPDFLink($documentID) : null;
                        Error::success(
                            $insertMessage,
                            "https://www.moloni.pt/" . $me['slug'] . "/" .
                            Documents::getDocumentType() . "/showDetail/" . $documentID . "/",
                            $downloadURL
                        );
                        WhmcsDB::insertMoloniInvoice($invoiceInfo, $documentInfo, $insertValue);
                    } else {
                        #Inserir documento como rascunho com totais errados
                        Error::create(
                            "Documento",
                            "Documento inserido, mas totais não correspondem",
                            $documentInfo,
                            $invoiceInfo
                        );
                        WhmcsDB::insertMoloniInvoice($invoiceInfo, $documentInfo, '3');
                    }
                }
            }
        }
        return false;
    }

    public function product($productDefined, $item, $invoice, $moeda)
    {
        $reference = $productDefined['reference'];
        $productExists = Products::getByReference($reference);

        if ($productExists && is_array($productExists) && $productExists['product_id'] > 0) {
            return $productExists['product_id'];
        }

        $product = array();
        $product['category_id'] = Categories::check("WHMCS");

        if (isset($productDefined['type']) && in_array($productDefined['type'], [0, 1, 2])) {
            $product['type'] = (int)$productDefined['type'];
        } else {
            $product['type'] = (defined('AT_CATEGORY') && AT_CATEGORY === "SS") ? 2 : 1;
        }

        $product['name'] = $productDefined['name'];
        $product['summary'] = $productDefined['summary'];
        $product['reference'] = $reference;
        $product['ean'] = "";
        $invoiceTaxRate = ($invoice->taxrate == 0) ? $invoice->taxrate2 : $invoice->taxrate;
        $productPrice = (!($moeda['same_curr'])) ? $item->amount * $moeda['exchange_value_product'] : $item->amount;
        if (defined('REMOVE_TAX') && REMOVE_TAX) {
            $product['price'] = $productPrice - (($productPrice / (100 + $invoiceTaxRate)) * $invoiceTaxRate);
        } else {
            $product['price'] = $productPrice;
        }

        if (defined('MEASURE_UNIT') && !empty(MEASURE_UNIT)) {
            $product['unit_id'] = MEASURE_UNIT;
        } else {
            Error::create('Product', 'Não possui unidade de medida selecionada!');
            return false;
        }

        $product['has_stock'] = $product['type'] === 1 ? 1 : 0;
        $product['warehouse_id'] = 0;

        $product['stock'] = "0";
        $product['pos_favorite'] = "0";
        $product['at_product_category'] = (defined('AT_CATEGORY') && !empty(AT_CATEGORY)) ? AT_CATEGORY : '';

        if ((int)$invoice->taxrate === 0 && (int)$invoice->taxrate2 === 0) {
            if (defined('EXEMPTION_REASON') && !empty(EXEMPTION_REASON)) {
                $product['exemption_reason'] = EXEMPTION_REASON;
            } else {
                Error::create('Product', 'Não possui razão de isenção selecionada!');
                return false;
            }
        } else {
            $product['taxes'][0]['tax_id'] = Taxes::check($invoiceTaxRate);
            $product['taxes'][0]['value'] = round($productPrice * ($invoiceTaxRate / 100));
            $product['taxes'][0]['order'] = "0";
            $product['taxes'][0]['cumulative'] = "0";
        }
        $productID = Products::insert($product);

        return $productID;
    }

    public function verifyCustomer($id)
    {
        $customer = false;
        $number = false;

        $clientInfo = WhmcsDB::getCustomer($id);
        $name = (!empty($clientInfo->companyname)) ? $clientInfo->companyname : $clientInfo->firstname . " " . $clientInfo->lastname;

        $customVAT = WhmcsDB::getCustomFieldValueClient($id);

        if ($customVAT) {
            $vat = $customVAT;
        } elseif (isset($clientInfo->tax_id) && !empty($clientInfo->tax_id)) {
            $vat = $clientInfo->tax_id;
        } else {
            $vat = 999999990;
        }


        if ((int)$vat !== 999999990) {
            $customer = Customers::getByVat(['vat' => $vat]);
        } elseif (!empty($clientInfo->email)) {
            $customer = Customers::getByEmail(['email' => $clientInfo->email]);
        }

        if (!$customer) {
            $number = "9990";
            $customer = Customers::getByNumber(["number" => "9990"]);
        }

        $values = [];

        $returning['customer_id'] = $customer['customer_id'];
        $returning['email'] = $clientInfo->email;
        $returning['name'] = $name;
        $returning['currency_code'] = WhmcsDB::getCustomerCurrency($clientInfo->currency);

        if ($customer && count($customer) > 0) {
            if (!$number || (int)$number !== 9990) {
                if (defined('UPDATE_CUSTOMER') && UPDATE_CUSTOMER) {
                    $values['customer_id'] = $customer['customer_id'];
                    $values['name'] = $name;
                    $values['language_id'] = $this->getCountryCode($clientInfo->country, "language");
                    $values['address'] = $clientInfo->address1 . ((!empty($clientInfo->address2)) ? " - " . $clientInfo->address2 : "");
                    $values['zip_code'] = $this->checkZip($clientInfo->postcode, $clientInfo->country);
                    $values['city'] = $clientInfo->city;
                    $values['country_id'] = $this->getCountryCode($clientInfo->country, "country");
                    $values['email'] = $clientInfo->email;
                    $values['phone'] = $clientInfo->phonenumber;

                    $updated = Customers::update($values);
                    if ($updated['error']) {
                        return ($updated);
                    }

                    $returning['customer_id'] = $customer['customer_id'];
                }
            }
        } else {
            $me = Companies::companyMe();

            $MoloniCustomer['vat'] = $vat;
            $MoloniCustomer['number'] = Customers::getNextNumber();

            $MoloniCustomer['name'] = $name;
            $MoloniCustomer['email'] = $clientInfo->email;
            $MoloniCustomer['phone'] = $clientInfo->phonenumber;

            $MoloniCustomer['address'] = $clientInfo->address1 . ((!empty($clientInfo->address2)) ? " - " . $clientInfo->address2 : "");
            $MoloniCustomer['zip_code'] = $this->checkZip($clientInfo->postcode, $clientInfo->country);
            $MoloniCustomer['city'] = $clientInfo->city;

            $MoloniCustomer['country_id'] = $this->getCountryCode($clientInfo->country, "country");
            $MoloniCustomer['language_id'] = $this->getCountryCode($clientInfo->country, "language");

            $MoloniCustomer['maturity_date_id'] = ((defined('MATURITY_DATE') && !empty(MATURITY_DATE)) ? MATURITY_DATE : $me['maturity_date_id']);
            $MoloniCustomer['payment_method_id'] = ((defined('PAYMENT_METHOD') && !empty(PAYMENT_METHOD)) ? PAYMENT_METHOD : $me['payment_method_id']);
            $MoloniCustomer['qty_copies_document'] = $me['docs_copies'];
            $MoloniCustomer['delivery_method_id'] = $me['delivery_method_id'];

            $MoloniCustomer['salesman_id'] = "0";
            $MoloniCustomer['payment_day'] = "0";
            $MoloniCustomer['discount'] = "0";
            $MoloniCustomer['credit_limit'] = "0";

            $insertClient = Customers::insert($MoloniCustomer);
            if ($insertClient['error']) {
                return ($insertClient);
            }

            $returning['customer_id'] = $insertClient['customer_id'];
        }

        return ($returning);
    }

    public function checkZip($zipCode, $country = "PT")
    {
        if ($country === 'PT') {
            $zipCode = trim(str_replace(" ", "", $zipCode));
            $zipCode = preg_replace("/[^0-9]/", "", $zipCode);
            $lenZipCode = strlen($zipCode);

            switch ($lenZipCode) {
                case 0:
                    $zipCode = "1000-100";
                    break;
                case 1:
                    $zipCode = $zipCode . "000-" . "000";
                    break;
                case 2:
                    $zipCode = $zipCode . "00-" . "000";
                    break;
                case 3:
                    $zipCode = $zipCode . "0-" . "000";
                    break;
                case 4:
                    $zipCode = $zipCode . "-" . "000";
                    break;
                case 5:
                    $zipCode = $zipCode[0] . $zipCode[1] . $zipCode[2] . $zipCode[3] . "-" . $zipCode[4] . "00";
                    break;
                case 6:
                    $zipCode = $zipCode[0] . $zipCode[1] . $zipCode[2] . $zipCode[3] . "-" . $zipCode[4] . $zipCode[5] . "0";
                    break;
                case 7:
                    $zipCode = $zipCode[0] . $zipCode[1] . $zipCode[2] . $zipCode[3] . "-" . $zipCode[4] . $zipCode[5] . $zipCode[6];
                    break;
            }

            $regexp = "/[0-9]{4}\-[0-9]{3}/";
            if (preg_match($regexp, $zipCode)) {
                return ($zipCode);
            }

            return ("1000-100");
        }
        return $zipCode;
    }

    public function getCountryCode($iso2, $return = "all")
    {
        $info = array();
        if ($return === "country") {
            if (strtolower($iso2) === 'gb') {
                return 174;
            }

            $countries = Countries::getAll();
            foreach ($countries as $moloniCountry) {
                if (strtolower($iso2) == strtolower($moloniCountry['iso_3166_1'])) {
                    $info['country_id'] = $moloniCountry['country_id'];
                }
            }
            return ($info['country_id']);
        }

        if ($return === "language") {
            if ($iso2 === 'PT' || $iso2 === 'BR') {
                $info['language_id'] = 1;
            } else {
                $country_spanish = [
                    'MX', 'CO', 'ES', 'AR', 'PE', 'VE', 'CL', 'EC',
                    'GT', 'CU', 'BO', 'DO', 'HN', 'PY', 'SV', 'NI',
                    'CR', 'PA', 'UY', 'PR', 'GQ'
                ];
                $info['language_id'] = in_array($iso2, $country_spanish, true) ? 3 : 2;
            }
            return ($info['language_id']);
        }

        return false;
    }

    public function getCurrencyCode($code)
    {
        $fullCurrency = [];
        $currencyCodes = Currencies::getAll();
        foreach ($currencyCodes as $currCode) {
            if (strtoupper($code) == $currCode['iso4217']) {
                $fullCurrency['whmcs_curr'] = $currCode['currency_id'];
            }
        }

        if (!isset($fullCurrency['whmcs_curr']) || empty($fullCurrency['whmcs_curr'])) {
            $fullCurrency['whmcs_curr'] = 0;
        } else {
            $fullCurrency['moloni_curr'] = Companies::companyMe()['currency']['currency_id'];

            if ((int)$fullCurrency['whmcs_curr'] != (int)$fullCurrency['moloni_curr']) {
                $exchangeValues = Currencies::getAllCurrencyExchange();
                foreach ($exchangeValues as $exValue) {
                    if (($exValue['from'] == $fullCurrency['whmcs_curr']) && $exValue['to'] == $fullCurrency['moloni_curr']) {
                        $fullCurrency['exchange_value_product'] = $exValue['value'];
                    }
                    if (($exValue['from'] == $fullCurrency['moloni_curr']) && $exValue['to'] == $fullCurrency['whmcs_curr']) {
                        $fullCurrency['exchange_value'] = $exValue['value'];
                    }
                }
                $fullCurrency['same_curr'] = false;
            } else {
                $fullCurrency['same_curr'] = true;
            }
        }

        return $fullCurrency;
    }
}
