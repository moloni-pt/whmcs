<?php

namespace Moloni;

use Moloni\Core\Storage;
use Moloni\Model\WhmcsDB;

class Start
{
    public $template = 'index';
    public $message = "";

    public function __construct()
    {
        $moloni = WhmcsDB::getMoloniFirst();

        if ($moloni) {
            Storage::$MOLONI_ACCESS_TOKEN = $moloni->access_token;
            Storage::$MOLONI_REFRESH_TOKEN = $moloni->refresh_token;
            Storage::$MOLONI_DATE_EXPIRE = $moloni->date_expire;
            Storage::$MOLONI_COMPANY_ID = $moloni->company_id;
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