<?php

use Moloni\Start;
use Moloni\Facades\LoggerFacade;
use Moloni\Exceptions\DocumentException;
use Moloni\Exceptions\DocumentWarning;
use Moloni\Services\Invoices\CreateDocumentFromInvoice;

add_hook("InvoicePaid", 1, function ($vars) {
    require_once __DIR__ . '/vendor/autoload.php';

    $moloni = new Start();

    if (!$moloni->hasValidCompany() || !$moloni->hasValidAuthentication()) {
        return true;
    }

    $moloni->variablesDefine();

    if (defined('INVOICE_AUTO') && INVOICE_AUTO) {
        $invoiceId = (int)$vars['invoiceid'];

        try {
            $service = new CreateDocumentFromInvoice($invoiceId);
            $service->setIsHook(true);
            $service->execute();
        } catch (DocumentWarning $e) {
            // No need to catch
        } catch (DocumentException $e) {
            LoggerFacade::error($e->getMessage() . ' (automático)', [
                'tag' => 'service:document:create:error',
                'isHook' => 1,
                'invoiceId' => $invoiceId,
                'data' => $e->getData()
            ]);
        } catch (Exception $e) {
            LoggerFacade::error('Erro fatal. (automático)', [
                'tag' => 'service:document:create:fatalerror',
                'isHook' => 1,
                'invoiceId' => $invoiceId,
                'message' => $e->getMessage()
            ]);
        }
    }

    return true;
});
