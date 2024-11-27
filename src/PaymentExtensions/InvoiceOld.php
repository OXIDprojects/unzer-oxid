<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\PaymentExtensions;

use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Invoice;

class InvoiceOld extends UnzerPayment
{
    protected string $paymentMethod = 'invoice';

    protected array $allowedCurrencies = ['EUR', 'CHF'];

    /**
     * @return BasePaymentType
     * @throws UnzerApiException
     */
    public function getUnzerPaymentTypeObject(): BasePaymentType
    {
        return $this->unzerSDK->createPaymentType(
            new Invoice()
        );
    }
}
