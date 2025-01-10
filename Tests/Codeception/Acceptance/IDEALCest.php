<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Tests\Codeception\Acceptance;

use Codeception\Util\Fixtures;
use OxidSolutionCatalysts\Unzer\Tests\Codeception\AcceptanceTester;

/**
 * @group unzer_module
 * @group FirstGroup
 * @group IDEALCest
 */
final class IDEALCest extends BaseCest
{
    private string $idealPaymentLabel = "//label[@for='payment_oscunzer_ideal']";
    private string $paymentMethodForm = "//form[@id='payment-form']";
    private string $BICInput = "//input[@name='bic']";
    private string $nextButton = "//button[@class='btn btn-primary']";
    private string $usernameInput = "//input[@name='userLogin']";
    private string $usePINInput = "//input[@name='userPIN']";
    private string $tanInput = "//input[@name='tan']";

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);
        // IDEAL is now only available in NL, BaseCest should make all the necessary setup
        // User is assigned to NL
        $user = Fixtures::get('client');
        $I->updateInDatabase(
            'oxuser',
            ['oxcountryid' => 'a7c40f632cdd63c52.64272623'], // NL
            ['oxusername' => $user['username']]
        );
    }

    public function _after(AcceptanceTester $I): void
    {
        $user = Fixtures::get('client');
        $I->updateInDatabase(
            'oxuser',
            ['oxcountryid' => 'a7c40f631fc920687.20179984'], // DE
            ['oxusername' => $user['username']]
        );
    }

    protected function getOXID(): array
    {
        return ['oscunzer_ideal'];
    }

    /**
     * @param AcceptanceTester $I
     * @group iDEALPaymentTest
     */
    public function checkPaymentWorks(AcceptanceTester $I)
    {
        $I->markTestSkipped('Skipping this test - payment requires mobile app');

        $I->wantToTest('Test iDEAL payment works');
        $this->initializeTest();
        $orderPage = $this->choosePayment($this->idealPaymentLabel);

        $idealPaymentData = Fixtures::get('ideal_payment');
        $price = str_replace(',', '.', $this->getPrice());

        $I->waitForElement($this->paymentMethodForm);
        $I->click($this->paymentMethodForm);
        $I->click("//div[@data-value='" . $idealPaymentData["option"] . "']");
        $orderPage->submitOrder();

        // first page : put in bank name
        $I->waitForText($price, 60);
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
        $I->waitForPageLoad();
        $I->waitForText($price);
        $I->waitForElement($this->nextButton);
        $I->click($this->nextButton);

        $this->checkSuccessfulPayment();
    }
}
