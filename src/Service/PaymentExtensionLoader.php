<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Service;

use OxidEsales\Eshop\Application\Model\Payment as PaymentModel;
use OxidSolutionCatalysts\Unzer\Core\UnzerDefinitions;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\AliPay;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\ApplePay;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\Bancontact;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\Card;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\EPS;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\GiroPay;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\Ideal;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\Installment;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\InstallmentPaylater;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\Invoice;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\InvoiceOld;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\PayPal;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\PIS;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\PrePayment;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\Przelewy24;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\Sepa;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\SepaSecured;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\Sofort;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\UnzerPayment as AbstractUnzerPayment;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\WeChatPay;
use OxidSolutionCatalysts\Unzer\Service\SavedPayment\SavedPaymentSessionService;

class PaymentExtensionLoader
{
    public const UNZERCLASSNAMEMAPPING = [
        UnzerDefinitions::ALIPAY_UNZER_PAYMENT_ID => AliPay::class,
        UnzerDefinitions::APPLEPAY_UNZER_PAYMENT_ID => ApplePay::class,
        UnzerDefinitions::BANCONTACT_UNZER_PAYMENT_ID => Bancontact::class,
        UnzerDefinitions::CARD_UNZER_PAYMENT_ID => Card::class,
        UnzerDefinitions::EPS_UNZER_PAYMENT_ID => EPS::class,
        UnzerDefinitions::GIROPAY_UNZER_PAYMENT_ID => GiroPay::class,
        UnzerDefinitions::IDEAL_UNZER_PAYMENT_ID => Ideal::class,
        UnzerDefinitions::INSTALLMENT_UNZER_PAYMENT_ID => Installment::class,
        UnzerDefinitions::INSTALLMENT_UNZER_PAYLATER_PAYMENT_ID => InstallmentPaylater::class,
        UnzerDefinitions::INVOICE_UNZER_PAYMENT_ID => Invoice::class,
        UnzerDefinitions::OLD_INVOICE_UNZER_PAYMENT_ID => InvoiceOld::class,
        UnzerDefinitions::PAYPAL_UNZER_PAYMENT_ID => PayPal::class,
        UnzerDefinitions::PIS_UNZER_PAYMENT_ID => PIS::class,
        UnzerDefinitions::PREPAYMENT_UNZER_PAYMENT_ID => PrePayment::class,
        UnzerDefinitions::PRZELEWY24_UNZER_PAYMENT_ID => Przelewy24::class,
        UnzerDefinitions::SEPA_UNZER_PAYMENT_ID => Sepa::class,
        UnzerDefinitions::SEPA_SECURED_UNZER_PAYMENT_ID => SepaSecured::class,
        UnzerDefinitions::SOFORT_UNZER_PAYMENT_ID => Sofort::class,
        UnzerDefinitions::WECHATPAY_UNZER_PAYMENT_ID => WeChatPay::class,
    ];

    private UnzerSDKLoader $unzerSdkLoader;

    private Unzer $unzerService;

    private DebugHandler $logger;

    private SavedPaymentSessionService $paymentSession;

    private TmpOrderService $tmpOrderService;


    public function __construct(
        UnzerSDKLoader $unzerSDKLoader,
        Unzer $unzerService,
        DebugHandler $logger,
        SavedPaymentSessionService $paymentSession,
        TmpOrderService $tmpOrderService
    ) {
        $this->unzerSdkLoader = $unzerSDKLoader;
        $this->unzerService = $unzerService;
        $this->logger = $logger;
        $this->paymentSession = $paymentSession;
        $this->tmpOrderService = $tmpOrderService;
    }

    /**
     * Please only use this method if you want to have static information about the payment method and do not
     * need functions of the SDK. The SDK must always be loaded with the correct credentials. This is only
     * guaranteed if method getPaymentExtensionByCustomerTypeAndCurrency is used, as only this loads the SDK
     * with the correct credentials at all times
     */
    public function getPaymentExtension(PaymentModel $payment): AbstractUnzerPayment
    {
        return oxNew(
            self::UNZERCLASSNAMEMAPPING[$payment->getId()],
            $this->unzerSdkLoader->getUnzerSDK(),
            $this->unzerService,
            $this->logger,
            $this->paymentSession,
            $this->tmpOrderService
        );
    }

    /**
     * Please only use this method if you need the SDK. This is the only way to load the SDK with the correct
     * credentials. The getPaymentExtension method is only used to obtain static information about the payment
     * method.
     * @param PaymentModel $payment
     * @param string $customerType
     * @param string $currency
     * @return AbstractUnzerPayment
     */
    public function getPaymentExtensionByCustomerTypeAndCurrency(
        PaymentModel $payment,
        string $customerType,
        string $currency
    ): AbstractUnzerPayment {
        return oxNew(
            self::UNZERCLASSNAMEMAPPING[$payment->getId()],
            $this->unzerSdkLoader->getUnzerSDK(
                $payment->getId(),
                $currency,
                $customerType
            ),
            $this->unzerService,
            $this->logger,
            $this->paymentSession,
            $this->tmpOrderService
        );
    }
}
