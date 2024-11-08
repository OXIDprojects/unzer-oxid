<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Controller;

use OxidSolutionCatalysts\Unzer\Service\PrePaymentBankAccountService;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;

class ThankYouController extends ThankYouController_parent
{
    use ServiceContainer;

    /** Unzer Order Number */
    protected ?string $unzerOrderNumber = null;

    protected function getUnzerOrderNumberForPrePayments(): string
    {
        if (is_null($this->unzerOrderNumber)) {
            $this->unzerOrderNumber = '';
            $order = $this->getOrder();
            if ($order) {
                $orderNumber = $order->getFieldData('oxunzerordernr');
                $this->unzerOrderNumber = is_string($orderNumber) && !empty($orderNumber) ? $orderNumber : '';
            }
        }
        return $this->unzerOrderNumber;
    }

    /** Template variable getter. Returns Unzer PrePayment Iban */
    public function getUnzerPrePaymentIban(): ?string
    {
        $result = null;
        $unzerOrderNumber = $this->getUnzerOrderNumberForPrePayments();
        if ($unzerOrderNumber) {
            $result = $this->getPrePaymentBankAccountService()->getIban(
                $unzerOrderNumber
            );
        }
        return $result;
    }

    /** Template variable getter. Returns Unzer PrePayment Bic */
    public function getUnzerPrePaymentBic(): ?string
    {
        $result = null;
        $unzerOrderNumber = $this->getUnzerOrderNumberForPrePayments();
        if ($unzerOrderNumber) {
            $result = $this->getPrePaymentBankAccountService()->getBic(
                $unzerOrderNumber
            );
        }
        return $result;
    }

    /** Template variable getter. Returns Unzer PrePayment Holder */
    public function getUnzerPrePaymentHolder(): ?string
    {
        $result = null;
        $unzerOrderNumber = $this->getUnzerOrderNumberForPrePayments();
        if ($unzerOrderNumber) {
            $result = $this->getPrePaymentBankAccountService()->getHolder(
                $unzerOrderNumber
            );
        }
        return $result;
    }

    /** Template variable getter. Returns Unzer PrePayment Holder */
    public function getUnzerPrePaymentDescriptor(): ?string
    {
        $result = null;
        $unzerOrderNumber = $this->getUnzerOrderNumberForPrePayments();
        if ($unzerOrderNumber) {
            $result = $this->getPrePaymentBankAccountService()->getDescriptor(
                $unzerOrderNumber
            );
        }
        return $result;
    }

    private function getPrePaymentBankAccountService(): PrePaymentBankAccountService
    {
        return $this->getServiceFromContainer(PrePaymentBankAccountService::class);
    }
}
