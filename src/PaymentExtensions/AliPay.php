<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\PaymentExtensions;

use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Alipay as UnzerAlipay;

class AliPay extends UnzerPayment
{
    protected string $paymentMethod = 'alipay';

    protected bool $needPending = true;

    /**
     * @return BasePaymentType
     * @throws UnzerApiException
     */
    public function getUnzerPaymentTypeObject(): BasePaymentType
    {
        return $this->unzerSDK->createPaymentType(
            new UnzerAlipay()
        );
    }
}
