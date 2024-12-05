<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\PaymentExtensions;

use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

class ApplePay extends UnzerPayment
{
    protected string $paymentMethod = 'applepay';

    protected bool $needPending = true;

    protected bool $ajaxResponse = false;

    /**
     * @return BasePaymentType
     * @throws \Exception
     */
    public function getUnzerPaymentTypeObject(): BasePaymentType
    {
        return $this->unzerSDK->fetchPaymentType(
            $this->unzerService->getUnzerPaymentIdFromRequest()
        );
    }
}
