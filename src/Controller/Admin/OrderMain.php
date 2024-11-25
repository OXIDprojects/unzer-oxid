<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Controller\Admin;

use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\Unzer\Model\Payment;
use OxidSolutionCatalysts\Unzer\Service\Payment as PaymentService;
use OxidEsales\Eshop\Application\Model\Order;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use UnzerSDK\Exceptions\UnzerApiException;

class OrderMain extends OrderMain_parent
{
    use ServiceContainer;

    /**
     * Method is used for overriding.
     *
     * @return void
     */
    protected function onOrderSend()
    {
        $sOxid = $this->getEditObjectId();
        $oOrder = oxNew(Order::class);
        if ($oOrder->load($sOxid)) {
            /** @var Payment $oPayment */
            $oPayment = oxNew(Payment::class);
            /** @var string $paymentType */
            $paymentType = $oOrder->getFieldData('oxpaymenttype');
            if (
                $oPayment->load($paymentType) &&
                $oPayment->isUnzerSecuredPayment()
            ) {
                $this->sendShipmentNotification($oOrder);
            }
        }
    }

    /**
     * @param Order $oOrder
     * @return void
     */
    public function sendShipmentNotification(Order $oOrder): void
    {
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $oShipment = $paymentService->sendShipmentNotification($oOrder);
        if ($oShipment instanceof UnzerApiException) {
            $oxException = oxNew(
                StandardException::class,
                $oShipment->getMessage(),
                $oShipment->getCode(),
                $oShipment
            );

            Registry::getUtilsView()->addErrorToDisplay($oxException);
        }
    }
}
