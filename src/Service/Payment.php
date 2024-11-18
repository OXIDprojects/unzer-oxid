<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Service;

use Exception;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\Payment as PaymentModel;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Session;
use OxidSolutionCatalysts\Unzer\Traits\Request;
use OxidSolutionCatalysts\Unzer\Core\UnzerDefinitions;
use OxidSolutionCatalysts\Unzer\Exception\Redirect;
use OxidSolutionCatalysts\Unzer\Exception\RedirectWithMessage;
use OxidSolutionCatalysts\Unzer\Exception\UnzerException;
use OxidSolutionCatalysts\Unzer\Model\TmpOrder;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\UnzerPayment as AbstractUnzerPayment;
use OxidSolutionCatalysts\Unzer\Service\Transaction as TransactionService;
use stdClass;
use UnzerSDK\Constants\PaymentState;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment as UnzerPayment;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Resources\PaymentTypes\Prepayment as UnzerSDKPrepayment;
use UnzerSDK\Resources\PaymentTypes\Invoice as UnzerSDKInvoice;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Shipment;

/**
 * TODO: Decrease count of dependencies to 13
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * TODO: Decrease overall complexity below 50
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Payment
{
    use Request;

    public const STATUS_OK = "OK";
    public const STATUS_CANCELED = "CANCELED";
    public const STATUS_NOT_FINISHED = "NOT_FINISHED";
    public const STATUS_ERROR = "ERROR";

    protected Session $session;

    protected PaymentExtensionLoader $paymentExtLoader;

    protected Translator $translator;

    protected Unzer $unzerService;

    protected UnzerSDKLoader $unzerSDKLoader;

    protected ?UnzerPayment $sessionUnzerPayment = null;


    protected string $redirectUrl = '';

    protected string $pdfLink = '';

    protected Transaction $transactionService;

    /**
     * @param Session $session
     * @param PaymentExtensionLoader $paymentExtLoader
     * @param Translator $translator
     * @param Unzer $unzerService
     * @param UnzerSDKLoader $unzerSDKLoader
     * @param TransactionService $transactionService
     */
    public function __construct(
        Session $session,
        PaymentExtensionLoader $paymentExtLoader,
        Translator $translator,
        Unzer $unzerService,
        UnzerSDKLoader $unzerSDKLoader,
        TransactionService $transactionService
    ) {
        $this->session = $session;
        $this->paymentExtLoader = $paymentExtLoader;
        $this->translator = $translator;
        $this->unzerService = $unzerService;
        $this->unzerSDKLoader = $unzerSDKLoader;
        $this->transactionService = $transactionService;
    }

    /**
     * @throws Redirect
     * @throws RedirectWithMessage
     * @throws UnzerException
     */
    public function executeUnzerPayment(PaymentModel $paymentModel): bool
    {
        $paymentExtension = null;
        $customerType = $this->getUnzerStringRequestParameter('unzer_customer_type');
        $user = $this->session->getUser();
        $basket = $this->session->getBasket();
        $currency = $basket->getBasketCurrency()->name;

        try {
            $paymentExtension = $this->paymentExtLoader->getPaymentExtensionByCustomerTypeAndCurrency(
                $paymentModel,
                $customerType,
                $currency
            );

            $oOrder = oxNew(Order::class);
            /** @var Order $oOrder */
            $oOrder->createTmpOrder($basket, $user, $paymentExtension->getUnzerOrderId());

            $paymentExtension->execute(
                $user,
                $basket
            );

            $paymentStatus = $this->getUnzerPaymentStatus() !== self::STATUS_ERROR;

            if ($this->redirectUrl) {
                throw new Redirect($this->redirectUrl);
            }

            if ($this->pdfLink) {
                throw new Redirect($this->unzerService->preparePdfConfirmRedirectUrl());
            }
        } catch (Redirect $e) {
            throw $e;
        } catch (UnzerApiException $e) {
            throw new RedirectWithMessage(
                $this->unzerService->prepareOrderRedirectUrl(
                    $paymentExtension instanceof AbstractUnzerPayment && $paymentExtension->redirectUrlNeedPending()
                ),
                $this->translator->translateCode($e->getErrorId(), $e->getClientMessage())
            );
        } catch (Exception $e) {
            throw new RedirectWithMessage(
                $this->unzerService->prepareOrderRedirectUrl(false),
                $e->getMessage()
            );
        }

        return $paymentStatus;
    }

    /**
     * @return bool
     */
    public function removeTemporaryOrder(): bool
    {
        $orderModel = oxNew(Order::class);
        /** @var string $sessionOrderId */
        $sessionOrderId = $this->session->getVariable('sess_challenge');
        return $orderModel->delete($sessionOrderId);
    }

    /**
     * @return string
     * @throws UnzerApiException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getUnzerPaymentStatus(): string
    {
        $result = self::STATUS_ERROR;
        /** @var UnzerPayment $sessionUnzerPayment */
        $sessionUnzerPayment = $this->getSessionUnzerPayment();

        if (is_null($sessionUnzerPayment)) {
            return $result;
        }

        if ($sessionUnzerPayment->getState() === PaymentState::STATE_CANCELED) {
            return self::STATUS_CANCELED;
        }

        $transaction = $sessionUnzerPayment->getInitialTransaction();

        if ($sessionUnzerPayment->isCompleted()) {
            $result = self::STATUS_OK;
        } elseif ($sessionUnzerPayment->isPending() && $transaction) {
            if ($transaction->isSuccess()) {
                $this->pdfLink = '';
                if ($transaction instanceof Authorization) {
                    $pdfLink = $transaction->getPDFLink();
                    if ($pdfLink) {
                        $this->pdfLink = $pdfLink;
                    }
                    $result = self::STATUS_NOT_FINISHED;
                }
            } elseif ($transaction->isPending()) {
                $result = self::STATUS_NOT_FINISHED;
                /** @var string $redirectUrl */
                $redirectUrl = $transaction->getRedirectUrl();
                $this->redirectUrl = is_null($redirectUrl) ? "" : $redirectUrl;
            } elseif ($transaction->isError()) {
                throw new Exception($this->translator->translateCode(
                    $transaction->getMessage()->getCode(),
                    "Error in transaction for customer " . $transaction->getMessage()->getCustomer()
                ));
            }
        }

        return $result;
    }

    /**
     * @return string
     * @throws UnzerApiException
     */
    public function getUnzerOrderId(): string
    {
        $result = '';
        $sessionUnzerPayment = $this->getSessionUnzerPayment();
        if ($sessionUnzerPayment) {
            $transaction = $sessionUnzerPayment->getInitialTransaction();
            if ($transaction) {
                $result = (string) $transaction->getOrderId();
            }
        }
        return $result;
    }

    /**
     * @param string $paymentId
     * @param string $currency
     * @param string $customerType
     * @return \UnzerSDK\Unzer
    */
    protected function getUnzerSDK(
        string $paymentId = '',
        string $currency = '',
        string $customerType = ''
    ): \UnzerSDK\Unzer {
        return $this->unzerSDKLoader->getUnzerSDK($paymentId, $currency, $customerType);
    }

    /**
     * @param bool $noCache
     * @return UnzerPayment|null
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function getSessionUnzerPayment(bool $noCache = false): ?UnzerPayment
    {
        $result = null;
        if ($noCache === false) {
            if ($this->sessionUnzerPayment instanceof UnzerPayment) {
                return $this->sessionUnzerPayment;
            }
        }

        $uzrPaymentId = $this->session->getVariable('UnzerPaymentId');

        if (is_string($uzrPaymentId)) {
            /** @var string $sessionOrderId */
            $sessionOrderId = $this->session->getVariable('sess_challenge');

            if ($sessionOrderId == null) {
                return null;
            }
            /** @var Order $order */
            $order = oxNew(Order::class);
            $order->load($sessionOrderId);

            $paymentType = $this->getPaymentType($sessionOrderId, $order);
            $currency = $this->getOrderCurrency($sessionOrderId, $order, $paymentType);
            $customerType = $this->getCustomerType($currency, $paymentType);

            try {
                $result = $this->unzerSDKLoader->getUnzerSDK(
                    $paymentType,
                    $currency,
                    $customerType
                )->fetchPayment($uzrPaymentId);
            } catch (UnzerApiException $e) {
                Registry::getLogger()->warning(
                    'Payment not found with key: ' . $uzrPaymentId
                );
            }
            $this->sessionUnzerPayment = $result;
        }

        return $result;
    }

    private function getCustomerType(?string $currency, string $paymentType): string
    {
        $customerType = 'B2C';

        if (($currency !== null) && $this->isPaylaterInvoice($paymentType)) {
            $customerInRequest = $this->getUnzerStringRequestParameter('unzer_customer_type');
            if ($customerInRequest !== 'B2C') {
                $customerType = 'B2B';
            }
        }
        return $customerType;
    }

    private function isPaylaterInvoice(string $paymentType): bool
    {
        return in_array($paymentType, [
            UnzerDefinitions::INVOICE_UNZER_PAYMENT_ID,
            UnzerDefinitions::INSTALLMENT_UNZER_PAYLATER_PAYMENT_ID,
            UnzerDefinitions::INSTALLMENT_UNZER_PAYMENT_ID,
        ], true);
    }

    private function getOrderCurrency(string $sessionOrderId, Order $order, string $paymentType): string
    {
        /** @var string $currency */
        $currency = $order->getFieldData('oxcurrency') ?? '';

        if ($this->isPaylaterInvoice($paymentType)) {
            $tmpOrder = oxNew(TmpOrder::class)->getTmpOrderByOxOrderId($sessionOrderId);
            if ($tmpOrder !== null) {
                /** @var stdClass{name: string} $orderCurrency */
                $orderCurencyStdCls = $tmpOrder->getOrderCurrency();
                $currency = $orderCurencyStdCls->name;
            }
        }
        return $currency;
    }

    private function getPaymentType(string $sessionOrderId, Order $order): string
    {
        $paymentType = $order->getFieldData('oxpaymenttype');

        if (empty($paymentType)) {
            $tmpOrder = oxNew(TmpOrder::class)->getTmpOrderByOxOrderId($sessionOrderId);
            if ($tmpOrder !== null) {
                $paymentType = $tmpOrder->getFieldData('oxpaymenttype');
            }
        }

        if (is_string($paymentType)) {
            return $paymentType;
        }

        return '';
    }

    /**
     * @param Order $oOrder
     * @param string $unzerid
     * @param string $chargeid
     * @param float $amount
     * @param string $reason
     * @return UnzerApiException|bool
     *
     * @throws \Exception
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function doUnzerCancel($oOrder, $unzerid, $chargeid, $amount, $reason)
    {
        try {
            $sdk = $this->unzerSDKLoader->getUnzerSDKbyPaymentType($unzerid);

            if ($chargeid) {
                $unzerCharge = $sdk->fetchChargeById($unzerid, $chargeid);
                $cancellation = $unzerCharge->cancel($amount, $reason);
            } else {
                $payment = $sdk->fetchPayment($unzerid);
                $cancellation = new Cancellation($amount);
                $cancellation->setReasonCode($reason);
                $cancellation = $sdk->cancelChargedPayment($payment, $cancellation);
            }

            /** @var string $oxuserid */
            $oxuserid = $oOrder->getFieldData('oxuserid');
            $this->transactionService->writeCancellationToDB(
                $oOrder->getId(),
                $oxuserid,
                $cancellation
            );
        } catch (UnzerApiException $e) {
            return $e;
        }
        return true;
    }

    /**
     * @param Order|null $oOrder
     * @param string $unzerid
     * @param float $amount
     * @return UnzerApiException|bool
     * @throws \Exception
     */
    public function doUnzerCollect($oOrder, $unzerid, $amount)
    {
        if (!($oOrder instanceof Order)) {
            return false;
        }

        try {
            $unzerPayment = $this->unzerSDKLoader->getUnzerSDKbyPaymentType($unzerid)->fetchPayment($unzerid);

            /** @var Authorization $authorization */
            $authorization = $unzerPayment->getAuthorization();
            if (null === $authorization) {
                return false;
            }
            $charge = $authorization->charge($amount);

            /** @var string $oxuserid */
            $oxuserid = $oOrder->getFieldData('oxuserid');
            $this->transactionService->writeChargeToDB(
                $oOrder->getId(),
                $oxuserid,
                $charge
            );
            $payment = $charge->getPayment();
            if (
                !is_null($payment) &&
                $charge->isSuccess() &&
                $payment->getAmount()->getRemaining() === 0.0
            ) {
                $oOrder->markUnzerOrderAsPaid();
            }
        } catch (UnzerApiException $e) {
            return $e;
        }

        return true;
    }

    /**
     * @param Order|null $oOrder
     * @param string $unzerid
     * @param float $amount
     * @return UnzerApiException|bool
     * @throws \Exception
     */
    public function doUnzerAuthorizationCancel($oOrder, $unzerid, $amount)
    {
        if (!($oOrder instanceof Order)) {
            return false;
        }

        try {
            $sdk = $this->unzerSDKLoader->getUnzerSDKbyPaymentType($unzerid);
            $unzerPayment = $sdk->fetchPayment($unzerid);
            $cancellation = new Cancellation($amount);
            $sdk->cancelAuthorizedPayment($unzerPayment, $cancellation);

            /** @var string $oxuserid */
            $oxuserid = $oOrder->getFieldData('oxuserid');
            $this->transactionService->writeCancellationToDB(
                $oOrder->getId(),
                $oxuserid,
                $cancellation
            );
        } catch (UnzerApiException $e) {
            return $e;
        }

        return true;
    }

    /**
     * @param Order|null $oOrder
     * @param string|null $sPaymentId
     * @return UnzerApiException|bool
     *
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @throws UnzerApiException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function sendShipmentNotification($oOrder, $sPaymentId = null)
    {
        if (!($oOrder instanceof Order)) {
            return false;
        }

        $sPaymentId = $sPaymentId ?? $this->transactionService->getPaymentIdByOrderId($oOrder->getId());
        $transactionDetails = $this->transactionService->getCustomerTypeAndCurrencyByOrderId($oOrder->getId());

        $blSuccess = false;

        if ($sPaymentId) {
            $sdk = $this->getUnzerSDK(
                $sPaymentId,
                $transactionDetails['currency'],
                $transactionDetails['customertype']
            );
            /** @var string $sInvoiceNr */
            $sInvoiceNr = $oOrder->getUnzerInvoiceNr();

            $unzerPayment = $sdk->fetchPayment($sPaymentId);

            if ($unzerPayment->getPaymentType() instanceof InstallmentSecured) {
                $this->setInstallmentDueDate($unzerPayment);
            }
            try {
                $shipment = $sdk->ship(
                    $unzerPayment,
                    $sInvoiceNr,
                    $oOrder->getId()
                );
                $unzerPayment->addShipment($shipment);

                foreach ($unzerPayment->getShipments() as $unzShipment) {
                    if ($unzShipment->isSuccess()) {
                        $blSuccess = true;
                    }
                }
            } catch (UnzerApiException $e) {
                $blSuccess = $e;
            }

            if (!$blSuccess && $unzerPayment->getAmount()->getRemaining() === 0.0) {
                try {
                    /** @var string $oxuserid */
                    $oxuserid = $oOrder->getFieldData('oxuserid');
                    /** @var Shipment $shipment */
                    $shipment = $unzerPayment->ship($sInvoiceNr);
                    $blSuccess = $this->transactionService->writeTransactionToDB(
                        $oOrder->getId(),
                        $oxuserid,
                        $unzerPayment,
                        $shipment
                    );
                } catch (UnzerApiException $e) {
                    $blSuccess = $e;
                }
            }
        }

        return $blSuccess;
    }

    /**
     * @param UnzerPayment $unzerPayment
     * @return BasePaymentType|AbstractUnzerResource The updated PaymentType object.
     * @throws UnzerApiException
     */
    public function setInstallmentDueDate($unzerPayment)
    {
        /** @var InstallmentSecured $installment */
        $installment = $unzerPayment->getPaymentType();
        if ($installment->getInvoiceDate() === null) {
            $installment->setInvoiceDate($this->getYesterdaysTimestamp());
        }
        if ($installment->getInvoiceDueDate() === null) {
            $installment->setInvoiceDueDate($this->getTomorrowsTimestamp());
        }

        return $unzerPayment->getUnzerObject()->updatePaymentType($installment);
    }

    public function getYesterdaysTimestamp(): string
    {
        return date('Y-m-d', strtotime("-1 days"));
    }

    public function getTomorrowsTimestamp(): string
    {
        return date('Y-m-d', strtotime("+1 days"));
    }

    /**
     * @return bool
     */
    public function isPdfSession(): bool
    {
        return $this->getUnzerBoolRequestParameter('pdfConfirm');
    }

    public function isInvoice(): bool
    {
        $sessionPayment = $this->getSessionUnzerPayment();

        if ($sessionPayment === null) {
            return false;
        }

        return $sessionPayment->getPaymentType() instanceof UnzerSDKInvoice;
    }

    public function isPrepayment(): bool
    {
        $sessionPayment = $this->getSessionUnzerPayment();

        if ($sessionPayment === null) {
            return false;
        }

        return $sessionPayment->getPaymentType() instanceof UnzerSDKPrepayment;
    }
}
