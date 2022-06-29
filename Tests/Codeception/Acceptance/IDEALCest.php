<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Tests\Codeception\Acceptance;

use Codeception\Util\Fixtures;
use OxidSolutionCatalysts\Unzer\Tests\Codeception\AcceptanceTester;

class IDEALCest extends BaseCest
{
    private $idealPaymentLabel = "//label[@for='payment_oscunzer_ideal']";
    private $paymentMethodForm = "//form[@id='payment-form']";
    private $BICInput = "//input[@name='bic']";
    private $nextButton = "//button[@class='btn btn-primary']";
    private $usernameInput = "//input[@name='userLogin']";
    private $usePINInput = "//input[@name='userPIN']";
    private $tanInput = "//input[@name='tan']";

    /**
     * @param AcceptanceTester $I
     * @group iDEALPaymentTest
     */
    public function checkPaymentWorks(AcceptanceTester $I)
    {
        $I->wantToTest('Test iDEAL payment works');
        $I->updateInDatabase('oxpayments', ['OXACTIVE' => 1], ['OXID' => 'oscunzer_ideal']);
        $this->_setAcceptance($I);
        $this->_initializeTest();
        $orderPage = $this->_choosePayment($this->idealPaymentLabel);

        $idealPaymentData = Fixtures::get('ideal_payment');
        $price = str_replace(',', '.', $this->_getPrice());

        $I->waitForElement($this->paymentMethodForm);
        $I->click($this->paymentMethodForm);
        $I->click("//div[@data-value='" . $idealPaymentData["option"] . "']");
        $orderPage->submitOrder();

        // first page : put in bank name
        $I->waitForText($price);
        $I->waitForElement($this->BICInput);
        $I->fillField($this->BICInput, $idealPaymentData['account_bankname']);
        $I->click($this->nextButton);

        // second page : login
        $I->waitForElement($this->usernameInput);
        $I->fillField($this->usernameInput, $idealPaymentData['account_number']);
        $I->fillField($this->usePINInput, $idealPaymentData['USER_PIN']);
        $I->click($this->nextButton);

        // third page : put in TAN
        $I->waitForElement($this->tanInput);
        $I->fillField($this->tanInput, $idealPaymentData['USER_TAN']);
        $I->click($this->nextButton);

        // forth page : successful
        $I->waitForText($price);
        $I->waitForElement($this->nextButton);
        $I->click($this->nextButton);

        $I->waitForText($this->_getTranslator()->translate('THANK_YOU'), 20);
    }
}
