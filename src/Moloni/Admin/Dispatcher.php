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

    //            Privates            //

    private function actionDecide()
    {
        // Caso sejam enviados dados de “login”
        if (isset($_REQUEST['mol-username'], $_REQUEST['mol-password'])) {
            $this->tryLogin();

            return true;
        }

        if ($this->moloni->hasValidAuthentication()) {
            if (!$this->moloni->hasValidCompany()) {
                if (isset($_REQUEST['company_id'])) {
                    $this->moloni->setCompanyId($_REQUEST['company_id']);
                } else {
                    $this->template = 'company';

                    return true;
                }
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
        $loginResult = Curl::login($_REQUEST['mol-username'], $_REQUEST['mol-password']);

        if (empty($loginResult['access_token']) || empty($loginResult['refresh_token'])) {
            $this->parseLoginError($loginResult);
        } else {
            Storage::$MOLONI_ACCESS_TOKEN = $loginResult['access_token'];
            Storage::$MOLONI_REFRESH_TOKEN = $loginResult['refresh_token'];

            $this->moloni->clearMoloniTokens();
            $this->moloni->setTokens($loginResult['access_token'], $loginResult['refresh_token']);

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

    //            Auxiliary            //

    private function parseLoginError($loginResult)
    {
        if (!in_array('curl', get_loaded_extensions())) {
            $this->message = [
                "text" => 'Biblioteca cURL desativada.',
            ];

            return;
        }

        if (is_array($loginResult) && isset($loginResult['error'])) {
            if ($loginResult['error'] === 'invalid_grant') {
                $message = 'Combinação errada.';

                if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $_REQUEST['mol-password'])) {
                    $message .= ' Caso o problema persista, remova caracteres especiais para evitar problemas de codificação.';
                } else {
                    $message .= ' Tente novamente.';
                }

                $this->message = [
                    "text" => $message
                ];

                return;
            }

            if ($loginResult['error'] === 'invalid_request') {
                $this->message = [
                    "text" => 'Pedido inválido.',
                    "data" => json_encode($loginResult, JSON_PRETTY_PRINT)
                ];

                return;
            }
        }

        $message = 'Erro desconhecido. Caso o problema persista, confirme se o IP está bloqueado junto do apoio Moloni.';
        $data = [
            'IP' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '',
            'result' => is_array($loginResult) ? $loginResult : []
        ];

        $this->message = [
            "text" => $message,
            "data" => json_encode($data, JSON_PRETTY_PRINT)
        ];
    }
}
