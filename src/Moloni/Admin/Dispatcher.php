<?php

namespace Moloni\Admin;

use Exception;
use Moloni\Core\Storage;
use Moloni\Curl;
use Moloni\Exceptions\DocumentException;
use Moloni\Exceptions\DocumentWarning;
use Moloni\Facades\LoggerFacade;
use Moloni\Model\WhmcsDB;
use Moloni\Services\Invoices\CreateDocumentFromInvoice;
use Moloni\Services\Logs\DeleteLogs;
use Moloni\Services\Logs\FetchLogs;
use Moloni\Start;
use Moloni\Error;

class Dispatcher
{
    /** @var Start */
    protected $moloni;
    protected $template = 'login';
    protected $message = [];

    public function dispatch($parameters)
    {
        $this->moloni = new Start();
        $this->actionDecide();

        $templatePath = MOLONI_TEMPLATE_PATH . $this->template . '.php';

        if (file_exists($templatePath)) {
            require_once $templatePath;
            return true;
        }

        echo 'Failed loading template ' . $templatePath;
        return false;
    }

    public function dispatchAjax()
    {
        $this->moloni = new Start();

        switch ($_GET['action']) {
            case 'logs':
                $response = $this->logsPageAjax();

                break;
            default:
                $response = [];

                break;
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        die();
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
                    $this->settingsPage();

                    return true;
                case "invoice":
                    $this->invoicePage();

                    break;
                case 'docs':
                    $this->documentsPage();

                    return true;
                case 'logs':
                    $this->logsPage();

                    return true;
                case 'logout':
                    $this->logoutPage();

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

    //            Pages            //

    private function settingsPage()
    {
        if (isset($_GET['command']) && $_GET['command'] === "save") {
            $this->moloni->variablesUpdate();

            $msg = 'Configurações guardadas com sucesso.';

            $data = $_POST;
            $data['token'] = '';
            $data['tag'] = 'manual:settings:save';

            LoggerFacade::info($msg, $data);
            Error::success($msg);

            $this->template = 'index';
        } else {
            $this->template = 'config';
        }

        $this->moloni->variablesDefine();
    }

    private function invoicePage()
    {
        $this->moloni->variablesDefine();

        $orderId = (int)$_GET['id'];

        if (isset($_GET['command']) && $_GET['command'] === "gen") {
            try {
                $service = new CreateDocumentFromInvoice($orderId);
                $service->execute();
            } catch (DocumentWarning $e) {
                Error::warning($e->getMessage());
            } catch (DocumentException $e) {
                Error::create(
                    $e->getWhere(),
                    $e->getMessage(),
                    $e->getData()
                );

                LoggerFacade::error($e->getMessage() . ' (manual)', [
                    'tag' => 'service:document:create:error',
                    'isHook' => 0,
                    'invoiceId' => $orderId,
                    'data' => $e->getData()
                ]);
            } catch (Exception $e) {
                Error::create('Geral', $e->getMessage());

                LoggerFacade::error('Erro fatal. (manual)', [
                    'tag' => 'service:document:create:fatalerror',
                    'isHook' => 0,
                    'invoiceId' => $orderId,
                    'message' => $e->getMessage()
                ]);
            }

            return;
        }

        if ($this->deleteInvoice($orderId)) {
            $msg = 'Encomenda apagada com sucesso.';
            $data = [
                'tag' => 'manual:invoice:discard',
                'id' => $orderId
            ];

            LoggerFacade::info($msg, $data);
            Error::success($msg);
        }
    }

    private function documentsPage()
    {
        if (isset($_GET['command']) && $_GET['command'] === "redo") {
            $orderId = (int)$_GET['id'];

            $this->redoInvoice($orderId);

            $msg = 'Documento revertido com sucesso.';
            $data = [
                'tag' => 'manual:invoice:redo',
                'id' => $orderId
            ];

            LoggerFacade::info($msg, $data);
            Error::success($msg);
        }

        $this->moloni->variablesDefine();
        $this->template = 'document';
    }

    private function logsPage()
    {
        if (isset($_GET['command']) && $_GET['command'] === "delete") {
            $service = new DeleteLogs();
            $service->run();
            $service->saveLog();

            Error::success('Registos antigos eliminados com sucesso');
        }

        $this->template = 'logs';
    }

    private function logoutPage()
    {
        $msg = 'Logout manual efetuado.';
        $data = [
            'tag' => 'manual:logout'
        ];

        WhmcsDB::clearMoloniTokens();
        LoggerFacade::info($msg, $data);

        $this->template = 'login';
    }

    //            Pages Ajax request           //

    private function logsPageAjax()
    {
        $service = new FetchLogs($_GET);

        return $service->run();
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
