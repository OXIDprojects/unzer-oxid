<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

$sLangName = 'Deutsch';

// -------------------------------
// RESOURCE IDENTIFIER = STRING
// -------------------------------
$aLang = [
    'charset' => 'UTF-8',
    'tbclorder_unzer' => 'Unzer',
    'OSCUNZER_PAYMENT_PROCEDURE' => 'Preautorisierung der Zahlung aktivieren',
    'HELP_OSCUNZER_PAYMENT_PROCEDURE' => 'Ist diese Einstellung aktiv, so wird bei dieser Zahlungsart die Zahlung zunächst autorisiert und der Kunde zu einem späteren Zeitpunkt belastet',
    'OSCUNZER_NO_UNZER_ORDER' => 'Bei diesem Auftrag wurde Unzer nicht verwendet oder es sind keine Unzer-Daten verfügbar',
    'OSCUNZER_TRANSACTION_CREATED' => 'Transaktion erstellt',
    'OSCUNZER_TRANSACTION_CUSTOMERID' => 'Unzer Kunden-ID',
    'OSCUNZER_TRANSACTION_STATUS' => 'Unzer Auftragsstatus',
    'OSCUNZER_TRANSACTION_ORDERNR' => 'Unzer OrderNr',
    'OSCUNZER_TRANSACTION_TYPEID' => 'Unzer Type-ID',
    'OSCUNZER_TRANSACTION_AMOUNT' => 'Betrag',
    'OSCUNZER_TRANSACTION_STATUS_pending' => 'ausstehend',
    'OSCUNZER_TRANSACTION_STATUS_completed' => 'abgeschlossen',
    'OSCUNZER_TRANSACTION_STATUS_canceled' => 'storniert',
    'OSCUNZER_TRANSACTION_STATUS_partly' => 'teilweise eingezogen',
    'OSCUNZER_TRANSACTION_STATUS_paymentreview' => 'Zahlung prüfen',
    'OSCUNZER_TRANSACTION_STATUS_chargeback' => 'Rückzahlung erfolgt',
    'OSCUNZER_TRANSACTION_STATUS_init' => 'initialisiert',
    'OSCUNZER_TRANSACTION_STATUS_charged' => 'eingezogen',
    'OSCUNZER_TRANSACTION_STATUS_success' => 'Erfolg',
    'OSCUNZER_TRANSACTION_STATUS_error' => 'Fehler',
    'OSCUNZER_TRANSACTION_STATUS_shipped' => 'versendet',
    'OSCUNZER_TRANSACTION_PAYMENTMETA' => 'Bezahl-Info',
    'OSCUNZER_TRANSACTION_SHORTID' => 'Unzer Short-ID',
    'OSCUNZER_TRANSACTION_TRACEID' => 'Unzer Trace-ID',
    'OSCUNZER_REMAING_AMOUNT' => 'Offener Betrag',
    'OSCUNZER_ORDER_AMOUNT' => 'Gesamtbetrag',
    'OSCUNZER_AUTHORIZE_CANCEL_POSSIBLE' => 'Solange kein Betrag eingezogen wurde, kann die Autorisierung storniert werden.',
    'OSCUNZER_AUTHORIZE_CANCEL' => 'Autorisierung stornieren',
    'OSCUNZER_AUTHORIZE_CANCEL_ALERT' => 'Wollen Sie wirklich für den gewählten Betrag die Authorisierung stornieren? Gewählter Betrag: ',
    'OSCUNZER_COLLECT_ALERT' => 'Wollen Sie wirklich für den gewählten Betrag einziehen? Gewählter Betrag: ',
    'OSCUNZER_CHARGES' => 'Eingezogene Beträge',
    'OSCUNZER_CHARGED_AMOUNT' => 'Eingezogener Betrag',
    'OSCUNZER_CHARGED_CANCELLED' => 'Erstatteter Betrag',
    'OSCUNZER_CANCEL_ERR_AMOUNT' => 'Ungültiger Betrag',
    'OSCUNZER_CHARGE_CANCELREASON' => 'Rückzahlungsgrund',
    'OSCUNZER_CHARGE_CANCELAMOUNT' => 'Rückzahlungsbetrag',
    'OSCUNZER_CHARGE_CANCEL_FROM_PAYMENT' => 'Rückzahlung von eingezogenem Gesamtbetrag',
    'OSCUNZER_CHARGE_COLLECT' => 'Einziehen',
    'OSCUNZER_CHARGE_ID' => 'Charge-ID',
    'OSCUNZER_CANCEL_ID' => 'Cancel-ID',
    'OSCUNZER_BANK_HOLDER_DETAILS' => 'Bankinformationen',
    'OSCUNZER_IBAN' => 'IBAN',
    'OSCUNZER_BIC' => 'BIC',
    'OSCUNZER_HOLDER' => 'Inhaber',
    'OSCUNZER_DESCRIPTOR' => 'Beschreibung',
    'OSCUNZER_AUTHORIZATION' => 'Autorisierung',
    'OSCUNZER_NOSHIPINGYET' => 'Bestellung ist bei Unzer noch nicht als Versendet markiert',
    'OSCUNZER_SHIPMENTS' => 'Auslieferungen',
    'OSCUNZER_SHIPMENT_NOTIFICATION' => 'Versand bestätigen',
    'OSCUNZER_CANCELLATIONS' => 'Stornierungen',
    'OSCUNZER_CANCEL_MISSINGREASON' => 'Für die Zahlungsart muss ein Rückzahlungs-Grund angegeben werden',
    'OSCUNZER_CANCEL_ALERT' => 'Zurückgebuchte Beträge können nicht wieder eingezogen werden! Wollen Sie wirklich den gewählten Betrag zurückerstatten? Gewählter Betrag: ',
    'OSCUNZER_SHIP_ID' => 'Shipping-Id',
    'OSCUNZER_REASON_NONE' => 'keine Begründung',
    'OSCUNZER_REASON_CANCEL' => 'Bestellung widerrufen',
    'OSCUNZER_REASON_RETURN' => 'Kunde sendet Ware zurück',
    'OSCUNZER_REASON_CREDIT' => 'Kunde bekommt Geld zurück, Ware kann er behalten',
    'OSCUNZER_PAYOUT' => 'Rückzahlung',

    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_SET_CERT' => 'ApplePay: Übertragung des Händlerzertifikats fehlgeschlagen',

    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_KEY_TOO_SHORT' => 'ApplePay: Händlerschlüssel ist zu kurz',
    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_KEY_EMPTY' => 'ApplePay: Händlerschlüssel ist leer',

    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_CERT_TOO_SHORT' => 'ApplePay: Händlerzertifikat ist zu kurz',
    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_CERT_EMPTY' => 'ApplePay: Händlerzertifikat ist leer',

    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_KEY_TOO_SHORT' => 'ApplePay: Zahlungsschlüssel ist zu kurz',
    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_KEY_EMPTY' => 'ApplePay: Zahlungsschlüssel ist leer',

    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_CERT_TOO_SHORT' => 'ApplePay: Zahlungszertifikat ist zu kurz',
    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_CERT_EMPTY' => 'ApplePay: Zahlungszertifikat ist leer',

    'OSCUNZER_sandbox' => 'Sandbox',
    'OSCUNZER_production' => 'Livebetrieb',
    'OSCUNZER_CHARGEBACK' => 'Chargeback erfolgt!',
];
