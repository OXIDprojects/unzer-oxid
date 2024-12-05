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

    // Error
    'OSCUNZER_ERROR_DURING_CHECKOUT' => 'An error occurred while processing the payment. The process was reversed. Alternatively, please select another payment method.',
    'OSCUNZER_CANCEL_DURING_CHECKOUT' => 'The payment was cancelled. This order is saved as "not finished" under the number %s in your customer account so that you can track the process.',

    //Invoice
    'OSCUNZER_BANK_DETAILS_AMOUNT' => 'Please transfer the amount of %s %s to the following account:<br /><br />',
    'OSCUNZER_BANK_DETAILS_HOLDER' => 'Holder: %s<br/>',
    'OSCUNZER_BANK_DETAILS_IBAN' => 'IBAN: %s<br/>',
    'OSCUNZER_BANK_DETAILS_BIC' => 'BIC: %s<br/><br/>',
    'OSCUNZER_BANK_DETAILS_DESCRIPTOR' => 'Please use only this identification number as the descriptor: <br/>%s',
    'OSCUNZER_CONSUMER_TARGET' => 'Invoice as a ...',
    'OSCUNZER_CONSUMER_TARGET_B2B' => 'Company',
    'OSCUNZER_CONSUMER_TARGET_B2C' => 'Private person',

    'OSCUNZER_COMPANY_FORM' => 'Company-<br>form',
    'OSCUNZER_COMPANY_FORM_authority' => 'Authority',
    'OSCUNZER_COMPANY_FORM_association' => 'Association',
    'OSCUNZER_COMPANY_FORM_sole' => 'Sole',
    'OSCUNZER_COMPANY_FORM_company' => 'Company',
    'OSCUNZER_COMPANY_FORM_other' => 'Other',
    'OSCUNZER_COMPANY_FORM_birthday' => 'Please enter your date of birth',

    'OSCUNZER_MISSING_INTAGIBLE_CONFIRMATION_MESSAGE' => 'Please confirm the hint regarding the access to digital goods.',
    'OSCUNZER_MISSING_SERVICEAGREEMENT_CONFIRMATION_MESSAGE' => 'Please confirm the service products agreement.',

    // Invoice Secured B2B
    'OSCUNZER_COMMERCIAL_SECTOR' => 'Wirtschaftszweig',
    'OSCUNZER_COMMERCIAL_SECTOR_OTHER' => 'Sonstige',
    'OSCUNZER_COMMERCIAL_SECTOR_WHOLESALE_TRADE_EXCEPT_VEHICLE_TRADE' => 'wholesale trade except vehicle trade',
    'OSCUNZER_COMMERCIAL_SECTOR_RETAIL_TRADE_EXCEPT_VEHICLE_TRADE' => 'retail trade except vehicle trade',
    'OSCUNZER_COMMERCIAL_SECTOR_WATER_TRANSPORT' => 'water transport',
    'OSCUNZER_COMMERCIAL_SECTOR_AIR_TRANSPORT' => 'air transport',
    'OSCUNZER_COMMERCIAL_SECTOR_WAREHOUSING_AND_SUPPORT_ACTIVITES_FOR_TRANSPORTATION' => 'warehousing and support activites for transportation',
    'OSCUNZER_COMMERCIAL_SECTOR_POSTAL_AND_COURIER_ACTIVITIES' => 'postal and courier activities',
    'OSCUNZER_COMMERCIAL_SECTOR_ACCOMMODATION' => 'accommodation',
    'OSCUNZER_COMMERCIAL_SECTOR_FOOD_AND_BEVERAGE_SERVICE_ACTIVITIES' => 'food and beverage service activities',
    'OSCUNZER_COMMERCIAL_SECTOR_MOTION_PICTURE_PRODUCTION_AND_SIMILAR_ACTIVITIES' => 'motion picture production and similar activities',
    'OSCUNZER_COMMERCIAL_SECTOR_TELECOMMUNICATIONS' => 'telecommunications',
    'OSCUNZER_COMMERCIAL_SECTOR_COMPUTER_PROGRAMMING_CONSULTANCY_AND_RELATED_ACTIVITIES' => 'computer programming consultancy and related activities',
    'OSCUNZER_COMMERCIAL_SECTOR_INFORMATION_SERVICE_ACTIVITIES' => 'information service activities',
    'OSCUNZER_COMMERCIAL_SECTOR_RENTAL_AND_LEASING_ACTIVITIES' => 'rental and leasing activities',
    'OSCUNZER_COMMERCIAL_SECTOR_TRAVEL_AGENCY_AND_RELATED_ACTIVITIES' => 'travel agency and related activities',
    'OSCUNZER_COMMERCIAL_SECTOR_SERVICES_TO_BUILDINGS_AND_LANDSCAPE_ACTIVITIES' => 'services to buildings and landscape activities',
    'OSCUNZER_COMMERCIAL_SECTOR_LIBRARIES_AND_SIMILAR_CULTURAL_ACTIVITIES' => 'libraries and similar cultural activities',
    'OSCUNZER_COMMERCIAL_SECTOR_SPORTS_ACTIVITIES_AND_AMUSEMENT_AND_RECREATION_ACTIVITIES' => 'sports activities and amusement and recreation activities',
    'OSCUNZER_COMMERCIAL_SECTOR_OTHER_PERSONAL_SERVICE_ACTIVITIES' => 'other personal service activities',
    'OSCUNZER_COMMERCIAL_SECTOR_NON_RESIDENTIAL_REAL_ESTATE_ACTIVITIES' => 'non residential real estate activities',
    'OSCUNZER_COMMERCIAL_SECTOR_MANAGEMENT_CONSULTANCY_ACTIVITIES' => 'management consultancy activities',
    'OSCUNZER_COMMERCIAL_SECTOR_ELECTRICITY_GAS_AND_STEAM_SUPPLY' => 'electricity gas and steam supply',
    'OSCUNZER_COMMERCIAL_SECTOR_WATER_COLLECTION_TREATMENT_AND_SUPPLY' => 'water collection treatment and supply',
    'OSCUNZER_COMMERCIAL_SECTOR_SEWERAGE' => 'sewerage',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_FOOD_PRODUCTS' => 'manufacture of food products',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_BEVERAGES' => 'manufacture of beverages',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_TEXTILES' => 'manufacture of textiles',
    'OSCUNZER_COMMERCIAL_SECTOR_OTHERS_COMMERCIAL_SECTORS' => 'others commercial sectors',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_WEARING_APPAREL' => 'manufacture of wearing apparel',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_LEATHER_AND_RELATED_PRODUCTS' => 'manufacture of leather and related products',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_PHARMACEUTICAL_PRODUCTS' => 'manufacture of pharmaceutical products',
    'OSCUNZER_COMMERCIAL_SECTOR_REPAIR_AND_INSTALLATION_OF_MACHINERY_AND_EQUIPMENT' => 'repair and installation of machinery and equipment',
    'OSCUNZER_COMMERCIAL_SECTOR_TRADE_AND_REPAIR_OF_MOTOR_VEHICLES' => 'trade and repair of motor vehicles',
    'OSCUNZER_COMMERCIAL_SECTOR_PUBLISHING_ACTIVITIES' => 'publishing activities',
    'OSCUNZER_COMMERCIAL_SECTOR_REPAIR_OF_COMPUTERS_AND_GOODS' => 'repair of computers and goods',
    'OSCUNZER_COMMERCIAL_SECTOR_PRINTING_AND_REPRODUCTION_OF_RECORDED_MEDIA' => 'printing and reproduction of recorded media',
    'OSCUNZER_COMMERCIAL_SECTOR_MANUFACTURE_OF_FURNITURE' => 'manufacture of furniture',
    'OSCUNZER_COMMERCIAL_SECTOR_OTHER_MANUFACTURING' => 'other manufacturing',
    'OSCUNZER_COMMERCIAL_SECTOR_ADVERTISING_AND_MARKET_RESEARCH' => 'advertising and market research',
    'OSCUNZER_COMMERCIAL_SECTOR_OTHER_PROFESSIONAL_SCIENTIFIC_AND_TECHNICAL_ACTIVITIES' => 'other professional scientific and technical activities',
    'OSCUNZER_COMMERCIAL_SECTOR_ARTS_ENTERTAINMENT_AND_RECREATION' => 'arts entertainment and recreation',
    'OSCUNZER_COMMERCIAL_REGISTER_NUMBER' => 'Company registration number',
    'OSCUNZER_COMMERCIAL_HELP' => 'For "Invoice with Unzer" we still need information from your company about the economic sector and your commercial register number (if you have one).',

    // SEPA
    'OSCUNZER_DIRECT_DEBIT_MANDATE' => 'SEPA direct debit mandate (direct debit) is issued',
    'ERROR_UNZER_SEPA_CONFIRMATION_MISSING' => 'Please confirm the SEPA mandate',

    // Installment
    'OSCUNZER_INSTALLMENT_PURCHASE_AMOUNT' => 'total purchase amount',
    'OSCUNZER_INSTALLMENT_INTEREST_AMOUNT' => 'total interest amount',
    'OSCUNZER_INSTALLMENT_TOTAL' => 'total',
    'OSCUNZER_INSTALLMENT_PDF' => 'I agree to the <b><a href="%s" target="_blank">contract (PDF)</a></b>!',
    'OSCUNZER_INSTALLMENT_SUBMIT' => 'confirm contract',
    'OSCUNZER_INSTALLMENT_CONTINUE' => 'Continue',
    'OSCUNZER_INSTALLMENT_CAMCEL' => 'cancel contract',
    'OSCUNZER_BUY_WITH' => 'Buy with',

    // Payment methods
    'OSCUNZER_PAYMENT_METHOD_ALIPAY' => 'Alipay',
    'OSCUNZER_PAYMENT_METHOD_APPLEPAY' => 'ApplePay',
    'OSCUNZER_PAYMENT_METHOD_BANCONTACT' => 'Bancontact',
    'OSCUNZER_PAYMENT_METHOD_CARD' => 'Card',
    'OSCUNZER_PAYMENT_METHOD_EPS' => 'EPS',
    'OSCUNZER_PAYMENT_METHOD_GIROPAY' => 'Giropay',
    'OSCUNZER_PAYMENT_METHOD_IDEAL' => 'iDEAL',
    'OSCUNZER_PAYMENT_METHOD_INSTALLMENT' => 'Installment',
    'OSCUNZER_PAYMENT_METHOD_INVOICE' => 'Invoice',
    'OSCUNZER_PAYMENT_METHOD_INVOICE-SECURED' => 'Invoice with Unzer',
    'OSCUNZER_PAYMENT_METHOD_PAYPAL' => 'PayPal',
    'OSCUNZER_PAYMENT_METHOD_PIS' => 'Bank Transfer',
    'OSCUNZER_PAYMENT_METHOD_PREPAYMENT' => 'Prepayment',
    'OSCUNZER_PAYMENT_METHOD_PRZELEWY24' => 'Przelewy24',
    'OSCUNZER_PAYMENT_METHOD_SEPA' => 'SEPA Direct',
    'OSCUNZER_PAYMENT_METHOD_SEPA-SECURED' => 'SEPA Direct Debit with Unzer',
    'OSCUNZER_PAYMENT_METHOD_SOFORT' => 'Sofort',
    'OSCUNZER_PAYMENT_METHOD_WECHATPAY' => 'WeChat Pay',
    'OSCUNZER_SAVED_PAYMENTS' => 'Saved Payments',
    'OSCUNZER_SAVE_PAYMENT' => 'Save Payment',
    'OSCUNZER_SAVE_PAYPAL' => 'New PayPal account',
    'OSCUNZER_SAVE_PAYMENT_PAYPAL' => 'Save PayPal access in the next step',
    'OSCUNZER_SAVE_PAYMENT_NO_PAYMENTS' => 'No saved Payments',
    'OSCUNZER_BRAND' => 'Brand',
    'OSCUNZER_HOLDER' => 'Holder',
    'OSCUNZER_IBAN' => 'IBAN',
    'OSCUNZER_CARD_NUMBER' => 'Card Number',
    'OSCUNZER_EXPIRY_DATE' => 'Expiry Date',
    'OSCUNZER_DELETE' => 'Delete',
    'OSCUNZER_REMOVE_PAYMENT' => 'Delete Payment?',
    'OSCUNZER_NEW_CARD' => 'New Card',
    'OSCUNZER_INVALID_PAYMENT_METHOD' => 'Invalid payment method',
    'OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_HEADLINE' => 'Bank Account Details',
    'OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_IBAN' => 'IBAN',
    'OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_BIC' => 'BIC',
    'OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_BANK_HOLDER' => 'Account Holder',
    'OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_BANK_DESCRIPTOR' => 'Descriptor',
];
