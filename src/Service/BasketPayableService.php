<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Service;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\Unzer\Core\UnzerDefinitions as CoreUnzerDefinitions;
use OxidEsales\Eshop\Application\Model\Payment;

class BasketPayableService
{
    public function basketIsPayable(Payment $payment): bool
    {
        $oBasket = Registry::getSession()->getBasket();
        /** @var \OxidEsales\Eshop\Application\Model\Payment $payment */
        $brutto = $oBasket->getBruttoSum();
        $minimalPayment = $payment->getFieldData('oxpayments__oxfromamount');
        if ($brutto < $minimalPayment) {
            return false;
        }

        if ($brutto < CoreUnzerDefinitions::MINIMAL_PAYABLE_AMOUNT) {
            return false;
        }

        return true;
    }
}
