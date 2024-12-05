<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

$sLangName = "English";

$aLang = [
    "charset" => "UTF-8",
    'SHOP_MODULE_GROUP_unzermerchant' => 'Access Data',
    'SHOP_MODULE_GROUP_unzerenvironment' => 'Operation mode',
    'SHOP_MODULE_UnzerSavePaymentsForUser' => 'Save Userpayments (id)',
    'SHOP_MODULE_GROUP_unzercard' => 'additional options for credit cards',
    'SHOP_MODULE_GROUP_unzerpaypal' => 'additional options for PayPal',
    'SHOP_MODULE_GROUP_unzerinstallment' => 'additional options for Installment',
    'SHOP_MODULE_GROUP_unzerapplepay' => 'additional options for ApplePay',
    'SHOP_MODULE_GROUP_unzerinvoice' => 'additional options for Unzer Invoice (Paylater)',
    'SHOP_MODULE_GROUP_unzerwebhooks' => 'Webhook settings',
    'SHOP_MODULE_GROUP_unzerother' => 'Other',
    'SHOP_MODULE_GROUP_unzerpaylater' => 'additional options for Unzer installment (Paylater)',

    'SHOP_MODULE_webhookConfiguration' => '',
    'SHOP_MODULE_webhook_id' => 'Webhook ID',
    'SHOP_MODULE_webhook_context' => 'Context',
    'SHOP_MODULE_webhook_register' => 'Create webhooks',
    'SHOP_MODULE_webhook_unregister' => 'Delete webhooks',
    'SHOP_MODULE_sandbox-UnzerPublicKey' => 'Sandbox Public-Key',
    'SHOP_MODULE_sandbox-UnzerPrivateKey' => 'Sandbox Private-Key',
    'SHOP_MODULE_production-UnzerPublicKey' => 'Production Public-Key',
    'SHOP_MODULE_production-UnzerPrivateKey' => 'Production Private-Key',
    'SHOP_MODULE_UnzerSystemMode' => 'Mode',
    'SHOP_MODULE_UnzerSystemMode_0' => 'Sandbox',
    'SHOP_MODULE_UnzerSystemMode_1' => 'Production',
    'SHOP_MODULE_UnzerOption_oscunzer_card' => 'CreditCard',
    'SHOP_MODULE_UnzerOption_oscunzer_card_0' => 'direct Capture',
    'SHOP_MODULE_UnzerOption_oscunzer_card_1' => 'Authorize & later Capture',
    'SHOP_MODULE_UnzerOption_oscunzer_paypal' => 'PayPal',
    'SHOP_MODULE_UnzerOption_oscunzer_paypal_0' => 'direct Capture',
    'SHOP_MODULE_UnzerOption_oscunzer_paypal_1' => 'Authorize & later Capture',
    'SHOP_MODULE_UnzerOption_oscunzer_installment_rate' => 'Installment rate',
    'HELP_SHOP_MODULE_UnzerOption_oscunzer_installment_rate' => 'Installmentrate per 100 in %, i.e.: "4.5"',
    'SHOP_MODULE_UnzerjQuery' => 'Include jQuery via the module',
    'SHOP_MODULE_UnzerWebhookTimeDifference' => 'Time limit in minutes for creating paid orders via webhook',
    'HELP_SHOP_MODULE_UnzerSystemMode' => 'Here you can switch between Sandbox (demo mode) and Production mode',
    'SHOP_MODULE_UnzerDebug' => 'Enable debug mode',
    'HELP_SHOP_MODULE_UnzerDebug' => 'In active debug mode, log files are written to the /log/unzer directory.',
    'HELP_SHOP_MODULE_UnzerLogLevel' => 'The log level determines which events are logged in the log file.
        Possible values are Debug (all), Warning (warnings and errors), or Error (only errors).',
    'SHOP_MODULE_WEBHOOK' => 'Registered Webhook',
    'SHOP_MODULE_REGISTER_WEBHOOK' => 'Register Webhook',
    'SHOP_MODULE_DELETE_WEBHOOK' => 'Delete Webhook',
    'SHOP_MODULE_WEBHOOK_NO_UNZER' => 'In order to register a webhook, the Unzer keys must be stored',
    'SHOP_MODULE_APPLE_PAY_PAYMENT_CERTS_PROCESSED' => 'Payment certificates (%s) have been transferred',
    'SHOP_MODULE_TRANSFER_APPLE_PAY_PAYMENT_DATA' => 'transfer Payment certificates (%s)',
    'SHOP_MODULE_RETRANSFER_APPLE_PAY_PAYMENT_DATA' => 'Transfer new payment certificates (%s)',
    'SHOP_MODULE_APPLE_PAY_PAYMENT_PROCESSING_CERT' => 'Payment Processing Certificate (%s)',
    'SHOP_MODULE_APPLE_PAY_PAYMENT_PROCESSING_CERT_KEY' => 'Private key for payment processing (%s)',
    'SHOP_MODULE_sandbox-applepay_merchant_identifier' => 'Merchant Identifier (%s)',
    'SHOP_MODULE_sandbox-applepay_merchant_cert' => 'Merchant Certificate (%s)',
    'SHOP_MODULE_sandbox-applepay_merchant_cert_key' => 'Merchant Certificate Private Key (%s)',
    'SHOP_MODULE_production-applepay_merchant_identifier' => 'Merchant Identifier (%s)',
    'SHOP_MODULE_production-applepay_merchant_cert' => 'Merchant Certificate (%s)',
    'SHOP_MODULE_production-applepay_merchant_cert_key' => 'Merchant Certificate Private Key (%s)',
    'SHOP_MODULE_TRANSFER_APPLE_PAY_CERT' => 'Transfer certificate (%s) to unzer',
    'SHOP_MODULE_TRANSFER_APPLE_PAY_PRIVATE_KEY' => 'Transfer key (%s) to unzer',
    'SHOP_MODULE_applepay_merchant_capabilities' => 'Supported payment types',
    'SHOP_MODULE_applepay_merchant_capabilities_supportsCredit' => 'Credit Card',
    'SHOP_MODULE_applepay_merchant_capabilities_supportsDebit' => 'Debit Card',
    'HELP_SHOP_MODULE_applepay_merchant_capabilities' => 'Payment methods supported by the merchant. Select at least one payment method that you want to offer the customer. If you do not select a payment method, all payment methods will be displayed by default.',
    'SHOP_MODULE_applepay_networks' => 'Supported credit cards',
    'SHOP_MODULE_applepay_networks_maestro' => 'Maestro',
    'SHOP_MODULE_applepay_networks_masterCard' => 'Mastercard',
    'SHOP_MODULE_applepay_networks_visa' => 'Visa',
    'HELP_SHOP_MODULE_applepay_networks' => 'Credit cards supported by the merchant. Select at least one credit card that you want to offer the customer. If you do not select a credit card, all credit cards are displayed by default.',
    'SHOP_MODULE_applepay_label' => 'Company',
    'HELP_SHOP_MODULE_applepay_label' => 'If no value is entered, the company name stored in the basic settings is used instead',
    // live Paylater Invoice B2C EUR
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2CEURPrivateKey' => 'Live Private-Key for Invoice B2C-purchases in EUR',
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2CEURPublicKey' => 'Live Public-Key for Invoice B2C-purchases in EUR',
    // live Paylater Invoice B2B EUR
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2BEURPrivateKey' => 'Live Private-Key for Invoice B2B-purchases in EUR',
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2BEURPublicKey' => 'Live Public-Key for Invoice B2B-purchases in EUR',
    // live Paylater Invoice B2C CHF
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2CCHFPrivateKey' => 'Live Private-Key for Invoice B2C-purchases in CHF',
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2CCHFPublicKey' => 'Live Public-Key for Invoice B2C-purchases in CHF',
    // live Paylater Invoice B2B CHF
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2BCHFPrivateKey' => 'Live Private-Key for Invoice B2B-purchases in CHF',
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2BCHFPublicKey' => 'Live Public-Key for Invoice B2B-purchases in CHF',
    // live Paylater Installment B2C EUR
    'SHOP_MODULE_production-UnzerPayLaterInstallmentB2CEURPrivateKey' => 'Live Private-Key for Installment B2C-purchases in EUR',
    'SHOP_MODULE_production-UnzerPayLaterInstallmentB2CEURPublicKey' => 'Live Public-Key for Installment B2C-purchases in EUR',
    // live Paylater Installment B2C CHF
    'SHOP_MODULE_production-UnzerPayLaterInstallmentB2CCHFPrivateKey' => 'Live Private-Key for Installment B2C-purchases in CHF',
    'SHOP_MODULE_production-UnzerPayLaterInstallmentB2CCHFPublicKey' => 'Live Public-Key for Installment B2C-purchases in CHF',
    // sandbox Paylater Invoice B2C EUR
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2CEURPrivateKey' => 'Sandbox Private-Key for Invoice B2C-purchases in EUR',
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2CEURPublicKey' => 'Sandbox Public-Key for Invoice B2C-purchases in EUR',
    // sandbox Paylater Invoice B2B EUR
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2BEURPrivateKey' => 'Sandbox Private-Key for Invoice B2B-purchases in EUR',
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2BEURPublicKey' => 'Sandbox Public-Key for Invoice B2B-purchases in EUR',
    // sandbox Paylater Invoice B2C CHF
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2CCHFPrivateKey' => 'Sandbox Private-Key for Invoice B2C-purchases in CHF',
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2CCHFPublicKey' => 'Sandbox Public-Key for Invoice B2C-purchases in CHF',
    // sandbox Paylater Invoice B2B CHF
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2BCHFPrivateKey' => 'Sandbox Private-Key for Invoice B2B-purchases in CHF',
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2BCHFPublicKey' => 'Sandbox Public-Key for Invoice B2B-purchases in CHF',
    // sandbox Paylater Installment B2C EUR
    'SHOP_MODULE_sandbox-UnzerPayLaterInstallmentB2CEURPrivateKey' => 'Sandbox Private-Key for Installment B2C-purchases in EUR',
    'SHOP_MODULE_sandbox-UnzerPayLaterInstallmentB2CEURPublicKey' => 'Sandbox Public-Key for Installment B2C-purchases in EUR',
    // sandbox Paylater Installment B2C EUR
    'SHOP_MODULE_sandbox-UnzerPayLaterInstallmentB2CCHFPrivateKey' => 'Sandbox Private-Key for Installment B2C-purchases in CHF',
    'SHOP_MODULE_sandbox-UnzerPayLaterInstallmentB2CCHFPublicKey' => 'Sandbox Public-Key for Installment B2C-purchases in CHF',
];
