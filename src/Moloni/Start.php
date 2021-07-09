<?php

namespace Moloni;

use Moloni\Model\WhmcsDB;

class Start
{
    public $template = 'index';
    public $message = "";

    public function __construct()
    {
        $moloni = WhmcsDB::getMoloniFirst();

        if ($moloni) {
            define("ACCESS", $moloni->access_token);
            define("COMPANY", $moloni->company_id);
            define("REFRESH", $moloni->refresh_token);
            define("DATE_EXPIRE", $moloni->date_expire);
        }
    }

    public function clearMoloniTokens()
    {
        WhmcsDB::clearMoloniTokens();

        return true;
    }

    public function setTokens($access = '', $refresh = '')
    {
        WhmcsDB::setMoloniTokens($access, $refresh);

        return true;
    }

    public function updateTokens($access = '', $refresh = '')
    {
        WhmcsDB::updateMoloniTokens($access, $refresh);

        return true;
    }

    public function setCompanyId($companyId)
    {
        WhmcsDB::setMoloniCompanyId($companyId);
        return true;
    }

    public function variablesDefine()
    {
        WhmcsDB::variablesDefineMoloni();
        return true;
    }

    public function variablesUpdate()
    {
        WhmcsDB::variablesUpdateMoloni();
        $this->variablesDefine();

        return true;
    }
}