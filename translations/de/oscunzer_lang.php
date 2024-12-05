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

    // Error
    'OSCUNZER_ERROR_DURING_CHECKOUT' => 'Bei der Abwicklung der Zahlung ist ein Fehler aufgetreten. Der Prozess wurde rückgängig gemacht. Bitte wählen Sie alternativ eine andere Zahlart aus.',
    'OSCUNZER_CANCEL_DURING_CHECKOUT' => 'Die Zahlung wurde abgebrochen. Diese Bestellung ist als "nicht abgeschlossen" unter der Nummer %s, in Ihrem Kundenkonto gespeichert, damit Sie den Vorgang nachvollziehen können.',

    // Invoice
    'OSCUNZER_BANK_DETAILS_AMOUNT' => 'Bitte überweisen sie den Betrag von %s %s auf folgendes Bankkonto:<br /><br />',
    'OSCUNZER_BANK_DETAILS_HOLDER' => 'Kontoinhaber: %s<br/>',
    'OSCUNZER_BANK_DETAILS_IBAN' => 'IBAN: %s<br/>',
    'OSCUNZER_BANK_DETAILS_BIC' => 'BIC: %s<br/><br/>',
    'OSCUNZER_BANK_DETAILS_DESCRIPTOR' => '<i>Bitte verwenden Sie diese Identifikationsnummer als Verwendungszweck: </i><br/>%s',
    'OSCUNZER_CONSUMER_TARGET' => 'Kauf auf Rechnung als ...',
    'OSCUNZER_CONSUMER_TARGET_B2B' => 'Firma',
    'OSCUNZER_CONSUMER_TARGET_B2C' => 'Privatperson',

    'OSCUNZER_COMPANY_FORM' => 'Unternehmens-<br>form',
    'OSCUNZER_COMPANY_FORM_authority' => 'Behörde',
    'OSCUNZER_COMPANY_FORM_association' => 'Interessenverband',
    'OSCUNZER_COMPANY_FORM_sole' => 'Einzelunternehmen',
    'OSCUNZER_COMPANY_FORM_company' => 'Betrieb',
    'OSCUNZER_COMPANY_FORM_other' => 'Sonstige',
    'OSCUNZER_COMPANY_FORM_birthday' => 'Bitte Geburtsdatum eingeben',

    'OSCUNZER_MISSING_INTAGIBLE_CONFIRMATION_MESSAGE' => 'Bitte bestätigen Sie den Hinweis für den Zugang zu digitalen Inhalten.',
    'OSCUNZER_MISSING_SERVICEAGREEMENT_CONFIRMATION_MESSAGE' => 'Bitte bestätigen Sie die Serviceprodukt-Vereinbarung.',

    // Invoice Secured B2B
    'OSCUNZER_COMMERCIAL_SECTOR' => 'Wirtschaftszweig',
    'OSCUNZER_COMMERCIAL_SECTOR_OTHER' => 'Sonstige',
    'OSCUNZER_COMMERCIAL_SECTOR_WHOLESALE_TRADE_EXCEPT_VEHICLE_TRADE' => 'Großhandel mit Ausnahme von Fahrzeughandel',
    'OSCUNZER_COMMERCIAL_SECTOR_RETAIL_TRADE_EXCEPT_VEHICLE_TRADE' => 'Einzelhandel mit Ausnahme von Fahrzeughandel',
    'OSCUNZER_COMMERCIAL_SECTOR_WATER_TRANSPORT' => 'Wassertransport',
    'OSCUNZER_COMMERCIAL_SECTOR_AIR_TRANSPORT' => 'Lufttransport',
    'OSCUNZER_COMMERCIAL_SECTOR_WAREHOUSING_AND_SUPPORT_ACTIVITES_FOR_TRANSPORTATION' => 'Dienstleistungen im Bereich Einlagerung und Transportunterstützung',
    'OSCUNZER_COMMERCIAL_SECTOR_POSTAL_AND_COURIER_ACTIVITIES' => 'Post- und Kurierdienstleistungen',
    'OSCUNZER_COMMERCIAL_SECTOR_ACCOMMODATION' => 'Unterbringung',
    'OSCUNZER_COMMERCIAL_SECTOR_FOOD_AND_BEVERAGE_SERVICE_ACTIVITIES' => 'Gastronomiedienstleistungen',
    'OSCUNZER_COMMERCIAL_SECTOR_MOTION_PICTURE_PRODUCTION_AND_SIMILAR_ACTIVITIES' => 'Filmproduktion und ähnliche Tätigkeiten',
    'OSCUNZER_COMMERCIAL_SECTOR_TELECOMMUNICATIONS' => 'Telekommunikation',
    'OSCUNZER_COMMERCIAL_SECTOR_COMPUTER_PROGRAMMING_CONSULTANCY_AND_RELATED_ACTIVITIES' => 'Beratungstätigkeiten in der Informationstechnologie und ähnliche Dienstleistungen',
    'OSCUNZER_COMMERCIAL_SECTOR_INFORMATION_SERVICE_ACTIVITIES' => 'Informationsdienstleistungen',
    'OSCUNZER_COMMERCIAL_SECTOR_RENTAL_AND_LEASING_ACTIVITIES' => 'Vermietung und Verpachtung',
    'OSCUNZER_COMMERCIAL_SECTOR_TRAVEL_AGENCY_AND_RELATED_ACTIVITIES' => 'Reisebüro und ähnliche Dienstleistungen',
    'OSCUNZER_COMMERCIAL_SECTOR_SERVICES_TO_BUILDINGS_AND_LANDSCAPE_ACTIVITIES' => 'Gebäudebetreuung, Garten- und Landschaftsbau',
    'OSCUNZER_COMMERCIAL_SECTOR_LIBRARIES_AND_SIMILAR_CULTURAL_ACTIVITIES' => 'Bibliotheken und andere kulturelle Dienstleistungen',
    'OSCUNZER_COMMERCIAL_SECTOR_SPORTS_ACTIVITIES_AND_AMUSEMENT_AND_RECREATION_ACTIVITIES' => 'Dienstleistungen des Sports, der Unterhaltung und der Erholung',
    'OSCUNZER_COMMERCIAL_SECTOR_OTHER_PERSONAL_SERVICE_ACTIVITIES' => 'Sonstige persönlich erbrachte Dienstleistungen',
    'OSCUNZER_COMMERCIAL_SECTOR_NON_RESIDENTIAL_REAL_ESTATE_ACTIVITIES' => 'Immobilientätigkeiten mit Nicht-Wohngebäuden',
    'OSCUNZER_COMMERCIAL_SECTOR_MANAGEMENT_CONSULTANCY_ACTIVITIES' => 'Unternehmensberatung',
    'OSCUNZER_COMMERCIAL_SECTOR_ELECTRICITY_GAS_AND_STEAM_SUPPLY' => 'Energieversorgung',
    'OSCUNZER_COMMERCIAL_SECTOR_WATER_COLLECTION_TREATMENT_AND_SUPPLY' => 'Wasserversorgung',
    'OSCUNZER_COMMERCIAL_SECTOR_SEWERAGE' => 'Abwasserentsorgung',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_FOOD_PRODUCTS' => 'Herstellung von Lebensmitteln',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_BEVERAGES' => 'Herstellung von Getränken',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_TEXTILES' => 'Herstellung von Textilien',
    'OSCUNZER_COMMERCIAL_SECTOR_OTHERS_COMMERCIAL_SECTORS' => 'Sonstige Gewerbebereiche',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_WEARING_APPAREL' => 'Herstellung von Bekleidung',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_LEATHER_AND_RELATED_PRODUCTS' => 'Herstellung von Lederwaren',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_PHARMACEUTICAL_PRODUCTS' => 'Herstellung pharmazeutischer Produkte',
    'OSCUNZER_COMMERCIAL_SECTOR_REPAIR_AND_INSTALLATION_OF_MACHINERY_AND_EQUIPMENT' => 'Reparatur und Installation von Maschinen und Ausrüstungen',
    'OSCUNZER_COMMERCIAL_SECTOR_TRADE_AND_REPAIR_OF_MOTOR_VEHICLES' => 'Handel und Reparatur motorisierter Fahrzeuge',
    'OSCUNZER_COMMERCIAL_SECTOR_PUBLISHING_ACTIVITIES' => 'Verlegerische Tätigkeiten',
    'OSCUNZER_COMMERCIAL_SECTOR_REPAIR_OF_COMPUTERS_AND_GOODS' => 'Reparatur von Datenverarbeitungsgeräten und Gebrauchsgütern',
    'OSCUNZER_COMMERCIAL_SECTOR_PRINTING_AND_REPRODUCTION_OF_RECORDED_MEDIA' => 'Druck und Reproduktion von Aufzeichnungsträgern',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_FURNITURE' => 'Möbelherstellung',
    'OSCUNZER_COMMERCIAL_SECTOR_OTHER_MANUFACTURING' => 'Sonstige Fertigung',
    'OSCUNZER_COMMERCIAL_SECTOR_ADVERTISING_AND_MARKET_RESEARCH' => 'Werbung und Marktforschung',
    'OSCUNZER_COMMERCIAL_SECTOR_OTHER_PROFESSIONAL_SCIENTIFIC_AND_TECHNICAL_ACTIVITIES' => 'Sonstige freiberufliche, wissenschaftliche und technische Tätigkeiten',
    'OSCUNZER_COMMERCIAL_SECTOR_ARTS_ENTERTAINMENT_AND_RECREATION' => 'Kunst, Unterhaltung und Erholung',
    'OSCUNZER_COMMERCIAL_REGISTER_NUMBER' => 'Handelsregister-Nummer',
    'OSCUNZER_COMMERCIAL_HELP' => 'Für die Bezahlung auf Rechnung benötigen wir von Ihrem Unternehmen noch Angaben zum Wirtschaftszweig und Ihre Handelsregister-Nummer (falls Sie eine besitzen).',

    // SEPA
    'OSCUNZER_DIRECT_DEBIT_MANDATE' => 'SEPA Lastschrift-Mandat (Bankeinzug) wird erteilt',
    'ERROR_UNZER_SEPA_CONFIRMATION_MISSING' => 'Bitte bestätigen Sie das SEPA-Mandat',

    // Installment
    'OSCUNZER_INSTALLMENT_PURCHASE_AMOUNT' => 'Gesamtkaufbetrag',
    'OSCUNZER_INSTALLMENT_INTEREST_AMOUNT' => 'Gesamtzinsbetrag',
    'OSCUNZER_INSTALLMENT_TOTAL' => 'Gesamtsumme',
    'OSCUNZER_INSTALLMENT_PDF' => 'Ich erkläre mich mit dem <b><a href="%s" target="_blank">Vertrag (PDF)</a></b> einverstanden!',
    'OSCUNZER_INSTALLMENT_SUBMIT' => 'Vertrag bestätigen',
    'OSCUNZER_INSTALLMENT_CONTINUE' => 'Weiter',
    'OSCUNZER_INSTALLMENT_CANCEL' => 'Vertrag ablehnen',
    'OSCUNZER_BUY_WITH' => 'Bezahlen mit',

    // Payment methods
    'OSCUNZER_PAYMENT_METHOD_ALIPAY' => 'Alipay',
    'OSCUNZER_PAYMENT_METHOD_APPLEPAY' => 'ApplePay',
    'OSCUNZER_PAYMENT_METHOD_BANCONTACT' => 'Bancontact',
    'OSCUNZER_PAYMENT_METHOD_CARD' => 'Kartenzahlung',
    'OSCUNZER_PAYMENT_METHOD_EPS' => 'EPS',
    'OSCUNZER_PAYMENT_METHOD_GIROPAY' => 'Giropay',
    'OSCUNZER_PAYMENT_METHOD_IDEAL' => 'iDEAL',
    'OSCUNZER_PAYMENT_METHOD_INSTALLMENT' => 'Ratenzahlung',
    'OSCUNZER_PAYMENT_METHOD_INVOICE' => 'Kauf auf Rechnung',
    'OSCUNZER_PAYMENT_METHOD_INVOICE-SECURED' => 'Abgesicherter Rechnungskauf',
    'OSCUNZER_PAYMENT_METHOD_PAYPAL' => 'PayPal',
    'OSCUNZER_PAYMENT_METHOD_PIS' => 'Bank Transfer',
    'OSCUNZER_PAYMENT_METHOD_PREPAYMENT' => 'Vorkasse',
    'OSCUNZER_PAYMENT_METHOD_PRZELEWY24' => 'Przelewy24',
    'OSCUNZER_PAYMENT_METHOD_SEPA' => 'SEPA-Lastschrift',
    'OSCUNZER_PAYMENT_METHOD_SEPA-SECURED' => 'SEPA Lastschrift (abgesichert durch Unzer)',
    'OSCUNZER_PAYMENT_METHOD_SOFORT' => 'Sofort',
    'OSCUNZER_PAYMENT_METHOD_WECHATPAY' => 'WeChat Pay',
    'OSCUNZER_SAVED_PAYMENTS' => 'Gespeicherte Zahlungsarten',
    'OSCUNZER_SAVE_PAYMENT' => 'Zahlungsart speichern',
    'OSCUNZER_SAVE_PAYPAL' => 'Neuer PayPal-Account',
    'OSCUNZER_SAVE_PAYMENT_PAYPAL' => 'PayPal-Zugang im nächsten Schritt speichern',
    'OSCUNZER_SAVE_PAYMENT_NO_PAYMENTS' => 'Keine Zahlungsarten gespeichert',
    'OSCUNZER_BRAND' => 'Marke',
    'OSCUNZER_HOLDER' => 'Inhaber',
    'OSCUNZER_IBAN' => 'IBAN',
    'OSCUNZER_CARD_NUMBER' => 'Kartennummer',
    'OSCUNZER_EXPIRY_DATE' => 'Ablaufdatum',
    'OSCUNZER_DELETE' => 'Löschen',
    'OSCUNZER_REMOVE_PAYMENT' => 'Gespeicherte Zahlungsart löschen?',
    'OSCUNZER_NEW_CARD' => 'Neue Kreditkarte',
    'OSCUNZER_INVALID_PAYMENT_METHOD' => 'Ungültige Zahlart',
    'OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_HEADLINE' => 'Bankkonto Details',
    'OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_IBAN' => 'IBAN',
    'OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_BIC' => 'BIC',
    'OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_BANK_HOLDER' => 'Kontoinhaber',
    'OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_BANK_DESCRIPTOR' => 'Verwendungszweck',
];
