<?php

namespace Moloni\Model;

use Moloni\Api\Companies;
use Moloni\Api\Documents;
use Moloni\Curl;
use Moloni\Error;
use WHMCS\Database\Capsule;

class WhmcsDB
{
    // Moloni Tables --------------------------------------------------------------------------
    public static function getMoloniFirst()
    {
        return (Capsule::table('moloni')->first());
    }

    public static function clearMoloniTokens()
    {
        $pdo = Capsule::connection()->getPdo();
        $statement = $pdo->prepare('TRUNCATE moloni');
        $statement->execute();

        return true;
    }

    public static function setMoloniTokens($access = '', $refresh = '')
    {
        $pdo = Capsule::connection()->getPdo();
        $statement = $pdo->prepare('
            INSERT INTO moloni (access_token, refresh_token, date_login, date_expire, company_id) 
            VALUES (:access_token, :refresh_token, NOW(), :date_expire, 0)');

        $timeNow = time();
        $timeExpire = $timeNow + 3000;

        $statement->execute([
            ':access_token' => $access,
            ':refresh_token' => $refresh,
            ':date_expire' => date('Y-m-d H:i:s', $timeExpire)
        ]);

        return true;
    }

    public static function updateMoloniTokens($access = '', $refresh = '')
    {
        $timeNow = time();
        $timeExpire = $timeNow + 3000;
        $now = strtotime('-7 hours', $timeNow);

        $update = Capsule::table('moloni')->where('id', 1)->update([
            'access_token' => $access,
            'refresh_token' => $refresh,
            'date_login' => date('Y-m-d H:i:s', $now),
            'date_expire' => date('Y-m-d H:i:s', $timeExpire)
        ]);

        return (!empty($update));
    }

    public static function setMoloniCompanyId($company_id)
    {
        Capsule::table('moloni')->update(['company_id' => $company_id]);
        return true;
    }

    public static function variablesDefineMoloni()
    {
        foreach (Capsule::table('moloni_configs')->get() as $configs){
            if (!defined(strtoupper($configs->label))) {
                define(strtoupper($configs->label), $configs->value);
            }
        }

        return true;
    }

    public static function variablesUpdateMoloni()
    {
        $options = [];
        $options["document_set"] = "Série de documentos";
        $options["after_date"] = "Encomendas desde";
        $options["after_date_doc"] = "Documentos desde";
        $options["exemption_reason"] = "Razão de isenção";
        $options["payment_method"] = "Método de pagamento";
        $options["measure_unit"] = "Unidade de medida";
        $options["maturity_date"] = "Prazo de vencimento";
        $options["update_customer"] = "Atualizar cliente";
        $options["document_status"] = "Estado do documento";
        $options["invoice_auto"] = "Gerar automaticamente";
        $options["email_send"] = "Enviar email";
        $options["remove_tax"] = "Remover IVA";
        $options["client_prefix"] = "Prefixo do cliente";
        $options["product_prefix"] = "Prefixo do artigo";
        $options["document_type"] = "Tipo de documento";
        $options["at_category"] = "Tipo de artigo AT";
        $options["custom_reference"] = "Campo customizado Ref Produto";
        $options["custom_client"] = "Campo customizado NIF cliente";

        foreach ($_POST as $key => $value) {
            $val = (is_array($value) ? serialize($value) : $value);
            if(isset($options[$key])){
                Capsule::table('moloni_configs')->updateOrInsert(['label' => $key, 'name' => $options[$key], 'description' => ''], ['value' => $val]);
            }
        }

        return true;
    }

    public static function insertMoloniInvoice($invoiceInfo, $invoice, $value)
    {
        if(defined('DOCUMENT_STATUS')){
            $pdo = Capsule::connection()->getPdo();
            $pdo->beginTransaction();

            try {
                $statement = $pdo->prepare('
                            INSERT INTO moloni_invoices(order_id, order_total, invoice_id, invoice_total, invoice_date, invoice_status, value)
                            VALUES (:order_id, :order_total, :invoice_id, :invoice_total, NOW(), :document_status, :value)');

                $statement->execute([
                    ':order_id' => $invoiceInfo->id,
                    ':order_total' => $invoiceInfo->total,
                    ':invoice_id' => $invoice['document_id'],
                    ':invoice_total' => $invoice['net_value'],
                    ':document_status' => DOCUMENT_STATUS,
                    ':value' => $value
                ]);

                $pdo->commit();
                return true;
            } catch (\Exception $e) {
                Error::create('Documentos', 'Erro ao inserir em moloni_invoice:' . $e->getMessage());
                $pdo->rollBack();

                return false;
            }

        } else {
            Error::create('Documentos', 'Estado de documento não selecionado');
            return false;
        }
    }

    public static function deleteMoloniInvoice($invoiceInfo)
    {
        Capsule::table('moloni_invoices')->where('order_id', '=', $invoiceInfo)->delete();
    }

    // WHMCS Tables ------------------------------------------------------------------------------------------

    public static function getCustomFieldClient()
    {
        $array = array();
        foreach (Capsule::table('tblcustomfields')->distinct('fieldname')->where('type','client')->get() as $row){
            $array[] = $row;
        }

        return ($array);
    }

    public static function getCustomFieldValueClient($userId)
    {
        if(defined('CUSTOM_CLIENT') && !empty(CUSTOM_CLIENT)){

            $row = Capsule::table('tblcustomfieldsvalues')->select('value')->where('relid', $userId)->whereIn('fieldid', function ($query){
                $query->select("id")
                    ->from('tblcustomfields')
                    ->whereRaw('fieldname = "'. CUSTOM_CLIENT .'"');
            })->first();
            return ($row->value);
        }
        return false;
    }

    public static function getCustomFieldProduct()
    {
        $array = array();
        foreach (Capsule::table('tblcustomfields')->distinct('fieldname')->groupBy('fieldname')->where('type','product')->get() as $row){
            $array[] = $row;
        }

        return ($array);
    }

    public static function getCustomFieldDescriptionProduct($packageId)
    {
        if(defined('CUSTOM_REFERENCE') && !empty(CUSTOM_REFERENCE)){

            $row = Capsule::table('tblcustomfields')->select('description')->where('type','product')->where('fieldname', CUSTOM_REFERENCE)->where('relid', $packageId)->first();
            return ($row->description);
        }
        return false;
    }

    public static function getAllOrders()
    {
        $array = array();
        foreach (Capsule::table('tblinvoices')
                     ->whereNotExists(function ($query){
                         $query->select("order_id")
                             ->from('moloni_invoices')
                             ->whereRaw('moloni_invoices.order_id = tblinvoices.id');
                     })->whereIn('status', ['Paid','Unpaid','Payment Pending'])->where('date', '>=', (defined('AFTER_DATE') ? (string)AFTER_DATE : ''))->get() as $row){

            $client = self::getCustomer($row->userid);
            $array[] = [
                'invoice' => $row,
                'order' => self::getOrderByInvoice($row->id),
                'client' => $client,
                'currency' => self::getCustomerCurrency($client->currency)
            ];
        }

        return ($array);
    }

    public static function getAllDocuments()
    {
        $me = Companies::companyMe();
        $array = array();
        foreach (Capsule::table('moloni_invoices')
                     ->join('tblinvoices', 'moloni_invoices.order_id', '=', 'tblinvoices.id')
                     ->select('moloni_invoices.*', 'tblinvoices.invoicenum')
                     ->where('moloni_invoices.invoice_date', '>=', (defined('AFTER_DATE_DOC') ? (string)AFTER_DATE_DOC : ''))->get() as $row){
            if($row->value != -1){
                if((int)$row->invoice_id > 0){
                    $document = Documents::getOneInfo($row->invoice_id);
                    if($document){
                        $invoice['order_id'] = $row->order_id;
                        $invoice['invoicenum'] = $row->invoicenum;
                        $invoice['name'] = $document['entity_name'];
                        $invoice['set'] = $document['document_set']['name'];
                        $invoice['date'] = $document['date'];
                        $invoice['status'] = $document['status'];
                        $invoice['net_value'] = $document['net_value'];

                        unset($document);

                        $array[] = [
                            'invoice' => $invoice,
                            'detail' => "https://www.moloni.com/" . $me['slug'] . "/" . Documents::getDocumentType($row->invoice_id) . "/showDetail/" . $row->invoice_id . "/",
                            'download' => ($row->invoice_status == 1) ? Documents::getPDFLink($row->invoice_id) : null,
                        ];
                    }
                }
            } else {
                $invoice['order_id'] = $row->order_id;
                $invoice['invoicenum'] = $row->invoicenum;
                $invoice['name'] = 'Não gerado';
                $invoice['set'] = 'Não gerado';
                $invoice['date'] = date('c',strtotime($row->invoice_date));
                $invoice['status'] = -1;
                $invoice['net_value'] = "";

                $array[] = [
                    'invoice' => $invoice,
                    'detail' => null,
                    'download' => null,
                ];
            }
        }

        return ($array);
    }

    public static function getOneOrder($id)
    {
        $array = Capsule::table('tblorders')->where('id', $id)->first();

        return $array;
    }

    public static function getOrderByInvoice($id)
    {
        $array = Capsule::table('tblorders')->where('invoiceid', $id)->first();

        return $array;
    }

    public static function getCustomer($id)
    {
        $array = Capsule::table('tblclients')->where('id', $id)->first();

        return ($array);
    }

    public static function getInvoice($id)
    {
        $invoice = Capsule::table('tblinvoices')->where('id', $id)
            ->whereNotExists(function ($query){
                $query->select("order_id")
                    ->from('moloni_invoices')
                    ->whereRaw('moloni_invoices.order_id = tblinvoices.id');
            })
            ->first();

        if (empty($invoice)) {
            return false;
        }

        return ($invoice);
    }

    public static function getInvoiceItems($id)
    {
        $array = array();
        foreach (Capsule::table('tblinvoiceitems')->where('invoiceid', $id)->get() as $row){
            $array[] = $row;
        }

        return ($array);
    }

    public static function getDomainInfo($id)
    {
        $domain = Capsule::table('tbldomains')->where('id', $id)->first();

        return ($domain);
    }

    public static function getHostingInfo($id)
    {
        $row = Capsule::table('tblhosting')->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')->where('tblhosting.id', $id)->first();

        return ($row);
    }

    public static function getAddonInfo($id)
    {
        $row = Capsule::table('tblhostingaddons')->join('tbladdons', 'tblhostingaddons.addonid', '=', 'tbladdons.id')->join('tblhosting', 'tblhostingaddons.hostingid', '=', 'tblhosting.id')->where('tblhostingaddons.id', $id)->first();

        return ($row);
    }

    public static function getHostingDiscount($id, $relid)
    {
        $row = Capsule::table('tblinvoiceitems')->where('invoiceid', $id)->where('type', 'PromoHosting')->where('relid', $relid)->first();

        if(!empty($row)){
            return ($row->amount < 0) ? abs($row->amount) : 0;
        } else {
            return 0;
        }
    }

    public static function getDomainDiscount($id, $relid)
    {
        $row = Capsule::table('tblinvoiceitems')->where('invoiceid', $id)->where('type', 'PromoDomain')->where('relid', $relid)->first();

        if(!empty($row)){
            return ($row->amount < 0) ? abs($row->amount) : 0;
        } else {
            return 0;
        }
    }

    public static function getCustomerCurrency($id)
    {
        $row = Capsule::table('tblcurrencies')->select('code', 'prefix', 'suffix')->where('id', $id)->first();

        return $row;
    }
}