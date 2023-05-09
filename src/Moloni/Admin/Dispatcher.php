<?php

namespace Moloni\Admin;

use Moloni\Core\Storage;
use Moloni\Curl;
use Moloni\Model\WhmcsDB;
use Moloni\Start;
use Moloni\General;
use Moloni\Error;

class Dispatcher
{
    /** @var Start */
    protected $moloni;
    protected $template = 'login';
    protected $general;
    protected $message = [];

    public function dispatch($parameters)
    {
        $this->moloni = new Start();
        $this->general = new General();
        $this->actionDecide();

        $templatePath = MOLONI_TEMPLATE_PATH . $this->template . '.php';
        if (file_exists($templatePath)) {
            require_once $templatePath;
            return true;
        }

        echo 'Failed loading template ' . $templatePath;
        return false;
    }

    private function actionDecide()
    {
        // Caso sejam enviados dados de login
        if (isset($_REQUEST['mol-username'], $_REQUEST['mol-password'])) {
            $this->tryLogin();
            return true;
        }

        if (!empty(Storage::$MOLONI_ACCESS_TOKEN) && !empty(Storage::$MOLONI_REFRESH_TOKEN)) {
            $date_expire = strtotime(Storage::$MOLONI_DATE_EXPIRE);

            if (time() > $date_expire) {
                if (time() > strtotime('+14 days', $date_expire)) {
                    Error::create('Login', 'Refresh token expirou');

                    $this->moloni->clearMoloniTokens();
                    $this->template = 'login';

                    return true;
                }

                $newtokens = Curl::refresh(Storage::$MOLONI_REFRESH_TOKEN);

                /** Refresh requerst failed */
                if (empty($newtokens['access_token']) || empty($newtokens['refresh_token'])) {
                    Error::create('Login', 'Erro atualizar tokens');

                    $this->moloni->clearMoloniTokens();
                    $this->template = 'login';

                    return true;
                }

                $this->moloni->updateTokens($newtokens['access_token'], $newtokens['refresh_token']);
            }

            if (isset($_REQUEST['company_id'])) {
                $this->moloni->setCompanyId($_REQUEST['company_id']);
            }

            if (empty(Storage::$MOLONI_COMPANY_ID) && !isset($_REQUEST['company_id'])) {
                $this->template = 'company';
                return true;
            }

            switch ($_GET['action']) {
                case "config":
                    if (isset($_GET['command']) && $_GET['command'] === "save") {
                        $this->moloni->variablesUpdate();
                        Error::success("Configurações guardadas com sucesso");
                        break;
                    }

                    $this->moloni->variablesDefine();
                    $this->template = 'config';
                    return true;
                case "invoice":
                    $this->moloni->variablesDefine();
                    if (isset($_GET['command']) && $_GET['command'] === "gen") {
                        $this->general->createInvoice($_GET['id']);
                    } elseif ($this->deleteInvoice($_GET['id'])) {
                        Error::success('Encomenda apagada com sucesso');
                    }
                    break;
                case 'docs':
                    if (isset($_GET['command']) && $_GET['command'] === "redo") {
                        $this->redoInvoice($_GET['id']);
                        Error::success('Documento revertido com sucesso');
                    }
                    $this->moloni->variablesDefine();
                    $this->template = 'document';
                    return true;
                case 'logout':
                    WhmcsDB::clearMoloniTokens();
                    $this->template = 'login';
                    return true;
            }

            $this->moloni->variablesDefine();
            $this->template = 'index';

            return true;
        }

        WhmcsDB::clearMoloniTokens();
        $this->template = "login";

        return true;
    }

    private function tryLogin()
    {
        $isValidLogin = Curl::login($_REQUEST['mol-username'], $_REQUEST['mol-password']);

        if (!$isValidLogin) {
            $this->message = [
                "label" => "login-errado",
                "text" => 'Combinação errada, tente novamente'
            ];
        } else {
            Storage::$MOLONI_ACCESS_TOKEN = $isValidLogin['access_token'];
            Storage::$MOLONI_REFRESH_TOKEN = $isValidLogin['refresh_token'];

            $this->moloni->clearMoloniTokens();
            $this->moloni->setTokens($isValidLogin['access_token'], $isValidLogin['refresh_token']);

            $this->template = 'company';
        }
    }

    private function deleteInvoice($invoice_id)
    {
        $invoiceOrder = WhmcsDB::getInvoice($invoice_id);
        $invoice = [];
        $invoice['document_id'] = -1;
        $invoice['net_value'] = 0;
        if (WhmcsDB::insertMoloniInvoice($invoiceOrder, $invoice, '-1')) {
            return true;
        }

        return false;
    }

    private function redoInvoice($invoice_id)
    {
        WhmcsDB::deleteMoloniInvoice($invoice_id);
        return true;
    }
}
