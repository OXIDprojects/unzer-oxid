<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\Unzer\Traits\Request;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidSolutionCatalysts\Unzer\Model\Order as UnzerOrder;
use OxidSolutionCatalysts\Unzer\Model\TransactionList;
use OxidSolutionCatalysts\Unzer\Service\Payment as UnzerPaymentService;
use OxidSolutionCatalysts\Unzer\Service\Transaction as TransactionService;
use OxidSolutionCatalysts\Unzer\Service\Translator;
use OxidSolutionCatalysts\Unzer\Service\UnzerSDKLoader;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Shipment;
use UnzerSDK\Unzer;
use DateTime;
use DateTimeZone;
use OxidSolutionCatalysts\Unzer\Service\Payment\GetPaymentType;

/**
 * Order class wrapper for Unzer module
 *
 * TODO: Decrease count of dependencies to 13
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * TODO: Decrease complexity to 50 or under
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AdminOrderController extends AdminDetailsController
{
    use ServiceContainer;
    use Request;

    protected ?Order $editObject = null;
    protected Payment $oPayment;
    protected string $sTypeId;

    /**
     * Executes parent method parent::render()
     * name of template file "oscunzer_order.tpl".
     *
     * @return string
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function render()
    {
        parent::render();

        $this->_aViewData["sOxid"] = $this->getEditObjectId();

        $transactionList = oxNew(TransactionList::class);
        $transactionList->getTransactionList($this->getEditObjectId());
        if ($transactionList->count()) {
            $this->_aViewData['oUnzerTransactions'] = $transactionList;
        }

        if ($this->isUnzerOrder()) {
            /** @var Order $oOrder */
            $oOrder = $this->getEditObject();

            $this->_aViewData['paymentTitle'] = $this->oPayment->getFieldData('OXDESC');
            $this->_aViewData['oOrder'] = $oOrder;
            /** @var string $sPaymentId */
            $sPaymentId = $oOrder->getFieldData('oxpaymenttype');

            $transactionService = $this->getServiceFromContainer(TransactionService::class);
            $orderId = $this->getEditObjectId();
            $paymentId = $transactionService->getPaymentIdByOrderId($orderId, true); //somthesing like s-chg-XXXX
            $this->sTypeId = $paymentId;
            $this->_aViewData['sTypeId'] = $this->sTypeId;
            if ($this->sTypeId) {
                $this->getUnzerViewData($sPaymentId, $this->sTypeId);
            }
            $this->_aViewData['uzrCurrency'] = $oOrder->getFieldData('oxcurrency');
        } else {
            $translator = $this->getServiceFromContainer(Translator::class);
            $this->_aViewData['sMessage'] = $translator->translate("OSCUNZER_NO_UNZER_ORDER");
        }

        return "oscunzer_order.tpl";
    }

    public function getUnzerSDK(string $paymentId = '', string $currency = '', string $customerType = ''): Unzer
    {
        return $this->getServiceFromContainer(UnzerSDKLoader::class)
            ->getUnzerSDK($paymentId, $currency, $customerType);
    }

    /**
     * @param string $sPaymentId - OXID Payment ID
     * @param string $sTypeId - Unzer Type ID
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function getUnzerViewData(string $sPaymentId, string $sTypeId): void
    {
        try {
            $transactionInfo = $this->getCustomerTypeAndCurrencyFromTransaction();
            // initialize proper SDK object
            $sdk = $this->getUnzerSDK($sPaymentId, $transactionInfo['currency'], $transactionInfo['customertype']);
            /** @var \UnzerSDK\Resources\Payment $unzerPayment */
            $unzerPayment = $sdk->fetchPayment($sTypeId);
            $fCharged = 0.0;

            $paymentType = $unzerPayment->getPaymentType();
            /** @var Order $editObject */
            $editObject = $this->getEditObject();
            $this->_aViewData['totalBasketAmount'] = $editObject->getTotalOrderSum();
            $this->_aViewData['totalBasketPrice'] = sprintf(
                '%s %s',
                $editObject->getFormattedTotalOrderSum(),
                $unzerPayment->getCurrency()
            );
            $this->_aViewData["blShipment"] = ($paymentType instanceof InstallmentSecured);
            $shipments = [];

            $blShipped = false;
            /** @var Shipment $shipment */
            foreach ($unzerPayment->getShipments() as $shipment) {
                $aRv = [];
                $aRv['shipingDate'] = $this->toLocalDateString($shipment->getDate() ?? '');
                $aRv['shipId'] = $shipment->getId();
                $aRv['invoiceid'] = $unzerPayment->getInvoiceId();
                $aRv['amount'] = $shipment->getAmount();
                $aRv['success'] = $shipment->isSuccess();

                $blShipped = $shipment->isSuccess();
                $shipments[] = $aRv;
            }
            $this->_aViewData["aShipments"] = $shipments;
            $this->_aViewData["blSuccessShipped"] = $blShipped;

            if ($unzerPayment->getAuthorization()) {
                /** @var Authorization $unzAuthorization */
                $unzAuthorization = $unzerPayment->getAuthorization();
                // an "auth-cancel" must be considered as "charged", to make the calculation work correctly
                $fCharged = $unzAuthorization->getCancelledAmount();
                $this->_aViewData["AuthAmountRemaining"] = $unzerPayment->getAmount()->getRemaining();
                $this->addAuthorizationViewData($unzAuthorization);
                $this->_aViewData['AuthCur'] = $unzerPayment->getCurrency();
            }

            $charges = [];
            $isChargeBack = $unzerPayment->isChargeBack();
            $this->_aViewData['isChargeBack']  = $isChargeBack;
            if (!$unzerPayment->isCanceled() && !$isChargeBack) {
                /** @var Charge $charge */
                foreach ($unzerPayment->getCharges() as $charge) {
                    if ($charge->isSuccess()) {
                        $this->addChargeViewData($charge);
                        $aRv = [];
                        $aRv['chargedAmount'] = $charge->getAmount();
                        $aRv['cancelledAmount'] = $charge->getCancelledAmount();
                        $aRv['chargeId'] = $charge->getId();
                        $aRv['cancellationPossible'] = $charge->getAmount() > $charge->getCancelledAmount();
                        $fCharged += $charge->getAmount();
                        // datetime from unzer is in GMT, convert it to local datetime
                        $aRv['chargeDate'] = $this->toLocalDateString($charge->getDate() ?? '');

                        $charges[] = $aRv;
                    }
                }
            }
            $this->_aViewData['totalAmountCharge'] = $fCharged;
            $this->_aViewData['remainingAmountCharge'] = floatval($editObject->getTotalOrderSum()) - $fCharged;

            $fCancelled = $this->getFullCancelled($unzerPayment);
            $this->_aViewData['totalAmountCancel'] = $fCancelled;
            $this->_aViewData['canCancelAmount'] = $fCharged - $fCancelled;

            $this->_aViewData['blCancellationAllowed'] = $fCancelled < $fCharged;
            $this->_aViewData['aCharges'] = $charges;
            $this->_aViewData['aCancellations'] = $this->getCancellationsViewData((string)$unzerPayment->getTraceId());
            $this->_aViewData['blCancelReasonReq'] = $this->isCancelReasonRequired();

            if (
                $editObject->getFieldData('oxpaid') == '0000-00-00 00:00:00' &&
                $fCharged == $unzerPayment->getAmount()->getTotal()
            ) {
                /** @var UnzerOrder $editObject */
                $editObject->markUnzerOrderAsPaid();
                $this->forceReloadListFrame();
            }
        } catch (\Exception $e) {
            Registry::getUtilsView()->addErrorToDisplay(
                $e->getMessage()
            );
        }
    }

    private function getCancellationsViewData(string $traceId): array
    {
        $transactionList = oxNew(TransactionList::class);
        $transactionList->getTransactionListByTraceId($traceId);

        $cancellations = [];

        foreach ($transactionList as $transaction) {
            if ($transaction === null) {
                continue;
            }
            $actionData = ($transaction->oscunzertransaction__oxactiondate !== null ) ?
                $transaction->oscunzertransaction__oxactiondate->value : '';

            $date = $this->toLocalDateString($actionData);

            $amount = ($transaction->oscunzertransaction__amount)
                ? $transaction->oscunzertransaction__amount->value : 0.0;

            $reason = ($transaction->oscunzertransaction__cancelreason)
                ? $transaction->oscunzertransaction__cancelreason->value : '';

            $cancellations[] = [
                'cancelledAmount' => $amount,
                'cancelDate' => $date,
                'cancellationId' => $transaction->getId(),
                'cancelReason' => $reason,
            ];
        }

        return $cancellations;
    }

    /**
     * Adding HolderData to View (if there is any)
     *
     * @param Charge $charge
     * @return void
     */
    protected function addChargeViewData(Charge $charge)
    {
        $holderData = [];
        $holderData['bic'] = $charge->getBic();
        $holderData['iban'] = $charge->getIban();
        $holderData['descriptor'] = $charge->getDescriptor();
        $holderData['holder'] = $charge->getHolder();
        $isDataSet = true;
        foreach ($holderData as $wert) {
            if (empty($wert)) {
                $isDataSet = false;
                break;
            }
        }
        if ($isDataSet === true) {
            $this->_aViewData["holderData"] = $holderData;
        }
    }
    protected function addAuthorizationViewData(Authorization $authorization): void
    {
        $date = '';
        $datetime = $authorization->getFetchedAt();
        if ($datetime) {
            $date = $datetime->format('Y-m-d H:i:s');
        }
        $this->_aViewData["AuthFetchedAt"] = $this->toLocalDateString($date);
        $this->_aViewData["AuthShortId"] = $authorization->getShortId();
        $this->_aViewData["AuthId"] = $authorization->getId();
        $this->_aViewData["AuthAmount"] = $authorization->getAmount();
        $holderData = [];
        $holderData['bic'] = $authorization->getBic();
        $holderData['iban'] = $authorization->getIban();
        $holderData['descriptor'] = $authorization->getDescriptor();
        $holderData['holder'] = $authorization->getHolder();
        $isDataSet = true;
        foreach ($holderData as $wert) {
            if (empty($wert)) {
                $isDataSet = false;
                break;
            }
        }
        if ($isDataSet === true) {
            $this->_aViewData["holderData"] = $holderData;
        }
    }

    /**
     * @return array
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function getCustomerTypeAndCurrencyFromTransaction(): array
    {
        $transactionService = $this->getServiceFromContainer(TransactionService::class);
        return $transactionService->getCustomerTypeAndCurrencyByOrderId($this->getEditObjectId());
    }

    /**
     * @return void
     */
    protected function forceReloadListFrame(): void
    {
        // we need to set the "edit object id", which will automatically be recognized to reload the list (admin area)
        /** @var Order $oOrder */
        $oOrder = $this->getEditObject();
        $this->setEditObjectId($oOrder->getId());
    }

    /**
     * @return void
     */
    public function sendShipmentNotification(): void
    {
        $unzerid = $this->getUnzerStringRequestParameter('unzerid');
        $translator = $this->getServiceFromContainer(Translator::class);

        if ($unzerid) {
            $paymentService = $this->getServiceFromContainer(UnzerPaymentService::class);
            /** @var \OxidSolutionCatalysts\Unzer\Model\Order $oOrder */
            $oOrder = $this->getEditObject();
            $oStatus = $paymentService->sendShipmentNotification($oOrder, $unzerid);

            if ($oStatus instanceof UnzerApiException) {
                $this->_aViewData['errShip'] = $translator->translateCode(
                    $oStatus->getErrorId(),
                    $oStatus->getMessage()
                );
            }
        }
    }

    /**
     * @return void
     */
    public function doUnzerCollect(): void
    {
        $this->forceReloadListFrame();
        $unzerid = $this->getUnzerStringRequestParameter('unzerid');
        $amount = $this->getUnzerFloatRequestParameter('amount');

        $translator = $this->getServiceFromContainer(Translator::class);

        $paymentService = $this->getServiceFromContainer(UnzerPaymentService::class);
        /** @var \OxidSolutionCatalysts\Unzer\Model\Order $oOrder */
        $oOrder = $this->getEditObject();
        $oStatus = $paymentService->doUnzerCollect($oOrder, $unzerid, $amount);

        if ($oStatus instanceof UnzerApiException) {
            $this->_aViewData['errAuth'] = $translator->translateCode($oStatus->getErrorId(), $oStatus->getMessage());
        }
    }

    /**
     * @return void
     */
    public function doUnzerCancel()
    {
        $this->forceReloadListFrame();
        $unzerid = $this->getUnzerStringRequestParameter('unzerid');
        $chargeid = $this->getUnzerStringRequestParameter('chargeid');
        $amount = $this->getUnzerFloatRequestParameter('amount');

        if (!$amount) {
            return; //should be an error shown/logged to the admin frontend?
        }

        $fCharged = $this->getUnzerFloatRequestParameter('chargedamount');
        $reason = $this->getUnzerStringRequestParameter('reason');

        $translator = $this->getServiceFromContainer(Translator::class);
        if ($reason === "NONE" && $this->isUnzerOrder() && $this->isCancelReasonRequired()) {
            $this->_aViewData['errCancel'] = $chargeid . ": "
                . $translator->translate('OSCUNZER_CANCEL_MISSINGREASON') . " " . $amount;
            return;
        }

        if ($reason === "NONE") {
            $reason = null;
        }

        if ($amount > $fCharged) {
            $this->_aViewData['errCancel'] = $chargeid . ": "
                . $translator->translate('OSCUNZER_CANCEL_ERR_AMOUNT') . " " . $amount;
            return;
        }
        $paymentService = $this->getServiceFromContainer(UnzerPaymentService::class);
        /** @var \OxidSolutionCatalysts\Unzer\Model\Order $oOrder */
        $oOrder = $this->getEditObject();
        $oStatus = $paymentService->doUnzerCancel($oOrder, $unzerid, $chargeid, $amount, (string)$reason);
        if ($oStatus instanceof UnzerApiException) {
            $this->_aViewData['errCancel'] = $translator->translateCode($oStatus->getErrorId(), $oStatus->getMessage());
        }
    }

    /**
     * @return void
     */
    public function doUnzerAuthorizationCancel()
    {
        $this->forceReloadListFrame();
        $unzerid = $this->getUnzerStringRequestParameter('unzerid');
        $amount = $this->getUnzerFloatRequestParameter('amount');

        $translator = $this->getServiceFromContainer(Translator::class);

        $paymentService = $this->getServiceFromContainer(UnzerPaymentService::class);
        /** @var \OxidSolutionCatalysts\Unzer\Model\Order $oOrder */
        $oOrder = $this->getEditObject();
        $oStatus = $paymentService->doUnzerAuthorizationCancel($oOrder, $unzerid, $amount);

        if ($oStatus instanceof UnzerApiException) {
            $this->_aViewData['errAuth'] = $translator->translateCode($oStatus->getErrorId(), $oStatus->getMessage());
        }

        // prove payment is canceled at unzer side, there could be an error during cancel transaction
        $paymentStatus = $this->getServiceFromContainer(GetPaymentType::class)
            ->getUnzerPaymentStatus($unzerid, $oOrder->getId());

        if (UnzerPaymentService::STATUS_CANCELED === $paymentStatus) {
            $oOrder->setFieldData('oxorder__oxtransstatus', UnzerPaymentService::STATUS_CANCELED);
            $oOrder->save();
        }
    }

    /**
     * Method checks is order was made with unzer payment
     *
     * @return bool
     */
    public function isUnzerOrder(): bool
    {
        $isUnzer = false;

        /** @var Order $order */
        $order = $this->getEditObject();
        /** @var string $oxPaymentType */
        $oxPaymentType = $order->getFieldData('oxpaymenttype');
        if ($order instanceof Order && strpos($oxPaymentType, "oscunzer") !== false) {
            $this->oPayment = oxNew(Payment::class);
            if ($this->oPayment->load($oxPaymentType)) {
                $isUnzer = true;
            }
        }

        return $isUnzer;
    }

    public function canCollectFully(): bool
    {
        if (!($this->oPayment instanceof Payment)) {
            return false;
        }

        return $this->oPayment->canCollectFully();
    }
    public function canCollectPartially(): bool
    {
        if (!($this->oPayment instanceof Payment)) {
            return false;
        }

        return $this->oPayment->canCollectPartially();
    }
    public function canRefundFully(): bool
    {
        if (!($this->oPayment instanceof Payment)) {
            return false;
        }

        return $this->oPayment->canRefundFully();
    }
    public function canRefundPartially(): bool
    {
        if (!($this->oPayment instanceof Payment)) {
            return false;
        }

        return $this->oPayment->canRefundPartially();
    }
    public function canRevertPartially(): bool
    {
        if (!($this->oPayment instanceof Payment)) {
            return false;
        }

        return $this->oPayment->canRevertPartially();
    }

    /**
     * @return bool
     */
    public function isCancelReasonRequired(): bool
    {
        if (!($this->oPayment instanceof Payment)) {
            return false;
        }

        return $this->oPayment->isUnzerSecuredPayment();
    }

    public function getEditObject(): ?object
    {
        $soxId = $this->getEditObjectId();
        if ($this->editObject === null && $soxId != '-1') {
            $this->editObject = oxNew(Order::class);
            $this->editObject->load($soxId);
        }

        return $this->editObject;
    }

    private function toLocalDateString(string $gmtDateString): string
    {
        $datetime = new DateTime($gmtDateString, new DateTimeZone('GMT'));
        $datetime->setTimezone(new DateTimeZone(date_default_timezone_get()));
        return $datetime->format('Y-m-d H:i:s');
    }

    private function getFullCancelled(\UnzerSDK\Resources\Payment $unzerPayment): float
    {
        $fCancelled = 0.0;
        /** @var Cancellation $cancellation */
        foreach ($unzerPayment->getCancellations() as $cancellation) {
            if ($cancellation->isSuccess()) {
                $fCancelled += $cancellation->getAmount();
            }
        }

        return $fCancelled;
    }
}
