<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Metadata version
 */

use OxidSolutionCatalysts\Unzer\Controller\AccountSavedPaymentController;
use OxidSolutionCatalysts\Unzer\Model\DiscountList;
use OxidSolutionCatalysts\Unzer\Controller\Admin\AdminOrderController;
use OxidSolutionCatalysts\Unzer\Controller\Admin\ModuleConfiguration;
use OxidSolutionCatalysts\Unzer\Controller\Admin\OrderMain;
use OxidSolutionCatalysts\Unzer\Controller\Admin\OrderList;
use OxidSolutionCatalysts\Unzer\Controller\ApplePayCallbackController;
use OxidSolutionCatalysts\Unzer\Controller\DispatcherController;
use OxidSolutionCatalysts\Unzer\Controller\InstallmentController;
use OxidSolutionCatalysts\Unzer\Controller\OrderController;
use OxidSolutionCatalysts\Unzer\Controller\PaymentController;
use OxidSolutionCatalysts\Unzer\Core\Config;
use OxidSolutionCatalysts\Unzer\Core\ShopControl;
use OxidSolutionCatalysts\Unzer\Core\ViewConfig;
use OxidSolutionCatalysts\Unzer\Model\Article;
use OxidSolutionCatalysts\Unzer\Model\Order;
use OxidSolutionCatalysts\Unzer\Model\Payment;
use OxidSolutionCatalysts\Unzer\Module;
use OxidSolutionCatalysts\Unzer\Service\ModuleSettings;

$sMetadataVersion = '2.1';

/**
 * Module information.
 */
$aModule = [
    'id'          => Module::MODULE_ID,
    'title'       => [
        'de' => 'Unzer Payment für OXID',
        'en' => 'Unzer Payment for OXID',
    ],
    'description' => [
        'de' => 'Vor dem Start bitte lesen und prüfen:
            <ul>
                <li><a href="https://docs.unzer.com/online-payments/go-live-checklist/" target="_blank">Go-live checklist</a></li>
                <li><a href="https://insights.unzer.com/" target="_blank">Prüfen Sie Ihren Account und Ihre Zahlarten direkt bei Unzer</a></li>
            </ul>',
        'en' => 'Please read and check before you start:
            <ul>
                <li><a href="https://docs.unzer.com/online-payments/go-live-checklist/" target="_blank">Go-live checklist</a></li>
                <li><a href="https://insights.unzer.com/" target="_blank">Check your account and your payments at unzer</a></li>
            </ul>',
    ],
    'thumbnail'   => 'logo.svg',
    'version'     => '2.2.0-rc.12',
    'author'      => 'OXID eSales AG',
    'url'         => 'https://www.oxid-esales.com',
    'email'       => 'info@oxid-esales.com',
    'extend'      => [
        \OxidEsales\Eshop\Application\Controller\Admin\ModuleConfiguration::class => ModuleConfiguration::class,
        \OxidEsales\Eshop\Application\Controller\Admin\OrderMain::class           => OrderMain::class,
        \OxidEsales\Eshop\Application\Controller\Admin\OrderList::class           => OrderList::class,
        \OxidEsales\Eshop\Application\Controller\OrderController::class           => OrderController::class,
        \OxidEsales\Eshop\Application\Controller\PaymentController::class         => PaymentController::class,
        \OxidEsales\Eshop\Application\Model\Article::class                        => Article::class,
        \OxidEsales\Eshop\Application\Model\DiscountList::class                   => DiscountList::class,
        \OxidEsales\Eshop\Application\Model\Order::class                          => Order::class,
        \OxidEsales\Eshop\Application\Model\Payment::class                        => Payment::class,
        \OxidEsales\Eshop\Core\ShopControl::class                                 => ShopControl::class,
        \OxidEsales\Eshop\Core\ViewConfig::class                                  => ViewConfig::class,
        \OxidEsales\Eshop\Core\Config::class                                      => Config::class,
    ],
    'controllers' => [
        'unzer_admin_order'       => AdminOrderController::class,
        'unzer_dispatcher'        => DispatcherController::class,
        'unzer_installment'       => InstallmentController::class,
        'unzer_applepay_callback' => ApplePayCallbackController::class,
        'unzer_saved_payments'    => AccountSavedPaymentController::class,
    ],
    'templates'   => [
        // admin
        '@osc-unzer/admin/tpl/oscunzer_order.tpl' => 'views/smarty/admin/tpl/oscunzer_order.tpl',

        // frontend
        '@osc-unzer/frontend/tpl/account/account_saved_payments.tpl'       => 'views/smarty/frontend/tpl/account/account_saved_payments.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_assets.tpl'                   => 'views/smarty/frontend/tpl/order/unzer_assets.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_card.tpl'                     => 'views/smarty/frontend/tpl/order/unzer_card.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_paypal.tpl'                   => 'views/smarty/frontend/tpl/order/unzer_paypal.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_eps_charge.tpl'               => 'views/smarty/frontend/tpl/order/unzer_eps_charge.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_installment.tpl'              => 'views/smarty/frontend/tpl/order/unzer_installment.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_installment_confirm.tpl'      => 'views/smarty/frontend/tpl/order/unzer_installment_confirm.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_installment_confirm_flow.tpl' => 'views/smarty/frontend/tpl/order/unzer_installment_confirm_flow.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_installment_confirm_wave.tpl' => 'views/smarty/frontend/tpl/order/unzer_installment_confirm_wave.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_installment_paylater.tpl'     => 'views/smarty/frontend/tpl/order/unzer_installment_paylater.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_other.tpl'                    => 'views/smarty/frontend/tpl/order/unzer_other.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_invoice.tpl'                  => 'views/smarty/frontend/tpl/order/unzer_invoice.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_applepay.tpl'                 => 'views/smarty/frontend/tpl/order/unzer_applepay.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_sepa.tpl'                     => 'views/smarty/frontend/tpl/order/unzer_sepa.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_sepa_secured.tpl'             => 'views/smarty/frontend/tpl/order/unzer_sepa_secured.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_ideal.tpl'                    => 'views/smarty/frontend/tpl/order/unzer_ideal.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_shippingAndPayment_flow.tpl'  => 'views/smarty/frontend/tpl/order/unzer_shippingAndPayment_flow.tpl',
        '@osc-unzer/frontend/tpl/order/unzer_shippingAndPayment_wave.tpl'  => 'views/smarty/frontend/tpl/order/unzer_shippingAndPayment_wave.tpl',
        '@osc-unzer/frontend/tpl/message/js-errors.tpl'                    => 'views/smarty/frontend/tpl/message/js-errors.tpl',
        '@osc-unzer/frontend/tpl/payment/applepay_availibility_check.tpl'  => 'views/smarty/frontend/tpl/payment/applepay_availibility_check.tpl',
        '@osc-unzer/frontend/tpl/payment/payment_unzer.tpl'                => 'views/smarty/frontend/tpl/payment/payment_unzer.tpl',
        '@osc-unzer/frontend/tpl/order/applepay_button.tpl'                => 'views/smarty/frontend/tpl/order/applepay_button.tpl',
    ],
    'blocks'      => [
        //frontend
        [
            'theme'    => 'flow',
            'template' => 'page/checkout/order.tpl',
            'block'    => 'shippingAndPayment',
            'file'     => 'views/smarty/frontend/blocks/page/checkout/shippingAndPayment_flow.tpl'
        ],
        [
            'theme'    => 'wave',
            'template' => 'page/checkout/order.tpl',
            'block'    => 'shippingAndPayment',
            'file'     => 'views/smarty/frontend/blocks/page/checkout/shippingAndPayment_wave.tpl'
        ],
        [
            'template' => 'page/checkout/order.tpl',
            'block'    => 'checkout_order_errors',
            'file'     => 'views/smarty/frontend/blocks/page/checkout/checkout_order_errors.tpl'
        ],
        [
            'template' => 'page/checkout/order.tpl',
            'block'    => 'checkout_order_btn_submit_bottom',
            'file'     => 'views/smarty/frontend/blocks/page/checkout/checkout_order_btn_submit_bottom.tpl'
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block'    => 'select_payment',
            'file'     => 'views/smarty/frontend/blocks/page/checkout/select_payment.tpl'
        ],
        [
            'template' => 'page/account/inc/account_menu.tpl',
            'block'    => 'account_menu',
            'file'     => 'views/smarty/frontend/blocks/page/account/inc/account_menu.tpl'
        ],
        [
            'template' => 'page/checkout/thankyou.tpl',
            'block' => 'checkout_thankyou_info',
            'file' => 'views/smarty/frontend/blocks/page/checkout/thankyou_prepayment_info.tpl'
        ],
        //admin
        [
            'template' => 'module_config.tpl',
            'block'    => 'admin_module_config_var',
            'file'     => 'views/smarty/admin/blocks/admin_module_config_var.tpl'
        ],
        [
            'template' => 'order_list.tpl',
            'block'    => 'admin_order_list_item',
            'file'     => 'views/admin/blocks/admin_order_list_item.tpl'
        ],
        //email
        [
            'template' => 'email/plain/order_cust.tpl',
            'block'    => 'email_plain_order_cust_paymentinfo',
            'file'     => 'views/smarty/frontend/blocks/email/plain/email_plain_order_cust_paymentinfo.tpl'
        ],
        [
            'template' => 'email/html/order_cust.tpl',
            'block'    => 'email_html_order_cust_paymentinfo',
            'file'     => 'views/smarty/frontend/blocks/email/html/email_html_order_cust_paymentinfo.tpl'
        ],
    ],
    'settings'    => [
        [
            'group'       => 'unzerenvironment',
            'name'        => 'UnzerSystemMode',
            'type'        => 'select',
            'value'       => '0',
            'constraints' => '0|1'
        ],
        [
            'group' => 'unzerenvironment',
            'name'  => 'UnzerDebug',
            'type'  => 'bool',
            'value' => '0',
        ],
        [
            'group' => 'unzermerchant',
            'name'  => 'sandbox-UnzerPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzermerchant',
            'name'  => 'sandbox-UnzerPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzermerchant',
            'name'  => 'production-UnzerPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzermerchant',
            'name'  => 'production-UnzerPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerwebhooks',
            'name'  => 'webhookConfiguration',
            'type'  => 'arr',
            'value' => []
        ],
        [
            'group'       => 'unzercard',
            'name'        => 'UnzerOption_oscunzer_card',
            'type'        => 'select',
            'value'       => '0',
            'constraints' => '0|1'
        ],
        [
            'group'       => 'unzerpaypal',
            'name'        => 'UnzerOption_oscunzer_paypal',
            'type'        => 'select',
            'value'       => '0',
            'constraints' => '0|1'
        ],
        [
            'group' => 'unzerapplepay',
            'name'  => 'applepay_merchant_capabilities',
            'type'  => 'aarr',
            'value' => ModuleSettings::APPLE_PAY_MERCHANT_CAPABILITIES
        ],
        [
            'group' => 'unzerapplepay',
            'name'  => 'applepay_networks',
            'type'  => 'aarr',
            'value' => ModuleSettings::APPLE_PAY_NETWORKS
        ],
        [
            'group' => 'unzerapplepay',
            'name'  => 'applepay_label',
            'type'  => 'str',
            'value' => 'default lable please change'
        ],
        [
            'group' => 'unzerapplepay',
            'name'  => 'sandbox-applepay_merchant_identifier',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerapplepay',
            'name'  => 'sandbox-applepay_merchant_cert',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerapplepay',
            'name'  => 'sandbox-applepay_merchant_cert_key',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerapplepay',
            'name'  => 'production-applepay_merchant_identifier',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerapplepay',
            'name'  => 'production-applepay_merchant_cert',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerapplepay',
            'name'  => 'production-applepay_merchant_cert_key',
            'type'  => 'str',
            'value' => ''
        ],

        // live Paylater Invoice B2C EUR
        [
            'group' => 'unzerinvoice',
            'name'  => 'production-UnzerPayLaterInvoiceB2CEURPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerinvoice',
            'name'  => 'production-UnzerPayLaterInvoiceB2CEURPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        // live Paylater Invoice B2B EUR
        [
            'group' => 'unzerinvoice',
            'name'  => 'production-UnzerPayLaterInvoiceB2BEURPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerinvoice',
            'name'  => 'production-UnzerPayLaterInvoiceB2BEURPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        // live Paylater Invoice B2C CHF
        [
            'group' => 'unzerinvoice',
            'name'  => 'production-UnzerPayLaterInvoiceB2CCHFPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerinvoice',
            'name'  => 'production-UnzerPayLaterInvoiceB2CCHFPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        // live Paylater Invoice B2B CHF
        [
            'group' => 'unzerinvoice',
            'name'  => 'production-UnzerPayLaterInvoiceB2BCHFPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerinvoice',
            'name'  => 'production-UnzerPayLaterInvoiceB2BCHFPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        // live Paylater Installment B2C EUR
        [
            'group' => 'unzerpaylater',
            'name'  => 'production-UnzerPayLaterInstallmentB2CEURPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerpaylater',
            'name'  => 'production-UnzerPayLaterInstallmentB2CEURPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        // live Paylater Installment B2C CHF
        [
            'group' => 'unzerpaylater',
            'name'  => 'production-UnzerPayLaterInstallmentB2CCHFPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerpaylater',
            'name'  => 'production-UnzerPayLaterInstallmentB2CCHFPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        // sandbox Paylater Invoice B2C EUR
        [
            'group' => 'unzerinvoice',
            'name'  => 'sandbox-UnzerPayLaterInvoiceB2CEURPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerinvoice',
            'name'  => 'sandbox-UnzerPayLaterInvoiceB2CEURPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        // sandbox Paylater Invoice B2B EUR
        [
            'group' => 'unzerinvoice',
            'name'  => 'sandbox-UnzerPayLaterInvoiceB2BEURPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerinvoice',
            'name'  => 'sandbox-UnzerPayLaterInvoiceB2BEURPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        // sandbox Paylater Invoice B2C CHF
        [
            'group' => 'unzerinvoice',
            'name'  => 'sandbox-UnzerPayLaterInvoiceB2CCHFPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerinvoice',
            'name'  => 'sandbox-UnzerPayLaterInvoiceB2CCHFPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        // sandbox Paylater Invoice B2B CHF
        [
            'group' => 'unzerinvoice',
            'name'  => 'sandbox-UnzerPayLaterInvoiceB2BCHFPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerinvoice',
            'name'  => 'sandbox-UnzerPayLaterInvoiceB2BCHFPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        // sandbox Paylater Installment B2C EUR
        [
            'group' => 'unzerpaylater',
            'name'  => 'sandbox-UnzerPayLaterInstallmentB2CEURPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerpaylater',
            'name'  => 'sandbox-UnzerPayLaterInstallmentB2CEURPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        // sandbox Paylater Installment B2C CHF
        [
            'group' => 'unzerpaylater',
            'name'  => 'sandbox-UnzerPayLaterInstallmentB2CCHFPrivateKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerpaylater',
            'name'  => 'sandbox-UnzerPayLaterInstallmentB2CCHFPublicKey',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => 'unzerother',
            'name'  => 'UnzerjQuery',
            'type'  => 'bool',
            'value' => '0',
        ],
        [
            'group' => 'unzerother',
            'name'  => 'UnzerWebhookTimeDifference',
            'type'  => 'str',
            'value' => '5',
        ],
        // this options are invisible because of missing group
        [
            'group' => '',
            'name'  => 'sandboxApplePayPaymentKeyId',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => '',
            'name'  => 'sandboxApplePayPaymentCertificateId',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => '',
            'name'  => 'productionApplePayPaymentKeyId',
            'type'  => 'str',
            'value' => ''
        ],
        [
            'group' => '',
            'name'  => 'productionApplePayPaymentCertificateId',
            'type'  => 'str',
            'value' => ''
        ],
    ],
    'events'      => [
        'onActivate'   => '\OxidSolutionCatalysts\Unzer\Core\Events::onActivate',
        'onDeactivate' => '\OxidSolutionCatalysts\Unzer\Core\Events::onDeActivate',
    ],
];
