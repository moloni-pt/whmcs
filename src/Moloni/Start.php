<?php

namespace Moloni;

use Moloni\Core\Storage;
use Moloni\Facades\LoggerFacade;
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

    //           PUBLICS           //

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

    //           PRIVATES           //

    private function refreshTokens()
    {
        $newtokens = Curl::refresh(Storage::$MOLONI_REFRESH_TOKEN);

        if (empty($newtokens['access_token']) || empty($newtokens['refresh_token'])) {
            $msg = 'Erro atualizar tokens.';
            $data = [
                'tag' => 'service:token:refresh',
                'response' => $newtokens
            ];

            LoggerFacade::info($msg, $data);
            Error::create('Login', $msg);

            return false;
        }

        Storage::$MOLONI_ACCESS_TOKEN = $newtokens['access_token'];
        Storage::$MOLONI_REFRESH_TOKEN = $newtokens['refresh_token'];

        $this->updateTokens($newtokens['access_token'], $newtokens['refresh_token']);

        return true;
    }

    //           VERIFICATIONS           //

    public function hasValidCompany()
    {
        return !empty(Storage::$MOLONI_COMPANY_ID);
    }

    public function hasValidAuthentication()
    {
        if (empty(Storage::$MOLONI_ACCESS_TOKEN) || empty(Storage::$MOLONI_REFRESH_TOKEN)) {
            return false;
        }

        if ($this->isValidAccessToken()) {
            return true;
        }

        return $this->isValidRefreshToken() && $this->refreshTokens();
    }

    private function isValidAccessToken()
    {
        return time() < strtotime(Storage::$MOLONI_DATE_EXPIRE);
    }

    private function isValidRefreshToken()
    {
        return time() < strtotime('+13 days', strtotime(Storage::$MOLONI_DATE_EXPIRE));
    }
}
