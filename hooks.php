<?php

use Moloni\Start;
use Moloni\General;
use WHMCS\Database\Capsule;

add_hook("InvoicePaid", 1, function ($vars) {

    require_once __DIR__ . '/vendor/autoload.php';

    $moloni = new Start();
    $moloni->variablesDefine();
    $general = new General();
    if (defined('INVOICE_AUTO') && INVOICE_AUTO) {
        $general->createInvoice($vars['invoiceid']);
    }
    return true;
});
