<?php

namespace Moloni\Enums;

class DocumentType
{
    const INVOICES = 'invoices';
    const INVOICE_RECEIPTS = 'invoiceReceipts';
    const ESTIMATES = 'estimates';
    const BILLS_OF_LADING = 'billsOfLading';

    const TYPES_WITH_PAYMENTS = [
        self::INVOICE_RECEIPTS,
    ];

    const TYPES_REQUIRES_DELIVERY = [
        self::BILLS_OF_LADING,
    ];

    public static function hasPayments($documentType = '')
    {
        return in_array($documentType, self::TYPES_WITH_PAYMENTS, true);
    }

    public static function requiresDelivery($documentType = '')
    {
        return in_array($documentType, self::TYPES_REQUIRES_DELIVERY, true);
    }

    public static function getDocumentTypeForRender()
    {
        return [
            self::INVOICES => 'Fatura',
            self::INVOICE_RECEIPTS => 'Fatura/Recibo',
            self::ESTIMATES => 'Orçamento',
            self::BILLS_OF_LADING => 'Guia de Transporte',
        ];
    }
}
