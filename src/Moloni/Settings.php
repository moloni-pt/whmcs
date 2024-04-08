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
        $invoicedItem = [];

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

                if ($addonsInfo && property_exists($addonsInfo, 'name')) {
                    $invoicedItem['name'] = $addonsInfo->name;
                    $invoicedItem['summary'] = $addonsInfo->domain . "<br>" . $this->item->duedate . " - " . $addonsInfo->nextduedate;
                    $invoicedItem['reference'] = $this->getReferenceByName($addonsInfo->name);
                } else {
                    $invoicedItem['name'] = $this->item->description;
                    $invoicedItem['summary'] = '';
                    $invoicedItem['reference'] = 'Addon';
                }

                break;


            case "Upgrade":
                $upgradeInfo = WhmcsDB::getUpgradeInfo($this->item->relid);

                $invoicedItem['name'] = "Upgrade/Downgrade - " . $upgradeInfo->name;
                $invoicedItem['summary'] = $upgradeInfo->domain . "<br>" . $this->item->duedate . " - " . $upgradeInfo->nextduedate;
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

            case "AddFunds":
                $invoicedItem['name'] = !empty($this->item->description) ? $this->item->description : 'Adição de fundos';
                $invoicedItem['summary'] = "";
                $invoicedItem['reference'] = 'ADD-FUNDS';
                $invoicedItem['type'] = 2;
                break;

            case "LateFee":
                $invoicedItem['name'] = !empty($this->item->description) ? $this->item->description : 'Taxa de atraso';
                $invoicedItem['summary'] = "";
                $invoicedItem['reference'] = 'LATE-FEE';
                $invoicedItem['type'] = 3;
                break;

            case "Invoice":
                $invoicedItem['massPay'] = true;
                break;

            case "Item":
            case "":
                $invoicedItem['name'] = $this->item->description;
                $invoicedItem['summary'] = "";
                $invoicedItem['reference'] = "9999";
                $invoicedItem['discount'] = "";

                if ($this->item->amount < 0) {
                    $invoicedItem['skip'] = true;
                }

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
