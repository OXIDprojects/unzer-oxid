<?php

namespace OxidSolutionCatalysts\Unzer\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\Unzer\Model\Payment;
use OxidSolutionCatalysts\Unzer\Model\TransactionList;
use OxidSolutionCatalysts\Unzer\Service\Transaction as TransactionService;
use OxidSolutionCatalysts\Unzer\Service\Translator;
use OxidSolutionCatalysts\Unzer\Service\UnzerSDKLoader;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Shipment;

/**
 * Order class wrapper for Unzer module
 */
class AdminOrderController extends AdminDetailsController
{
    use ServiceContainer;
    /**
     * Active order object
     *
     */
    protected $editObject = null;

    /** @var Payment $oPaymnet */
    protected $oPaymnet = null;

    /** @var string $sPaymentId */
    protected $sPaymentId;

    /**
     * Executes parent method parent::render()
     * name of template file "oscunzer_order.tpl".
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    public function render(): string
    {
        parent::render();

        $this->_aViewData["sOxid"] = $this->getEditObjectId();

        if ($this->isUnzerOrder()) {
            /** @var Order $oOrder */
            $oOrder = $this->getEditObject();
            $oPayment = oxNew(Payment::class);
            if ($oPayment->load($oOrder->oxorder__oxpaymenttype->value) && $oPayment->isUnzerSecuredPayment()) {
                $this->_aViewData["blShipment"] = true;
            }

            $this->_aViewData['oOrder'] = $oOrder;
            $transactionList = oxNew(TransactionList::class);
            $transactionList->getTransactionList($this->getEditObjectId());
            if ($transactionList->count()) {
                $this->_aViewData['oUnzerTransactions'] = $transactionList;
            }

            $transactionService = $this->getServiceFromContainer(TransactionService::class);
            $this->sPaymentId = $transactionService->getPaymentIdByOrderId($this->getEditObjectId())[0]['TYPEID'];
            $this->_aViewData['sPaymentId'] = $this->sPaymentId;
            if ($this->sPaymentId) {
                $this->getUnzerViewData($this->sPaymentId);
            }
        } else {
            $this->_aViewData['sMessage'] = Registry::getLang()->translateString("OSCUNZER_NO_UNZER_ORDER");
        }

        return "oscunzer_order.tpl";
    }

    protected function getUnzerViewData($sPaymentId) {
        /** @var \UnzerSDK\Resources\Payment $unzerPayment */
        $unzerPayment = $this->getServiceFromContainer(UnzerSDKLoader::class)
            ->getUnzerSDK()
            ->fetchPayment($sPaymentId);

        $fCancelled = 0.0;
        $fCharged = 0.0;

        $shipments = [];
        /** @var Shipment $shipment */
        foreach ($unzerPayment->getShipments() as $shipment) {
            $aRv = [];
            $aRv['shipingDate'] = $shipment->getDate();
            $aRv['shipId'] = $shipment->getId();
            $aRv['invoiceid'] = $unzerPayment->getInvoiceId();
            $aRv['amount'] = $shipment->getAmount();

            $shipments[] = $aRv;
        }
        $this->_aViewData["aShipments"] = $shipments;

        if ($unzerPayment->getAuthorization()) {
            $unzAuthorization = $unzerPayment->getAuthorization();
            $this->_aViewData["AuthAmountRemaining"] = $unzerPayment->getAmount()->getRemaining();
            $this->_aViewData["AuthFetchedAt"] = $unzAuthorization->getFetchedAt();
            $this->_aViewData["AuthShortId"] = $unzAuthorization->getShortId();
            $this->_aViewData["AuthId"] = $unzAuthorization->getId();
            $this->_aViewData["AuthAmount"] = $unzAuthorization->getAmount();
        }
        $charges = [];
        if (!$unzerPayment->isPending() && !$unzerPayment->isCanceled()) {
            /** @var Charge $charge */
            foreach ($unzerPayment->getCharges() as $charge) {
                $aRv = [];
                $aRv['chargedAmount'] = $charge->getAmount();
                $aRv['cancelledAmount'] = $charge->getCancelledAmount();
                $aRv['chargeId'] = $charge->getId();
                $aRv['cancellationPossible'] = $charge->getAmount() > $charge->getCancelledAmount();
                if ($charge->isSuccess()) {
                    $fCharged += $charge->getAmount();
                }
                $aRv['chargeDate'] = $charge->getDate();
                $charges [] = $aRv;
            }
        }

        $cancellations = [];
        /** @var Cancellation $cancellation */
        foreach ($unzerPayment->getCancellations() as $cancellation) {
            $aRv = [];
            $aRv['cancelledAmount'] = $cancellation->getAmount();
            $aRv['cancelDate'] = $cancellation->getDate();
            $aRv['cancellationId'] = $cancellation->getId();
            $aRv['cancelReason'] = $cancellation->getReasonCode();

            if ($cancellation->isSuccess()) {
                $fCancelled += $charge->getCancelledAmount();
            }
            $cancellations[] = $aRv;
        }
        $this->_aViewData['blCancellationAllowed'] = $fCancelled < $fCharged;
        $this->_aViewData['aCharges'] = $charges;
        $this->_aViewData['aCancellations'] = $cancellations;
        $this->_aViewData['blCancelReasonReq'] = $this->isCancelReasonRequired();
    }

    public function sendShipmentNotification() {
        $unzerid = Registry::getRequest()->getRequestParameter('unzerid');
        $transactionService = $this->getServiceFromContainer(TransactionService::class);
        $translator = $this->getServiceFromContainer(Translator::class);

        if ($unzerid) {
            try {
                $unzerPayment = $this->getServiceFromContainer(UnzerSDKLoader::class)
                    ->getUnzerSDK()
                    ->fetchPayment($unzerid);
                $sInvoiceNr = $this->getEditObject()->getUnzerInvoiceNr();
                $transactionService->writeTransactionToDB($this->getEditObject()->getId(),
                    $this->getEditObject()->oxorder__oxuserid->value, $unzerPayment, $unzerPayment->ship($sInvoiceNr));
            } catch (\Exception $e) {
                $this->_aViewData['errShip'] = $translator->translateCode($e->getCode(), $e->getMessage());
            }
        }
    }

    public function doUnzerCollect() {
        $unzerid = Registry::getRequest()->getRequestParameter('unzerid');
        $amount = (float) Registry::getRequest()->getRequestParameter('amount');
        $transactionService = $this->getServiceFromContainer(TransactionService::class);
        $translator = $this->getServiceFromContainer(Translator::class);
        try {
            $unzerPayment = $this->getServiceFromContainer(UnzerSDKLoader::class)
                ->getUnzerSDK()
                ->fetchPayment($unzerid);

            $charge = $unzerPayment->getAuthorization()->charge($amount);
            $transactionService->writeChargeToDB($this->getEditObjectId(), $this->getEditObject()->oxorder__oxuserid->value, $charge);
            if ($charge->isSuccess() && $charge->getPayment()->getAmount()->getRemaining() == 0) {
                $this->getEditObject()->markUnzerOrderAsPaid();
            }
        }
        catch (\Exception $e) {
            $this->_aViewData['errAuth'] = $translator->translateCode($e->getCode(), $e->getMessage());
        }
    }

    public function doUnzerCancel() {
        $unzerid = Registry::getRequest()->getRequestParameter('unzerid');
        $chargeid = Registry::getRequest()->getRequestParameter('chargeid');
        $amount = (float) Registry::getRequest()->getRequestParameter('amount');
        $fCharged = (float) Registry::getRequest()->getRequestParameter('chargedamount');
        $reason = Registry::getRequest()->getRequestParameter('reason');

        $translator = $this->getServiceFromContainer(Translator::class);
        if ($reason === "NONE" && $this->isUnzerOrder() && $this->isCancelReasonRequired()) {
            $this->_aViewData['errCancel'] = $chargeid . ": " . $translator->translate('OSCUNZER_CANCEL_MISSINGREASON') . " " . $amount;
            return;
        }

        if ($amount > $fCharged || $amount == 0) {
            $this->_aViewData['errCancel'] = $chargeid . ": " . $translator->translate('OSCUNZER_CANCEL_ERR_AMOUNT') . " " . $amount;
            return;
        }
        $transactionService = $this->getServiceFromContainer(TransactionService::class);
        try {
            $unzerPayment = $this->getServiceFromContainer(UnzerSDKLoader::class)
                ->getUnzerSDK()
                ->fetchChargeById($unzerid, $chargeid);

            $cancellation = $unzerPayment->cancel($amount, $reason);
            $transactionService->writeCancellationToDB($this->getEditObjectId(), $this->getEditObject()->oxorder__oxuserid->value, $cancellation);
        }
        catch (\Exception $e) {
            $this->_aViewData['errCancel'] = $chargeid . ": " . $e->getMessage();
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

        $order = $this->getEditObject();
        if ($order && strpos($order->getFieldData('oxpaymenttype'), "oscunzer") !== false) {
            $this->oPaymnet = oxNew(Payment::class);
            if ($this->oPaymnet->load($order->getFieldData('oxpaymenttype'))) {
                $isUnzer = true;
            }
        }

        return $isUnzer;
    }

    public function isCancelReasonRequired() {
        if (!$this->oPaymnet) {
            return false;
        } else {
            return $this->oPaymnet->isUnzerSecuredPayment();
        }
    }
    /**
     * Returns editable order object
     *
     * @return object
     */
    public function getEditObject(): ?object
    {
        $soxId = $this->getEditObjectId();
        if ($this->editObject === null && isset($soxId) && $soxId != '-1') {
            $this->editObject = oxNew(Order::class);
            $this->editObject->load($soxId);
        }

        return $this->editObject;
    }
}
