<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

$sLangName = 'English';

// -------------------------------
// RESOURCE IDENTIFIER = STRING
// -------------------------------
$aLang = [
    'charset' => 'UTF-8',
    'tbclorder_unzer' => 'Unzer',
    'OSCUNZER_PAYMENT_PROCEDURE' => 'Enable preauthorization of payment',
    'HELP_OSCUNZER_PAYMENT_PROCEDURE' => 'If this setting is active, the payment will be authorized first and the customer will be charged later for this paymenttype ',
    'OSCUNZER_NO_UNZER_ORDER' => 'This order did not use an Unzer paymentmethod or no data available',
    'OSCUNZER_TRANSACTION_CREATED' => 'Transaction created',
    'OSCUNZER_TRANSACTION_CUSTOMERID' => 'Unzer Customer-ID',
    'OSCUNZER_TRANSACTION_STATUS' => 'Unzer OrderState',
    'OSCUNZER_TRANSACTION_ORDERNR' => 'Unzer OrderNr',
    'OSCUNZER_TRANSACTION_TYPEID' => 'Unzer Type-ID',
    'OSCUNZER_TRANSACTION_AMOUNT' => 'Amount',
    'OSCUNZER_TRANSACTION_STATUS_pending' => 'pending',
    'OSCUNZER_TRANSACTION_STATUS_completed' => 'completed',
    'OSCUNZER_TRANSACTION_STATUS_canceled' => 'canceled',
    'OSCUNZER_TRANSACTION_STATUS_partly' => 'charged partly',
    'OSCUNZER_TRANSACTION_STATUS_paymentreview' => 'payment must be checked',
    'OSCUNZER_TRANSACTION_STATUS_chargeback' => 'chargeback happend',
    'OSCUNZER_TRANSACTION_STATUS_init' => 'init',
    'OSCUNZER_TRANSACTION_STATUS_charged' => 'charged',
    'OSCUNZER_TRANSACTION_STATUS_success' => 'success',
    'OSCUNZER_TRANSACTION_STATUS_error' => 'error',
    'OSCUNZER_TRANSACTION_STATUS_shipped' => 'shipped',
    'OSCUNZER_TRANSACTION_PAYMENTMETA' => 'Payment-Info',
    'OSCUNZER_TRANSACTION_SHORTID' => 'Unzer Short-ID',
    'OSCUNZER_TRANSACTION_TRACEID' => 'Unzer Trace-ID',
    'OSCUNZER_REMAING_AMOUNT' => 'Outstanding balance',
    'OSCUNZER_ORDER_AMOUNT' => 'Grand total',
    'OSCUNZER_AUTHORIZE_CANCEL_POSSIBLE' => 'As long as no amount has been collected, the authorization can be cancelled.',
    'OSCUNZER_AUTHORIZE_CANCEL' => 'Cancel authorization',
    'OSCUNZER_AUTHORIZE_CANCEL_ALERT' => 'Do you really want to cancel the authorization of the selected amount? chosen amount: ',
    'OSCUNZER_CHARGES' => 'Charges',
    'OSCUNZER_CHARGED_AMOUNT' => 'amount collected',
    'OSCUNZER_CHARGED_CANCELLED' => 'refunded amount',
    'OSCUNZER_CANCEL_ERR_AMOUNT' => 'wrong amount',
    'OSCUNZER_CHARGE_CANCELREASON' => 'refund reason',
    'OSCUNZER_CHARGE_CANCELAMOUNT' => 'redemption amount',
    'OSCUNZER_CHARGE_CANCEL_FROM_PAYMENT' => 'Refund from charged total amount',
    'OSCUNZER_CHARGE_COLLECT' => 'Collect',
    'OSCUNZER_CHARGE_ID' => 'Charge-ID',
    'OSCUNZER_CANCEL_ID' => 'Cancel-ID',
    'OSCUNZER_BANK_HOLDER_DETAILS' => 'Bankdetails',
    'OSCUNZER_IBAN' => 'IBAN',
    'OSCUNZER_BIC' => 'BIC',
    'OSCUNZER_HOLDER' => 'Holder',
    'OSCUNZER_DESCRIPTOR' => 'Descriptor',
    'OSCUNZER_AUTHORIZATION' => 'Authorization',
    'OSCUNZER_NOSHIPINGYET' => 'order has not yet been marked as shipped',
    'OSCUNZER_SHIPMENTS' => 'Shipments',
    'OSCUNZER_SHIPMENT_NOTIFICATION' => 'Confirm shipment',
    'OSCUNZER_CANCELLATIONS' => 'Cancellations',
    'OSCUNZER_CANCEL_MISSINGREASON' => 'A reason for repayment must be specified for the payment method',
    'OSCUNZER_CANCEL_ALERT' => 'Refunded amounts cannot be recovered! Do you really want to refund the selected amount? chosen amount: ',
    'OSCUNZER_COLLECT_ALERT' => 'Do you really want to collect the selected amount? chosen amount: ',
    'OSCUNZER_SHIP_ID' => 'Shipping-Id',
    'OSCUNZER_REASON_NONE' => 'none',
    'OSCUNZER_REASON_CANCEL' => 'cancel order',
    'OSCUNZER_REASON_RETURN' => 'Customer sends articles back',
    'OSCUNZER_REASON_CREDIT' => 'Customer gets money back, he can keep articles',
    'OSCUNZER_PAYOUT' => 'Payout',
    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_SET_KEY' => 'ApplePay: Transmitting key failed',

    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_SET_CERT' => 'ApplePay: Transmitting payment certificate failed',
    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_SET_CERT' => 'ApplePay: Transmitting merchant certificate failed',

    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_KEY_TOO_SHORT' => 'ApplePay: Merchant key is too short',
    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_KEY_EMPTY' => 'ApplePay: Merchant key is empty',

    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_CERT_TOO_SHORT' => 'ApplePay: Merchant certificate is too short',
    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_CERT_EMPTY' => 'ApplePay: Merchant certificate is empty',

    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_KEY_TOO_SHORT' => 'ApplePay: Payment key is too short',
    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_KEY_EMPTY' => 'ApplePay: Payment key is empty',

    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_CERT_TOO_SHORT' => 'ApplePay: Payment certificate is too short',
    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_CERT_EMPTY' => 'ApplePay: Payment certificate is empty',

    'OSCUNZER_ERROR_ACTIVATE_APPLEPAY_PAYMENT_CERT' => 'ApplePay: Activating certificate failed',

    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_GET_KEY' => 'ApplePay: Getting key failed',
    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_GET_CERT' => 'ApplePay: Getting certificate failed',

    'OSCUNZER_sandbox' => 'Sandbox',
    'OSCUNZER_production' => 'Production',
    'OSCUNZER_CHARGEBACK' => 'Chargeback occurred!',
];
