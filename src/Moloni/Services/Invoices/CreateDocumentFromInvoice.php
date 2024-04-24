<?php

namespace Moloni\Services\Invoices;

use Moloni\Api\Categories;
use Moloni\Api\Customers;
use Moloni\Api\Documents;
use Moloni\Api\PaymentMethods;
use Moloni\Api\Products;
use Moloni\Api\Settings\Taxes;
use Moloni\Core\Storage;
use Moloni\Api\Companies;
use Moloni\Enums\DocumentCreationStatus;
use Moloni\Enums\DocumentType;
use Moloni\Error;
use Moloni\Exceptions\APIException;
use Moloni\Exceptions\DocumentWarning;
use Moloni\Facades\LoggerFacade;
use Moloni\Model\WhmcsDB;
use Moloni\Enums\DocumentStatus;
use Moloni\Exceptions\DocumentException;
use Moloni\Api\GlobalSettings\Countries;
use Moloni\Api\GlobalSettings\Currencies;
use Moloni\Settings;

class CreateDocumentFromInvoice
{
    private $isHook = false;

    private $company = [];

    private $invoice;
    private $invoiceData;
    private $invoiceId;
    private $invoiceItems;
    private $invoiceCustomer;

    private $fullCurrency = [];
    private $fiscalCountry = [];

    private $documentId = 0;
    private $documentData = [];
    private $documentCustomer = [];

    private $hasMassPay = false;

    /**
     * Constructor
     *
     * @throws DocumentException
     */
    public function __construct($invoiceId = 0)
    {
        if (empty($invoiceId)) {
            throw new DocumentException("Documento não foi encontrado/já foi gerado", [], 'Documento não existe');
        }

        $this->invoiceId = $invoiceId;

        $this
            ->loadInvoice()
            ->loadInvoiceData()
            ->loadInvoiceItems()
            ->loadInvoiceCustomer()
            ->loadMoloniCompany();
    }

    //          PUBLICS          //

    /**
     * Runner
     *
     * @throws DocumentException
     * @throws DocumentWarning
     */
    public function execute()
    {
        $this->documentData = [];

        $this
            ->defineFiscalZone()
            ->defineBasics()
            ->defineExchange()
            ->defineCustomer()
            ->defineProducts()
            ->definePayment();

        if ($this->hasMassPay && empty($this->documentData['products'])) {
            $this->documentData['net_value'] = 0;
            $this->documentData['document_id'] = -1;

            $logMessage = 'Encomenda guardada mas não gerada pois é junção de outras faturas';

            $this->createDocumentEntry($this->documentData, DocumentCreationStatus::MASS_PAY);
            $this->createInfoDocumentLog($logMessage);
            $this->successMessage($logMessage);

            return;
        }

        /** Backwards compatible error checking */
        if (Error::$exists) {
            throw new DocumentException(
                Error::$error['message'],
                Error::$error['data'],
                Error::$error['where']
            );
        }

        if (!empty($this->documentData['your_reference'])) {
            $documentExist = Documents::getOneInfo(false, $this->documentData['your_reference']);

            if ($documentExist && isset($documentExist['customer_id']) && (int)$documentExist['customer_id'] > 0) {
                if ((int)$documentExist['status'] === 1) {
                    $value = DocumentCreationStatus::INSERTED_AS_CLOSED;
                } else {
                    $value = DocumentCreationStatus::INSERTED_AS_DRAFT;
                }

                if ((int)$documentExist['status'] === 1) {
                    $downloadURL = Documents::getPDFLink($documentExist['document_id']);
                } else {
                    $downloadURL = null;
                }

                $logMessage = 'Documento já se encontra gerado no Moloni!';

                $this->createDocumentEntry($documentExist, $value);
                $this->createInfoDocumentLog($logMessage);
                $this->successMessage($logMessage, $documentExist['document_id'], $downloadURL);

                return;
            }
        }

        try {
            $documentID = Documents::insertInvoice($this->documentData);
        } catch (APIException $e) {
            throw new DocumentException($e->getMessage(), $e->getData(), $e->getWhere());
        }

        if ($documentID) {
            $this->documentId = $documentID;
            $documentInfo = Documents::getOneInfo($documentID);

            $roundedTotal = round($this->invoice->total, 2);

            if (
                $this->hasMassPay ||
                round($documentInfo['net_value'], 2) === $roundedTotal ||
                round($documentInfo['exchange_total_value'], 2) === $roundedTotal
            ) {
                $insertValue = 0;
                $insertMessage = "Documento inserido como rascunho com sucesso!";

                if (defined('DOCUMENT_STATUS') && DOCUMENT_STATUS == DocumentStatus::CLOSED && !$this->hasMassPay) {
                    $update = [];
                    $update['document_id'] = $documentID;
                    $update['status'] = DocumentStatus::CLOSED;

                    if (defined('EMAIL_SEND') && EMAIL_SEND && !empty($this->documentCustomer['email'])) {
                        $update['send_email'] = [];
                        $update['send_email'][] = [
                            'email' => $this->documentCustomer['email'],
                            'name' => $this->documentCustomer['name'],
                            'msg' => ''
                        ];

                        $insertValue = DocumentCreationStatus::INSERTED_AS_CLOSED_AND_SENT;
                        $insertMessage = "Documento inserido, fechado e enviado por email.";
                    } else {
                        $insertValue = DocumentCreationStatus::INSERTED_AS_CLOSED;
                        $insertMessage = "Documento inserido e fechado com sucesso!";
                    }

                    try {
                        Documents::update($update);
                    } catch (APIException $e) {
                        throw new DocumentException($e->getMessage(), $e->getData(), $e->getWhere());
                    }

                    $documentInfo = Documents::getOneInfo($documentID);
                }

                #Inserir documento como rascunho/fechado
                $downloadURL = ($documentInfo['status'] == DocumentStatus::CLOSED) ? Documents::getPDFLink($documentID) : null;

                $this->createDocumentEntry($documentInfo, $insertValue);
                $this->createInfoDocumentLog($insertMessage);
                $this->successMessage($insertMessage, $documentID, $downloadURL);
            } else {
                #Inserir documento como rascunho com totais errados
                $logMessage = "Documento inserido, mas totais não correspondem";

                $this->createDocumentEntry($documentInfo, DocumentCreationStatus::INSERTED_WITH_ERROR);
                $this->createWarningDocumentLog($logMessage);

                throw new DocumentWarning($logMessage, [], "Documento");
            }
        }
    }

    //          DEFINES          //

    /**
     * Define document fiscal zone
     *
     * @throws DocumentException
     */
    private function defineFiscalZone(): CreateDocumentFromInvoice
    {
        $fiscalCountry = [
            'country_id' => 0,
            'country_code' => strtolower($this->invoiceData->country)
        ];

        if ($fiscalCountry['country_code'] === 'gb') {
            $fiscalCountry['country_id'] = 174;
        } else {
            $countries = Countries::getAll();

            foreach ($countries as $moloniCountry) {
                if ($fiscalCountry['country_code'] !== strtolower($moloniCountry['iso_3166_1'])) {
                    continue;
                }

                $fiscalCountry['country_id'] = (int)$moloniCountry['country_id'];

                break;
            }
        }

        if (empty($fiscalCountry['country_id'])) {
            throw new DocumentException("Zona fiscal não encontrada", $fiscalCountry, 'Zona fiscal');
        }

        $this->fiscalCountry = $fiscalCountry;

        return $this;
    }

    /**
     * Define basic information
     *
     * @throws DocumentException
     */
    private function defineBasics(): CreateDocumentFromInvoice
    {
        if (!defined('DOCUMENT_SET') || empty(DOCUMENT_SET)) {
            throw new DocumentException("Série de documento não selecionada", [], 'Série Documento');
        }

        $this->documentData['document_set_id'] = DOCUMENT_SET;
        $this->documentData['company_id'] = Storage::$MOLONI_COMPANY_ID;
        $this->documentData['date'] = date('Y-m-d');
        $this->documentData['expiration_date'] = date('Y-m-d');
        $this->documentData['our_reference'] = $this->invoice->id;

        if (!empty($this->invoice->invoicenum)) {
            $this->documentData['your_reference'] = $this->invoice->invoicenum;
        } else {
            $this->documentData['your_reference'] = '';
        }

        $this->documentData['financial_discount'] = "";
        $this->documentData['special_discount'] = "";

        if (defined('MATURITY_DATE') && !empty(MATURITY_DATE)) {
            $this->documentData['maturity_date_id'] = MATURITY_DATE;
        } else {
            $this->documentData['maturity_date_id'] = null;
        }

        $this->documentData['status'] = DocumentStatus::DRAFT;

        return $this;
    }

    /**
     * Set document exchange rate
     *
     * @throws DocumentException
     */
    private function defineExchange(): CreateDocumentFromInvoice
    {
        $fullCurrency = $this->getCustomerCurrencyCode();

        if (empty($fullCurrency['whmcs_curr'])) {
            throw new DocumentException('Moeda usada por cliente não existe no Moloni', [], 'Moeda');
        }

        if (!$fullCurrency['same_curr']) {
            $this->documentData['exchange_currency_id'] = $fullCurrency['whmcs_curr'];
            $this->documentData['exchange_rate'] = $fullCurrency['exchange_value'];
        }

        $this->fullCurrency = $fullCurrency;

        return $this;
    }

    /**
     * Set document costumer
     *
     * @throws DocumentException
     */
    private function defineCustomer(): CreateDocumentFromInvoice
    {
        $possibleCustomer = false;
        $number = false;

        if (empty($this->invoiceCustomer->companyname)) {
            $name = $this->invoiceCustomer->firstname . " " . $this->invoiceCustomer->lastname;
        } else {
            $name = $this->invoiceCustomer->companyname;
        }

        $customVAT = WhmcsDB::getCustomFieldValueClient($this->invoice->userid);

        if ($customVAT) {
            $vat = $customVAT;
        } elseif (!empty($this->invoiceCustomer->tax_id)) {
            $vat = $this->invoiceCustomer->tax_id;
        } else {
            $vat = 999999990;
        }

        if ((int)$vat !== 999999990) {
            $possibleCustomer = Customers::getByVat(['vat' => $vat]);
        } elseif (!empty($this->invoiceCustomer->email)) {
            $possibleCustomer = Customers::getByEmail(['email' => $this->invoiceCustomer->email]);
        }

        if (empty($possibleCustomer)) {
            $number = "9990";
            $possibleCustomer = Customers::getByNumber(["number" => "9990"]);
        }

        $actualCustomer = [];
        $actualCustomer['customer_id'] = $possibleCustomer['customer_id'] ?? 0;
        $actualCustomer['email'] = $this->invoiceCustomer->email;
        $actualCustomer['name'] = $name;

        if ($possibleCustomer && is_array($possibleCustomer) && count($possibleCustomer) > 0) {
            if (!$number || (int)$number !== 9990) {
                if (defined('UPDATE_CUSTOMER') && UPDATE_CUSTOMER) {
                    $values = [];

                    $values['customer_id'] = $possibleCustomer['customer_id'];
                    $values['name'] = $name;
                    $values['language_id'] = $this->getCustomerLanguageCode();
                    $values['address'] = $this->invoiceCustomer->address1 . ((!empty($this->invoiceCustomer->address2)) ? " - " . $this->invoiceCustomer->address2 : "");
                    $values['zip_code'] = $this->getZipValidated($this->invoiceCustomer->postcode, $this->invoiceCustomer->country);
                    $values['city'] = $this->invoiceCustomer->city;
                    $values['country_id'] = $this->getCustomerCountryCode();
                    $values['email'] = $this->invoiceCustomer->email;
                    $values['phone'] = $this->invoiceCustomer->phonenumber;

                    try {
                        Customers::update($values);
                    } catch (APIException $e) {
                        throw new DocumentException($e->getMessage(), $e->getData(), $e->getWhere());
                    }

                    $actualCustomer['customer_id'] = $possibleCustomer['customer_id'];
                }
            }
        } else {
            $MoloniCustomer = [];
            $MoloniCustomer['vat'] = $vat;
            $MoloniCustomer['number'] = Customers::getNextNumber();

            $MoloniCustomer['name'] = $name;
            $MoloniCustomer['email'] = $this->invoiceCustomer->email;
            $MoloniCustomer['phone'] = $this->invoiceCustomer->phonenumber;

            $MoloniCustomer['address'] = $this->invoiceCustomer->address1 . ((!empty($this->invoiceCustomer->address2)) ? " - " . $this->invoiceCustomer->address2 : "");
            $MoloniCustomer['zip_code'] = $this->getZipValidated($this->invoiceCustomer->postcode, $this->invoiceCustomer->country);
            $MoloniCustomer['city'] = $this->invoiceCustomer->city;

            $MoloniCustomer['country_id'] = $this->getCustomerCountryCode();
            $MoloniCustomer['language_id'] = $this->getCustomerLanguageCode();

            $MoloniCustomer['maturity_date_id'] = ((defined('MATURITY_DATE') && !empty(MATURITY_DATE)) ? MATURITY_DATE : $this->company['maturity_date_id']);
            $MoloniCustomer['payment_method_id'] = ((defined('PAYMENT_METHOD') && !empty(PAYMENT_METHOD)) ? PAYMENT_METHOD : $this->company['payment_method_id']);
            $MoloniCustomer['qty_copies_document'] = $this->company['docs_copies'];
            $MoloniCustomer['delivery_method_id'] = $this->company['delivery_method_id'];

            $MoloniCustomer['salesman_id'] = "0";
            $MoloniCustomer['payment_day'] = "0";
            $MoloniCustomer['discount'] = "0";
            $MoloniCustomer['credit_limit'] = "0";

            try {
                $insertClient = Customers::insert($MoloniCustomer);
            } catch (APIException $e) {
                throw new DocumentException($e->getMessage(), $e->getData(), $e->getWhere());
            }

            $actualCustomer['customer_id'] = $insertClient['customer_id'];
        }

        $this->documentData['customer_id'] = (int)$actualCustomer['customer_id'];
        $this->documentCustomer = $actualCustomer;

        return $this;
    }

    /**
     * Set document products
     *
     * @return CreateDocumentFromInvoice
     *
     * @throws DocumentException
     */
    private function defineProducts(): CreateDocumentFromInvoice
    {
        $this->documentData['products'] = [];
        $x = 0;

        foreach ($this->invoiceItems as $item) {
            $settingsProducts = new Settings($item, $this->invoiceId);
            $invoicedItem = $settingsProducts->buildProduct();

            if (isset($invoicedItem['skip']) && $invoicedItem['skip']) {
                $invoicedItem['skip'] = false;
            } elseif (isset($invoicedItem['massPay']) && $invoicedItem['massPay']) {
                $this->hasMassPay = true;
            } else {
                $this->documentData['products'][$x]['product_id'] = $this->getParsedProduct($invoicedItem, $item);
                $this->documentData['products'][$x]['name'] = $invoicedItem['name'];
                $this->documentData['products'][$x]['summary'] = $invoicedItem['summary'];
                $this->documentData['products'][$x]['discount'] = ($invoicedItem['discount'] > 0) ? $invoicedItem['discount'] : "";
                $this->documentData['products'][$x]['qty'] = "1";

                if ((int)$this->invoice->taxrate === 0) {
                    $invoiceTaxRate = $this->invoice->taxrate2;
                } else {
                    $invoiceTaxRate = $this->invoice->taxrate;
                }

                if ($this->fullCurrency['same_curr']) {
                    $productPrice = $item->amount;
                } else {
                    $productPrice = $item->amount * $this->fullCurrency['exchange_value_product'];
                }

                if (defined('REMOVE_TAX') && REMOVE_TAX) {
                    $this->documentData['products'][$x]['price'] = $productPrice - (($productPrice / (100 + $invoiceTaxRate)) * $invoiceTaxRate);
                } else {
                    $this->documentData['products'][$x]['price'] = $productPrice;
                }

                $this->documentData['products'][$x]['order'] = $x;

                if ($item->taxed == 1 && !empty((float)$invoiceTaxRate)) {
                    try {
                        $this->documentData['products'][$x]['taxes'][0]['tax_id'] = Taxes::check($invoiceTaxRate, $this->fiscalCountry);
                    } catch (APIException $e) {
                        throw new DocumentException($e->getMessage(), $e->getData(), $e->getWhere());
                    }

                    $this->documentData['products'][$x]['taxes'][0]['value'] = round($productPrice * ($invoiceTaxRate / 100));
                } elseif (defined('EXEMPTION_REASON') && !empty(EXEMPTION_REASON)) {
                    $this->documentData['products'][$x]['exemption_reason'] = EXEMPTION_REASON;
                } else {
                    throw new DocumentException("Não existe razão de isenção selecionada", [], 'Produtos');
                }

                unset($invoicedItem);
                $x++;
            }
        }

        return $this;
    }

    /**
     * Set document payment method
     *
     * @return void
     *
     * @throws DocumentException
     */
    private function definePayment()
    {
        if (!defined('DOCUMENT_TYPE') || !DocumentType::hasPayments(DOCUMENT_TYPE)) {
            return;
        }

        $orderTotal = (float)$this->invoice->total;
        $orderGateway = $this->invoice->paymentmethod;

        if (empty($orderGateway)) {
            return;
        }

        $gateway = WhmcsDB::getGatewayInfo($orderGateway);

        if (empty($gateway) || empty($gateway->value)) {
            return;
        }

        $paymentMethodId = PaymentMethods::searchByName($gateway->value);

        if (empty($paymentMethodId)) {
            try {
                $mutation = PaymentMethods::insert($gateway->value);
            } catch (APIException $e) {
                throw new DocumentException($e->getMessage(), $e->getData(), $e->getWhere());
            }

            $paymentMethodId = $mutation['payment_method_id'];
        }

        $this->documentData['payments'] = [];
        $this->documentData['payments'][] = [
            'payment_method_id' => $paymentMethodId,
            'date' => date('Y-m-d H:i:s'),
            'value' => $orderTotal
        ];
    }

    //          SETS          //

    public function setIsHook(bool $isHook)
    {
        $this->isHook = $isHook;
    }

    //          GETS          //

    /**
     * Get created document ID
     *
     * @return int
     */
    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    /**
     * Get current invoice
     *
     * @return mixed
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    //          PRIVATES          //

    /**
     * Load company info
     *
     * @return void
     */
    private function loadMoloniCompany()
    {
        $this->company = Companies::companyMe();
    }

    /**
     * Load WHMCS invoice
     *
     * @throws DocumentException
     */
    private function loadInvoice(): CreateDocumentFromInvoice
    {
        $this->invoice = WhmcsDB::getInvoice($this->invoiceId);

        if (empty($this->invoice)) {
            throw new DocumentException("Documento não foi encontrado/já foi gerado", [], 'Documento não existe');
        }

        return $this;
    }

    /**
     * Load WHMCS invoice data
     *
     * @throws DocumentException
     */
    private function loadInvoiceData(): CreateDocumentFromInvoice
    {
        $this->invoiceData = WhmcsDB::getInvoiceData($this->invoiceId);

        if (empty($this->invoiceData)) {
            throw new DocumentException("Dados do documento não foram encontrados", [], 'Dados do documento não existe');
        }

        return $this;
    }

    /**
     * Load WHMCS invoice data
     *
     * @throws DocumentException
     */
    private function loadInvoiceItems(): CreateDocumentFromInvoice
    {
        $this->invoiceItems = WhmcsDB::getInvoiceItems($this->invoiceId);

        if (empty($this->invoiceItems)) {
            throw new DocumentException("Documento não tem produtos", [], 'Produtos');
        }

        return $this;
    }

    /**
     * Load invoice customer
     *
     * @throws DocumentException
     */
    private function loadInvoiceCustomer(): CreateDocumentFromInvoice
    {
        $this->invoiceCustomer = WhmcsDB::getCustomer($this->invoice->userid);

        if (empty($this->invoiceCustomer)) {
            throw new DocumentException("Cliente do documento não existe", [], 'Cliente');
        }

        return $this;
    }

    //          AUXILIARY          //

    private function getCustomerCountryCode(): int
    {
        $iso2 = strtolower($this->invoiceCustomer->country);

        if ($iso2 === 'gb') {
            return 174;
        }

        $countries = Countries::getAll();

        $targetCountries = [];

        foreach ($countries as $moloniCountry) {
            if ($iso2 == strtolower($moloniCountry['iso_3166_1'])) {
                $targetCountries[] = $moloniCountry;
            }
        }

        /** Early return */
        if (empty($targetCountries)) {
            return 0;
        }

        /** Return the only one found */
        if (count($targetCountries) === 1) {
            return (int)$targetCountries[0]['country_id'];
        }

        $region = strtolower($this->invoiceCustomer->state);

        /** Try to find the best match */
        foreach ($targetCountries as $targetCountry) {
            foreach ($targetCountry['languages'] as $language) {
                if ($region === strtolower($language['name'])) {
                    return (int)$targetCountry['country_id'];
                }
            }
        }

        /** Fallback */
        return (int)$targetCountries[0]['country_id'];
    }

    private function getCustomerLanguageCode(): int
    {
        $iso2 = $this->invoiceCustomer->country;

        if ($iso2 === 'PT' || $iso2 === 'BR') {
            $languageId = 1;
        } else {
            $country_spanish = [
                'MX', 'CO', 'ES', 'AR', 'PE', 'VE', 'CL', 'EC',
                'GT', 'CU', 'BO', 'DO', 'HN', 'PY', 'SV', 'NI',
                'CR', 'PA', 'UY', 'PR', 'GQ'
            ];

            $languageId = in_array($iso2, $country_spanish, true) ? 3 : 2;
        }

        return $languageId;
    }

    private function getCustomerCurrencyCode(): array
    {
        $currency = WhmcsDB::getCustomerCurrency($this->invoiceCustomer->currency);

        $fullCurrency = [];
        $currencyCodes = Currencies::getAll();

        foreach ($currencyCodes as $currCode) {
            if (!isset($currCode['iso4217'])) {
                continue;
            }

            if (strtoupper($currency->code) == $currCode['iso4217']) {
                $fullCurrency['whmcs_curr'] = $currCode['currency_id'];
            }
        }

        if (empty($fullCurrency['whmcs_curr'])) {
            $fullCurrency['whmcs_curr'] = 0;
        } else {
            $fullCurrency['moloni_curr'] = $this->company['currency']['currency_id'];

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

    private function getZipValidated($zipCode = '', $country = "PT")
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
                return $zipCode;
            }

            return "1000-100";
        }

        return $zipCode;
    }

    /**
     * Get Moloni product
     *
     * @throws DocumentException
     */
    private function getParsedProduct($productDefined, $item)
    {
        $reference = $productDefined['reference'];
        $productExists = Products::getByReference($reference);

        if ($productExists && is_array($productExists) && $productExists['product_id'] > 0) {
            return $productExists['product_id'];
        }

        $product = [];

        try {
            $product['category_id'] = Categories::check("WHMCS");
        } catch (APIException $e) {
            throw new DocumentException($e->getMessage(), $e->getData(), $e->getWhere());
        }

        if (isset($productDefined['type']) && in_array($productDefined['type'], [0, 1, 2])) {
            $product['type'] = (int)$productDefined['type'];
        } else {
            $product['type'] = (defined('AT_CATEGORY') && AT_CATEGORY === "SS") ? 2 : 1;
        }

        $product['name'] = $productDefined['name'];
        $product['summary'] = $productDefined['summary'];
        $product['reference'] = $reference;
        $product['ean'] = "";

        if ((int)$this->invoice->taxrate === 0) {
            $invoiceTaxRate = $this->invoice->taxrate2;
        } else {
            $invoiceTaxRate = $this->invoice->taxrate;
        }

        if ($this->fullCurrency['same_curr']) {
            $productPrice = $item->amount;
        } else {
            $productPrice = $item->amount * $this->fullCurrency['exchange_value_product'];
        }

        if (defined('REMOVE_TAX') && REMOVE_TAX) {
            $product['price'] = $productPrice - (($productPrice / (100 + $invoiceTaxRate)) * $invoiceTaxRate);
        } else {
            $product['price'] = $productPrice;
        }

        if (!defined('MEASURE_UNIT') || empty(MEASURE_UNIT)) {
            throw new DocumentException('Não possui unidade de medida selecionada!', [], 'Produto');
        }

        $product['unit_id'] = MEASURE_UNIT;
        $product['has_stock'] = $product['type'] === 1 ? 1 : 0;
        $product['warehouse_id'] = 0;

        $product['stock'] = "0";
        $product['pos_favorite'] = "0";
        $product['at_product_category'] = (defined('AT_CATEGORY') && !empty(AT_CATEGORY)) ? AT_CATEGORY : '';

        if ((int)$this->invoice->taxrate === 0 && (int)$this->invoice->taxrate2 === 0) {
            if (!defined('EXEMPTION_REASON') || empty(EXEMPTION_REASON)) {
                throw new DocumentException('Não possui razão de isenção selecionada!', [], 'Produto');
            }

            $product['exemption_reason'] = EXEMPTION_REASON;
        } else {
            try {
                $product['taxes'][0]['tax_id'] = Taxes::check($invoiceTaxRate, $this->fiscalCountry);
            } catch (APIException $e) {
                throw new DocumentException($e->getMessage(), $e->getData(), $e->getWhere());
            }

            $product['taxes'][0]['value'] = round($productPrice * ($invoiceTaxRate / 100));
            $product['taxes'][0]['order'] = "0";
            $product['taxes'][0]['cumulative'] = "0";
        }

        try {
            $mutation = Products::insert($product);
        } catch (APIException $e) {
            throw new DocumentException($e->getMessage(), $e->getData(), $e->getWhere());
        }

        return $mutation['product_id'];
    }

    //          MESSAGES          //

    /**
     * Add success message
     *
     * @param string|null $message
     * @param int|null $documentId
     * @param string|null $downloadURL
     *
     * @return void
     */
    private function successMessage($message = '', $documentId = 0, $downloadURL = '')
    {
        $moloniURL = '';

        if (!empty($documentId)) {
            $moloniURL = "https://www.moloni.pt/" . $this->company['slug'] . "/" . Documents::getDocumentType() . "/showDetail/" . $documentId . "/";
        }

        Error::success($message, $moloniURL, $downloadURL);
    }

    //          DATABASE          //

    /**
     * Create entry in documents table
     */
    private function createDocumentEntry(array $data, int $value)
    {
        WhmcsDB::insertMoloniInvoice($this->invoice, $data, $value);
    }

    /**
     * Create info entry in documents table
     */
    private function createInfoDocumentLog(string $msg)
    {
        $data = [
            'tag' => 'service:document:create:success',
            'isHook' => $this->isHook ? 1 : 0,
            'invoiceId' => $this->invoiceId,
            'invoice' => $this->invoice,
            'document' => $this->documentData,
        ];

        if ($this->isHook) {
            $msg .= ' (automático)';
        } else {
            $msg .= ' (manual)';
        }

        LoggerFacade::info($msg, $data);
    }

    /**
     * Create warnign entry in documents table
     */
    private function createWarningDocumentLog(string $msg)
    {
        $data = [
            'tag' => 'service:document:create:warning',
            'isHook' => $this->isHook ? 1 : 0,
            'invoiceId' => $this->invoiceId,
            'invoice' => $this->invoice,
            'document' => $this->documentData,
        ];

        if ($this->isHook) {
            $msg .= ' (automático)';
        } else {
            $msg .= ' (manual)';
        }

        LoggerFacade::warning($msg, $data);
    }
}
