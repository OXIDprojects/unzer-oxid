<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\PaymentExtensions;

use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

class InstallmentPaylater extends UnzerPayment
{
    protected string $paymentMethod = 'paylater-installment';
    protected bool $needPending = true;

    /**
     * @return BasePaymentType
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     */
    public function getUnzerPaymentTypeObject(): BasePaymentType
    {
        return $this->unzerSDK->fetchPaymentType(
            $this->unzerService->getUnzerPaymentIdFromRequest()
        );
    }
}
