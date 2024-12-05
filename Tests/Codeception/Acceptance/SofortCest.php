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
 * @Irgnore("Skipped becouse of dynamic nature of user verification of the SEPA site")
 */
final class SofortCest extends BaseCest
{
    private string $sofortPaymentLabel = "//label[@for='payment_oscunzer_sofort']";
    private string $landSelect = "//select[@id='MultipaysSessionSenderCountryId']";
    private string $cookiesAcceptButton = "//button[@class='cookie-modal-accept-all button-primary']";
    private string $bankSearchInput = "//input[@id='BankCodeSearch']";
    private string $bankLabel = "//label[@for='account-88888888']";
    private string $accountNumberLabel = "//input[@id='BackendFormLOGINNAMEUSERID']";
    private string $PINNumberLabel = "//input[@id='BackendFormUSERPIN']";
    private string $continueButton = "//button[@class='button-right primary has-indicator']";
    private string $kontoOptionInput = "//input[@id='account-1']";
    private string $TANInput = "//input[@id='BackendFormTan']";

    protected function getOXID(): array
    {
        return ['oscunzer_sofort'];
    }

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);
    }

    /**
     * @throws \Exception
     * @group SofortPaymentTest
     */
    public function checkPaymentWorks(AcceptanceTester $I)
    {
        $I->markTestSkipped("Skipping due to dynamic user validation");
        $I->wantToTest('Test Sofort payment works');
        $this->initializeTest();
        $orderPage = $this->choosePayment($this->sofortPaymentLabel);
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

        $I->waitForText($this->getPrice() . ' ' . $this->getCurrency());
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

        $this->checkSuccessfulPayment();
    }
}
