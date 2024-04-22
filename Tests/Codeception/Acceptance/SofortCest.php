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
 * @group ThirdGroup
 */
final class SofortCest extends BaseCest
{
    private $sofortPaymentLabel = "//label[@for='payment_oscunzer_sofort']";
    private $landSelect = "//select[@id='MultipaysSessionSenderCountryId']";
    private $cookiesAcceptButton = "//button[@class='cookie-modal-accept-all button-primary']";
    private $bankSearchInput = "//input[@id='BankCodeSearch']";
    private $banksearchresultDiv = "//div[@id='BankSearcherResults']";
    private $bankLabel = "//label[@for='account-88888888']";
    private $accountNumberLabel = "//input[@id='BackendFormLOGINNAMEUSERID']";
    private $PINNumberLabel = "//input[@id='BackendFormUSERPIN']";
    private $continueButton = "//button[@class='button-right primary has-indicator']";
    private $kontoOptionInput = "//input[@id='account-1']";
    private $TANInput = "//input[@id='BackendFormTan']";

    protected function _getOXID(): array
    {
        return ['oscunzer_sofort'];
    }

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);
        $I->resetCookie([]);
    }

    /**
     * @throws \Exception
     * @group SofortPaymentTest
     */
    public function checkPaymentWorks(AcceptanceTester $I)
    {
        $I->wantToTest('Test Sofort payment works');
        $this->_initializeTest();
        $orderPage = $this->_choosePayment($this->sofortPaymentLabel);
        $orderPage->submitOrder();

        $sofortPaymentData = Fixtures::get('sofort_payment');

        try {
            // accept cookies
            $I->waitForElement($this->cookiesAcceptButton, 20);
            $I->click($this->cookiesAcceptButton);
        } catch (\Facebook\WebDriver\Exception\TimeoutException $exception) {
            $I->makeScreenshot('noAcceptCookie');
        }

        // first page : choose bank
        try {
            $I->waitForPageLoad();
        } catch (\Exception $exception) {
            $I->makeScreenshot('waitForPageLoad');
        }

        $I->waitForText($this->_getPrice() . ' ' . $this->_getCurrency());
        $I->selectOption($this->landSelect, 'DE');
        $I->waitForElement($this->bankSearchInput);
        $I->fillField($this->bankSearchInput, "Demo Bank");
        $I->wait(1);
        $I->waitForElementClickable($this->bankLabel);
        $I->click($this->bankLabel);

        // second page : put in account data
        $I->waitForElement($this->accountNumberLabel);
        $I->fillField($this->accountNumberLabel, $sofortPaymentData['account_number']);
        $I->fillField($this->PINNumberLabel, $sofortPaymentData['USER_PIN']);
        $I->click($this->continueButton);

        // third page : choose konto
        $I->waitForElement($this->kontoOptionInput);
        $I->click($this->kontoOptionInput);
        $I->waitForElement($this->continueButton);
        $I->click($this->continueButton);

        // forth page : confirm payment
        $I->waitForElement($this->TANInput);
        $I->fillField($this->TANInput, $sofortPaymentData['USER_TAN']);
        $I->click($this->continueButton);

        $this->_checkSuccessfulPayment();
    }
}
