<?php


namespace Moloni;

use Moloni\Model\WhmcsDB;

class Settings
{
    private $item;
    private $invoice_id;

    public function __construct($item = null, $invoice_id = null)
    {
        $this->item = $item;
        $this->invoice_id = $invoice_id;
    }

    public function buildProduct()
    {
        switch ($this->item->type) {
            case "DomainTransfer":
                $domainInfo = WhmcsDB::getDomainInfo($this->item->relid);
                $discountValue = WhmcsDB::getDomainDiscount($this->invoice_id, $this->item->relid);
                list($domain, $tld) = explode('.', $domainInfo->domain, 2);

                $invoicedItem['name'] = "Transferência de Domínio";
                $invoicedItem['summary'] = $domainInfo->domain;
                $invoicedItem['reference'] = "T-" . strtoupper($tld);
                $invoicedItem['discount'] = ($discountValue > 0) ? round(($discountValue * 100) / $this->item->amount) : "";
                $invoicedItem['type'] = 2;
                break;
            case "DomainRegister":
                $domainInfo = WhmcsDB::getDomainInfo($this->item->relid);
                $discountValue = WhmcsDB::getDomainDiscount($this->invoice_id, $this->item->relid);
                list($domain, $tld) = explode('.', $domainInfo->domain, 2);

                $invoicedItem['name'] = "Registo de Domínio";
                $invoicedItem['summary'] = $domainInfo->domain . "<br>" . $this->item->duedate . " - " . $domainInfo->nextduedate;
                $invoicedItem['reference'] = "REG-" . strtoupper($tld);
                $invoicedItem['discount'] = ($discountValue > 0) ? round(($discountValue * 100) / $this->item->amount) : "";
                $invoicedItem['type'] = 2;
                break;
            case "Domain":
                $domainInfo = WhmcsDB::getDomainInfo($this->item->relid);
                $discountValue = WhmcsDB::getDomainDiscount($this->invoice_id, $this->item->relid);
                list($domain, $tld) = explode('.', $domainInfo->domain, 2);

                $invoicedItem['name'] = "Renovação de Domínio";
                $invoicedItem['summary'] = $domainInfo->domain . "<br>" . $this->item->duedate . " - " . $domainInfo->nextduedate;
                $invoicedItem['reference'] = "REN-" . strtoupper($tld);
                $invoicedItem['discount'] = ($discountValue > 0) ? round(($discountValue * 100) / $this->item->amount) : "";
                $invoicedItem['type'] = 2;
                break;

            case "Addon":
                $addonsInfo = WhmcsDB::getAddonInfo($this->item->relid);
                $invoicedItem['name'] = $addonsInfo->name;
                $invoicedItem['summary'] = $addonsInfo->domain . "<br>" . $this->item->duedate . " - " . $addonsInfo->nextduedate;
                $invoicedItem['reference'] = $this->getReferenceByName($addonsInfo->name);
                break;


            case "Upgrade":
                $hostingInfo = WhmcsDB::getHostingInfo($this->item->relid);

                $invoicedItem['name'] = "Upgrade/Downgrade - " . $hostingInfo->name;
                $invoicedItem['summary'] = $hostingInfo->domain . "<br>" . $this->item->duedate . " - " . $hostingInfo->nextduedate;
                $invoicedItem['reference'] = "UPGRADE";
                $invoicedItem['type'] = 2;
                break;

            case "Hosting":
                $hostingInfo = WhmcsDB::getHostingInfo($this->item->relid);
                $discountValue = WhmcsDB::getHostingDiscount($this->invoice_id, $this->item->relid);
                $customValue = WhmcsDB::getCustomFieldDescriptionProduct($hostingInfo->packageid);

                $invoicedItem['name'] = $hostingInfo->name;
                $invoicedItem['summary'] = $hostingInfo->domain . "<br>" . $this->item->duedate . " - " . $hostingInfo->nextduedate;
                $invoicedItem['reference'] = !empty($customValue) ? $customValue : "Alojamento";
                $invoicedItem['discount'] = ($discountValue > 0) ? round(($discountValue * 100) / $this->item->amount) : "";
                $invoicedItem['type'] = 2;

                break;

            case "Setup":
                $invoicedItem['name'] = 'Taxa de Instalação';
                $invoicedItem['summary'] = $this->item->description;
                $invoicedItem['reference'] = 'TAX-INSTALL';
                $invoicedItem['type'] = 2;
                break;

            case "Invoice":
                $invoicedItem['massPay'] = true;
                break;

            case "":
                $invoicedItem['name'] = $this->item->description;
                $invoicedItem['summary'] = "";
                $invoicedItem['reference'] = "9999";
                $invoicedItem['discount'] = "";
                break;

            default:
                $invoicedItem['skip'] = true;

        }

        return $invoicedItem;
    }

    private function getReferenceByName($name)
    {
        $reference = '';
        $numbersCharacters = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
        $nameFixed = explode(" ", $numbersCharacters);

        if (!empty($nameFixed) && is_array($nameFixed)) {
            foreach ($nameFixed as $word) {
                $reference .= substr($word, 0, 3) . '-';
            }
        }

        return (substr($reference, 0, -1));
    }
}
