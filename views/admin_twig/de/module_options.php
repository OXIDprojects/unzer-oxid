<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

$sLangName = "Deutsch";

$aLang = [
    "charset" => "UTF-8",
    'SHOP_MODULE_GROUP_unzermerchant' => 'Zugangsdaten',
    'SHOP_MODULE_GROUP_unzerenvironment' => 'Betriebsmodus',
    'SHOP_MODULE_GROUP_unzercard' => 'zusätzliche Optionen für Kreditkarten',
    'SHOP_MODULE_GROUP_unzerpaypal' => 'zusätzliche Optionen für PayPal',
    'SHOP_MODULE_GROUP_unzerinstallment' => 'zusätzliche Optionen für Ratenzahlung',
    'SHOP_MODULE_GROUP_unzerapplepay' => 'zusätzliche Optionen für ApplePay',
    'SHOP_MODULE_GROUP_unzerinvoice' => 'zusätzliche Optionen für Unzer Rechnung (Paylater)',
    'SHOP_MODULE_GROUP_unzerwebhooks' => 'Webhook Einstellungen',
    'SHOP_MODULE_GROUP_unzerother' => 'Sonstiges',
    'SHOP_MODULE_GROUP_unzerpaylater' => 'zusätzliche Optionen für Unzer Ratenzahlung (Paylater)',

    'SHOP_MODULE_webhookConfiguration' => '',
    'SHOP_MODULE_webhook_id' => 'Webhook ID',
    'SHOP_MODULE_webhook_context' => 'Kontext',
    'SHOP_MODULE_webhook_register' => 'Webhooks anlegen',
    'SHOP_MODULE_webhook_unregister' => 'Webhooks löschen',
    'SHOP_MODULE_sandbox-UnzerPublicKey' => 'Sandbox öffentlicher Schlüssel',
    'SHOP_MODULE_sandbox-UnzerPrivateKey' => 'Sandbox privater Schlüssel',
    'SHOP_MODULE_production-UnzerPublicKey' => 'Live öffentlicher Schlüssel',
    'SHOP_MODULE_production-UnzerPrivateKey' => 'Live privater Schlüssel',
    'SHOP_MODULE_UnzerSystemMode' => 'Modus',
    'SHOP_MODULE_UnzerSystemMode_0' => 'Sandbox',
    'SHOP_MODULE_UnzerSystemMode_1' => 'Livebetrieb',
    'SHOP_MODULE_UnzerOption_oscunzer_card' => 'Kreditkarte',
    'SHOP_MODULE_UnzerOption_oscunzer_card_0' => 'einziehen',
    'SHOP_MODULE_UnzerOption_oscunzer_card_1' => 'autorisieren und einziehen',
    'SHOP_MODULE_UnzerOption_oscunzer_paypal' => 'Paypal',
    'SHOP_MODULE_UnzerOption_oscunzer_paypal_0' => 'einziehen',
    'SHOP_MODULE_UnzerOption_oscunzer_paypal_1' => 'autorisieren und einziehen',
    'SHOP_MODULE_UnzerOption_oscunzer_installment_rate' => 'Zinssatz für Ratenzahlung',
    'HELP_SHOP_MODULE_UnzerOption_oscunzer_installment_rate' => 'Zinssatz in %, Dezimaltrennzeichen "." z.B.: "4.5"',
    'SHOP_MODULE_UnzerjQuery' => 'Einbindung von jQuery über das Modul',
    'SHOP_MODULE_UnzerWebhookTimeDifference' => 'Zeitgrenze in Minuten, bis wann Bestellungen mittels Webhook erstellt werden',
    'HELP_SHOP_MODULE_UnzerSystemMode' => 'Wechseln Sie hier zwischen Sandbox (Testmodus) und Livebetrieb',
    'SHOP_MODULE_UnzerDebug' => 'Debug-Modus aktivieren',
    'HELP_SHOP_MODULE_UnzerDebug' => 'Im aktiven Debug-Modus werden Log-Files in das Verzeichnis /log/unzer geschrieben.',
    'HELP_SHOP_MODULE_UnzerLogLevel' => 'Das Log Level bestimmt, welche Ereignisse in der Logdatei protokolliert werden.
        Mögliche Werte sind Debug (alles), Warning (Warnungen und Fehler), oder Error (nur Fehler).',
    'SHOP_MODULE_WEBHOOK' => 'Registrierter Webhook',
    'SHOP_MODULE_REGISTER_WEBHOOK' => 'Webhook für diesen Shop registrieren',
    'SHOP_MODULE_DELETE_WEBHOOK' => 'Webhook für diesen Shop löschen',
    'SHOP_MODULE_WEBHOOK_NO_UNZER' => 'Um ein Webhook registrieren zu können, müssen die Unzer-Keys hinterlegt sein.',
    'SHOP_MODULE_APPLE_PAY_PAYMENT_CERTS_PROCESSED' => 'Zahlungs-Zertifikate (%s) wurden übertragen',
    'SHOP_MODULE_TRANSFER_APPLE_PAY_PAYMENT_DATA' => 'Zahlungs-Zertifikate übertragen (%s)',
    'SHOP_MODULE_RETRANSFER_APPLE_PAY_PAYMENT_DATA' => 'Neue Zahlungs-Zertifikate übertragen (%s)',
    'SHOP_MODULE_APPLE_PAY_PAYMENT_PROCESSING_CERT' => 'Zertifikat zur Zahlungsabwicklung (%s)',
    'SHOP_MODULE_APPLE_PAY_PAYMENT_PROCESSING_CERT_KEY' => 'Privater Schlüssel zur Zahlungsabwicklung (%s)',
    'SHOP_MODULE_sandbox-applepay_merchant_identifier' => 'Shopbetreiber Identifikation (%s)',
    'SHOP_MODULE_sandbox-applepay_merchant_cert' => 'Shopbetreiber Zertifikat (%s)',
    'SHOP_MODULE_sandbox-applepay_merchant_cert_key' => 'Shopbetreiber Zertifikat Privater Schlüssel (%s)',
    'SHOP_MODULE_production-applepay_merchant_identifier' => 'Shopbetreiber Identifikation (%s)',
    'SHOP_MODULE_production-applepay_merchant_cert' => 'Shopbetreiber Zertifikat (%s)',
    'SHOP_MODULE_production-applepay_merchant_cert_key' => 'Shopbetreiber Zertifikat Privater Schlüssel (%s)',
    'SHOP_MODULE_TRANSFER_APPLE_PAY_CERT' => 'Zertifikat (%s) an Unzer übertragen',
    'SHOP_MODULE_TRANSFER_APPLE_PAY_PRIVATE_KEY' => 'Schlüssel (%s) an Unzer übertragen',
    'SHOP_MODULE_applepay_merchant_capabilities' => 'Unterstützte Zahlungsarten',
    'SHOP_MODULE_applepay_merchant_capabilities_supportsCredit' => 'Kreditkarte',
    'SHOP_MODULE_applepay_merchant_capabilities_supportsDebit' => 'Debitkarte',
    'HELP_SHOP_MODULE_applepay_merchant_capabilities' => 'Vom Händler unterstützte Zahlungsarten. Wählen Sie mindestens eine Zahlungsart aus, die Sie dem Kunden anbieten wollen. Wenn Sie keine Zahlungsart auswählen, so werden standardmäßig alle Zahlungsarten angezeigt.',
    'SHOP_MODULE_applepay_networks' => 'Unterstützte Kreditkarten',
    'SHOP_MODULE_applepay_networks_maestro' => 'Maestro',
    'SHOP_MODULE_applepay_networks_masterCard' => 'Mastercard',
    'SHOP_MODULE_applepay_networks_visa' => 'Visa',
    'HELP_SHOP_MODULE_applepay_networks' => 'Vom Händler unterstützte Kreditkarten. Wählen Sie mindestens eine Kreditkarte aus, die Sie dem Kunden anbieten wollen. Wenn Sie keine Kreditkarte auswählen, so werden standardmäßig alle Kreditkarten angezeigt.',
    'SHOP_MODULE_applepay_label' => 'Firma',
    'HELP_SHOP_MODULE_applepay_label' => 'Wenn kein Wert eingetragen ist, wird stattdessen der in den Grundeinstellungen hinterlegte Firmenname verwendet',
    // live Paylater Invoice B2C EUR
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2CEURPrivateKey' => 'Live privater Schlüssel für Rechnung B2C-Käufe in EUR',
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2CEURPublicKey' => 'Live öffentlicher Schlüssel für Rechnung B2C-Käufe in EUR',
    // live Paylater Invoice B2B EUR
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2BEURPrivateKey' => 'Live privater Schlüssel für Rechnung B2B-Käufe in EUR',
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2BEURPublicKey' => 'Live öffentlicher Schlüssel für Rechnung B2B-Käufe in EUR',
    // live Paylater Invoice B2C CHF
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2CCHFPrivateKey' => 'Live privater Schlüssel für Rechnung B2C-Käufe in CHF',
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2CCHFPublicKey' => 'Live öffentlicher Schlüssel für Rechnung B2C-Käufe in CHF',
    // live Paylater Invoice B2B CHF
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2BCHFPrivateKey' => 'Live privater Schlüssel für Rechnung B2B-Käufe in CHF',
    'SHOP_MODULE_production-UnzerPayLaterInvoiceB2BCHFPublicKey' => 'Live öffentlicher Schlüssel für Rechnung B2B-Käufe in CHF',
    // live Paylater Installment B2C EUR
    'SHOP_MODULE_production-UnzerPayLaterInstallmentB2CEURPrivateKey' => 'Live privater Schlüssel für Ratenkauf B2C-Käufe in EUR',
    'SHOP_MODULE_production-UnzerPayLaterInstallmentB2CEURPublicKey' => 'Live öffentlicher Schlüssel für Ratenkauf B2C-Käufe in EUR',
    // live Paylater Installment B2C CHF
    'SHOP_MODULE_production-UnzerPayLaterInstallmentB2CCHFPrivateKey' => 'Live privater Schlüssel für Ratenkauf B2C-Käufe in CHF',
    'SHOP_MODULE_production-UnzerPayLaterInstallmentB2CCHFPublicKey' => 'Live öffentlicher Schlüssel für Ratenkauf B2C-Käufe in CHF',
    // sandbox Paylater Invoice B2C EUR
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2CEURPrivateKey' => 'Sandbox privater Schlüssel für Rechnung B2B-Käufe in EUR',
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2CEURPublicKey' => 'Sandbox öffentlicher Schlüssel für Rechnung B2B-Käufe in EUR',
    // sandbox Paylater Invoice B2B EUR
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2BEURPrivateKey' => 'Sandbox privater Schlüssel für Rechnung B2B-Käufe in EUR',
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2BEURPublicKey' => 'Sandbox öffentlicher Schlüssel Rechnung für B2B-Käufe in EUR',
    // sandbox Paylater Invoice B2C CHF
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2CCHFPrivateKey' => 'Sandbox privater Schlüssel für Rechnung B2C-Käufe in CHF',
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2CCHFPublicKey' => 'Sandbox öffentlicher Schlüssel für Rechnung B2C-Käufe in CHF',
    // sandbox Paylater Invoice B2B CHF
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2BCHFPrivateKey' => 'Sandbox privater Schlüssel für Rechnung B2B-Käufe in CHF',
    'SHOP_MODULE_sandbox-UnzerPayLaterInvoiceB2BCHFPublicKey' => 'Sandbox öffentlicher Schlüssel für Rechnung B2B-Käufe in CHF',
    // sandbox Paylater Installment B2C EUR
    'SHOP_MODULE_sandbox-UnzerPayLaterInstallmentB2CEURPrivateKey' => 'Sandbox privater Schlüssel für Ratenkauf B2C-Käufe in EUR',
    'SHOP_MODULE_sandbox-UnzerPayLaterInstallmentB2CEURPublicKey' => 'Sandbox öffentlicher Schlüssel für Ratenkauf B2C-Käufe in EUR',
    // sandbox Paylater Installment B2C EUR
    'SHOP_MODULE_sandbox-UnzerPayLaterInstallmentB2CCHFPrivateKey' => 'Sandbox privater Schlüssel für Ratenkauf B2C-Käufe in CHF',
    'SHOP_MODULE_sandbox-UnzerPayLaterInstallmentB2CCHFPublicKey' => 'Sandbox öffentlicher Schlüssel für Ratenkauf B2C-Käufe in CHF',
];
