<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Tests\Codeception\Acceptance;

use OxidSolutionCatalysts\Unzer\Tests\Codeception\AcceptanceTester;

final class PaymentsAvailableCest extends BaseCest
{
    private $paymentMethods = [
        'OSCUNZER_PAYMENT_METHOD_SEPA',
        'OSCUNZER_PAYMENT_METHOD_SEPA-SECURED',
        'OSCUNZER_PAYMENT_METHOD_INVOICE',
        'OSCUNZER_PAYMENT_METHOD_INVOICE-SECURED',
        'OSCUNZER_PAYMENT_METHOD_PREPAYMENT',
        'OSCUNZER_PAYMENT_METHOD_ALIPAY',
        'OSCUNZER_PAYMENT_METHOD_CARD',
        'OSCUNZER_PAYMENT_METHOD_GIROPAY',
        'OSCUNZER_PAYMENT_METHOD_PAYPAL',
    ];

    /**
     * @param AcceptanceTester $I
     * @group PaymentAvailableTest
     */
    public function checkPaymentsAvailable(AcceptanceTester $I)
    {
        $I->wantToTest('Test payment methods are available');
        $this->_setAcceptance($I);
        $this->_initializeTest();

        foreach ($this->paymentMethods as $onePaymentMethod) {
            $I->waitForText($this->_getTranslator()->translate($onePaymentMethod));
        }
    }
}
