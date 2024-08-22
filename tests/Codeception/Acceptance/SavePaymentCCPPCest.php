<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Tests\Codeception\Acceptance;

use Codeception\Util\Fixtures;
use OxidEsales\Codeception\Module\Context;
use OxidEsales\Codeception\Module\Translation\Translator;
use OxidEsales\Codeception\Page\Page;
use OxidSolutionCatalysts\Unzer\Tests\Codeception\Acceptance\BaseCest;
use OxidSolutionCatalysts\Unzer\Tests\Codeception\AcceptanceTester;
use OxidEsales\Codeception\Step\Basket as BasketSteps;

/**
 * @group unzer_module
 * @group SecondGroup
 */
final class SavePaymentCCPPCest extends BaseCest
{
    private string $cardPaymentLabel = "//label[@for='payment_oscunzer_card']";
    private string $cardNumberIframe = "//iframe[contains(@id, 'unzer-number-iframe')]";
    private string $expireDateIframe = "//iframe[contains(@id, 'unzer-expiry-iframe')]";
    private string $CVCIframe = "//iframe[contains(@id, 'unzer-cvc-iframe')]";
    private string $cardNumberInput = "//input[@id='card-number']";
    private string $expireDateInput = "//input[@id='card-expiry-date']";
    private string $CVCInput = "//input[@id='card-ccv']";
    private string $toCompleteAuthentication = "Click here to complete authentication.";
    private string $newCard = "#addNewCardCheckboxLabel";
    private string $saveCard = "#oscunzersavepayment";
    private string $useSavedCardForPayment = '//*[@id="payment-saved-cards"]/table/tbody/tr/td[3]/input';
    private string $acceptAllCookiesButton = "//button[@id='acceptAllButton']";
    private string $paypalPaymentLabel = "//label[@for='payment_oscunzer_paypal']";
    private string $loginInput = "#email";
    private string $passwordInput = "#password";
    private string $loginButton = "#btnLogin";
    private string $submitButton = "#payment-submit-btn";
    private string $globalSpinnerDiv = "//div[@data-testid='global-spinner']";
    private string $savePaypalPayment = "#oscunzersavepayment";
    private string $firstSavedPaypalPayment = "//*[@id='payment-saved-cards']/table/tbody/tr/td[2]/input";
    private string $savedPaymentsLocator = "//*[@id='account_menu']/ul/li[1]/a";

    public function setUp(): void
    {
        $this->markTestSkipped('Skipping this entire class temporararlly');
    }

    /**
     * @group unzer_module
     * @group SecondGroup
     * @throws \Exception
     * @group SavePaymentCCPPCest
     */
    public function testPaymentWorksWithSavingPayment(AcceptanceTester $I)
    {
        $I->wantToTest('if PayPal payment works and save payment flag is clickable');
        $this->initializeTest();
        $this->choosePayment($this->paypalPaymentLabel);
        $I->scrollTo($this->savePaypalPayment);
        $I->waitForElementClickable($this->savePaypalPayment, 15);
        $I->wait(10);
        $I->click($this->savePaypalPayment);
        $this->submitOrder();

        $paypalPaymentData = Fixtures::get('paypal_payment');

        // accept cookies
        $I->waitForDocumentReadyState();
        $I->wait(5);
        if ($this->checkElementExists($this->acceptAllCookiesButton, $I)) {
            $I->click($this->acceptAllCookiesButton);
        }

        // login page
        $I->waitForDocumentReadyState();
        $I->waitForElement($this->loginInput);
        $I->fillField($this->loginInput, $paypalPaymentData['username']);
        $I->fillField($this->passwordInput, $paypalPaymentData['password']);
        $I->waitForDocumentReadyState();
        $I->click($this->loginButton);
        $I->waitForElementNotVisible($this->globalSpinnerDiv, 60);

        // card choose page
        $I->waitForDocumentReadyState();
        $I->waitForText($this->getPrice());
        $I->waitForElement($this->submitButton);
        $I->executeJS("document.getElementById('payment-submit-btn').click();");
        $I->waitForDocumentReadyState();
        $I->waitForElementNotVisible($this->globalSpinnerDiv, 60);
        $I->wait(10);

        $this->checkSuccessfulPayment();
    }

    /**
     * @group unzer_module
     * @group SecondGroup
     * @group SavePaymentCCPPCest
     * @depends testPaymentWorksWithSavingPayment
     */
    public function testSavedPaypalPaymentIsVisibleInAccount(AcceptanceTester $I): void
    {
        $I->wantToTest("if saved paypal payment is visible in the user's account");

        $homePage = $this->I->openShop();
        $clientData = Fixtures::get('client');

        $homePage->openAccountMenu();
        $I->waitForText(Translator::translate('FORGOT_PASSWORD'));
        $I->waitForElementVisible($homePage->userLoginName);
        $I->fillField($homePage->userLoginName, $clientData['username']);
        $I->fillField($homePage->userLoginPassword, $clientData['password']);
        $I->click($homePage->userLoginButton);
        $I->waitForPageLoad();
        Context::setActiveUser($clientData['username']);

        $I->openShop()->openAccountPage();
        $I->click("//*[@id='wrapper']/div/div/div[2]/div[1]/div/div/a");
        $I->see("paypal-buyer@unzer.com");

        $basketItem = Fixtures::get('product');
        $basketSteps = new BasketSteps($this->I);
        $basketSteps->addProductToBasket($basketItem['id'], $this->amount);
        $I->openShop()->openMiniBasket();
        $I->waitForText(Translator::translate('CHECKOUT'));
        $I->click(Translator::translate('CHECKOUT'));
        $I->waitForPageLoad();
        $this->choosePayment($this->paypalPaymentLabel);
        $I->waitForElementClickable($this->firstSavedPaypalPayment);
        $I->wantTo('use saved payment to pay');
        $I->scrollTo('//*[@id="payment-saved-cards"]/table/tbody/tr/td[2]/input');
        $I->wait(5);
        $I->click('#payment-saved-cards > table > tbody > tr > td:nth-child(3) > input');
        $this->submitOrder();

        $paypalPaymentData = Fixtures::get('paypal_payment');

        // accept cookies
        $I->waitForDocumentReadyState();
        $I->wait(5);
        if ($this->checkElementExists($this->acceptAllCookiesButton, $I)) {
            $I->click($this->acceptAllCookiesButton);
        }

        // login page
        $I->wait(20);
        $I->waitForElement($this->loginInput);
        $I->fillField($this->loginInput, $paypalPaymentData['username']);
        $I->fillField($this->passwordInput, $paypalPaymentData['password']);
        $I->waitForDocumentReadyState();
        $I->click($this->loginButton);
        $I->waitForElementNotVisible($this->globalSpinnerDiv, 60);

        $I->wait(30);
        $I->waitForText($this->getPrice());
        $I->waitForElement($this->submitButton);
        $I->executeJS("document.getElementById('payment-submit-btn').click();");
        $I->wait(30);
        $I->waitForElementNotVisible($this->globalSpinnerDiv, 60);
        $I->wait(10);

        $this->checkSuccessfulPayment();
    }

    /**
     * @group unzer_module
     * @group SecondGroup
     * @group SavePaymentCCPPCest
     * @depends testSavedPaypalPaymentIsVisibleInAccount
     */
    public function testCannotAddSamePPSecondTime(AcceptanceTester $I): void
    {
        $I->wantToTest("if user cannot save Paypal Account twice");

        $homePage = $this->I->openShop();
        $clientData = Fixtures::get('client');

        $homePage->openAccountMenu();
        $I->waitForText(Translator::translate('FORGOT_PASSWORD'));
        $I->waitForElementVisible($homePage->userLoginName);
        $I->fillField($homePage->userLoginName, $clientData['username']);
        $I->fillField($homePage->userLoginPassword, $clientData['password']);
        $I->click($homePage->userLoginButton);
        $I->waitForPageLoad();
        Context::setActiveUser($clientData['username']);

        $I->amOnPage('/index.php?cl=account');
        $I->click("//*[@id='account_menu']/ul/li[1]/a");
        $I->see("paypal-buyer@unzer.com");
        $I->openShop();

        $basketItem = Fixtures::get('product');
        $basketSteps = new BasketSteps($this->I);
        $basketSteps->addProductToBasket($basketItem['id'], $this->amount);
        $I->openShop()->openMiniBasket()->openCheckout();
        $orderPage = $this->choosePayment($this->paypalPaymentLabel);
        $I->waitForElementClickable($this->firstSavedPaypalPayment);
        $I->wantTo('use new Paypal account');
        $I->seeAndClick($this->savePaypalPayment);
        $orderPage->submitOrder();

        $paypalPaymentData = Fixtures::get('paypal_payment');

        // accept cookies
        $I->waitForDocumentReadyState();
        $I->wait(5);
        if ($this->checkElementExists($this->acceptAllCookiesButton, $I)) {
            $I->click($this->acceptAllCookiesButton);
        }

        // login page
        $I->wait(30);
        $I->waitForElement($this->loginInput);
        $I->fillField($this->loginInput, $paypalPaymentData['username']);
        $I->fillField($this->passwordInput, $paypalPaymentData['password']);
        $I->wait(30);
        $I->click($this->loginButton);
        $I->waitForElementNotVisible($this->globalSpinnerDiv, 60);

        $I->waitForDocumentReadyState();
        $I->waitForText($this->getPrice());
        $I->waitForElement($this->submitButton);
        $I->executeJS("document.getElementById('payment-submit-btn').click();");
        $I->wait(30);
        $I->waitForElementNotVisible($this->globalSpinnerDiv, 60);
        $I->wait(10);

        $this->checkSuccessfulPayment();

        $I->amOnPage('/index.php?cl=account');
        $I->click("//*[@id='account_menu']/ul/li[1]/a");
        $I->see("paypal-buyer@unzer.com");
        $pageSource = $I->grabPageSource();
        // Count the number of occurrences of the text
        $occurrences = substr_count($pageSource, 'paypal-buyer@unzer.com');
        $I->assertEquals(1, $occurrences, 'Paypal Saving OK');
    }

    /**
     * @group unzer_module
     * @group SecondGroup
     * @throws \Exception
     * @group SavePaymentCCPPCest
     * @depends testCannotAddSamePPSecondTime
     */
    public function testSavedPaymentIsAvailableInAccountAndCanBeDeleted(AcceptanceTester $I)
    {
        $I->wantToTest("if saved paypal payment is visible in the user's account and can be deleted");
        $I->wait(5);
        $homePage = $this->I->openShop();
        $clientData = Fixtures::get('client');
        $homePage->loginUser($clientData['username'], $clientData['password']);

        $I->amOnPage('/index.php?cl=account');
        $I->click("//*[@id='account_menu']/ul/li[1]/a");
        $I->see("paypal-buyer@unzer.com");

        $I->wait(5);
        $I->seeElement("//*[@id='uzr_collect']/button");
        $I->wait(5);
        $I->submitForm("#uzr_collect", [], 'deletePayment');
        $I->waitForPageLoad();
        $I->dontSee("paypal-buyer@unzer.com");
    }

    /**
     * @group unzer_module
     * @group SecondGroup
     * @group   SavePaymentCCPPCest
     * @depends testPaymentCardCanSavePayment
     * @throws \Exception
     */
    public function testPaymentUsingSavedCardWorks(AcceptanceTester $I)
    {
        $I->wantToTest('if user can pay with a saved card');
        $this->updateArticleStockAndFlag();
        $this->initializeTest();

        $this->useSavedCardToPay();
        $this->checkCreditCardPayment();
    }

    /**
     * @group unzer_module
     * @group SecondGroup
     * @group   SavePaymentCCPPCest
     * @depends testSavedPaypalPaymentIsVisibleInAccount
     * @throws \Exception
     */
    public function testPaymentCardCanSavePayment(AcceptanceTester $I)
    {
        $I->wantToTest('if a user can save card as a payment method');
        $this->updateArticleStockAndFlag();
        $this->initializeTest();
        $I->wait(60);
        $this->submitCreditCardPaymentAndSavePayment($I, 'mastercard_payment', false, true);
        $this->checkCreditCardPayment();
    }

    /**
     * @group unzer_module
     * @group SecondGroup
     * @group SavePaymentCCPPCest
     * @depends testPaymentUsingSavedCardWorks
     * @throws \Exception
     */
    public function testCannotSaveCardTwice(AcceptanceTester $I)
    {
        $I->wantToTest('user can not save a card twice');
        $this->updateArticleStockAndFlag();
        $this->initializeTest();

        $this->submitCreditCardPaymentAndSavePayment($I, 'mastercard_payment', true, true);
        $I->openShop()->openAccountPage();
        $I->click("//*[@id='account_menu']/ul/li[1]/a");
        $I->see("545301******9543");
        $pageSource = $I->grabPageSource();
        // Count the number of occurrences of the text
        $occurrences = substr_count($pageSource, '545301******9543');
        $I->assertEquals(1, $occurrences, 'CC Saving OK');
    }

    /**
     * @group unzer_module
     * @group SecondGroup
     * @group SavePaymentCCPPCest
     * @depends testCannotSaveCardTwice
     */
    public function testRemoveSavedCardFromAccount(AcceptanceTester $I): void
    {
        $I->wantToTest("if saved card is visible in the user's account and can be deleted");
        $I->wait(5);
        $homePage = $I->openShop();
        $clientData = Fixtures::get('client');
        $homePage->loginUser($clientData['username'], $clientData['password']);

        $I->openShop()->openAccountPage();
        $I->click($this->savedPaymentsLocator);
        $I->see("545301******9543");

        $I->wait(5);
        $I->seeElement("//*[@id='uzr_collect']/button");
        $I->wait(5);
        $I->submitForm("#uzr_collect", [], 'deletePayment');
        $I->waitForPageLoad();
        $I->dontSee("545301******9543");
    }

    /**
     * @group unzer_module
     * @group SecondGroup
     * @throws \Exception
     * @group SavePaymentCCPPCest
     * @depends testRemoveSavedCardFromAccount
     */
    public function testSavedPaypalPaymentIsNotVisibleInCheckoutAfterDelete(AcceptanceTester $I): void
    {
        $I->wantToTest("if saved paypal payment is removed from the user's account");

        $homePage = $I->openShop();
        $clientData = Fixtures::get('client');
        $homePage->loginUser($clientData['username'], $clientData['password']);

        $basketItem = Fixtures::get('product');
        $basketSteps = new BasketSteps($I);
        $basketSteps->addProductToBasket($basketItem['id'], $this->amount);
        $I->openShop()->openMiniBasket()->openCheckout();
        $this->choosePayment($this->paypalPaymentLabel);
        $I->dontSee("paypal-buyer@unzer.com");
    }

    protected function getOXID(): array
    {
        return ['oscunzer_paypal'];
    }

    /**
     * @throws \Exception
     */
    private function submitCreditCardPaymentAndSavePayment(
        AcceptanceTester $I,
        string $string,
        bool $newCard,
        bool $saveCard
    ): void {
        $orderPage = $this->choosePayment($this->cardPaymentLabel);

        $I->waitForPageLoad();
        $I->wantTo("add and save a new card");

        if ($newCard) {
            $I->scrollTo($this->newCard);
            $I->click($this->newCard);
        }

        if ($saveCard) {
            $I->scrollTo($this->saveCard);
            $I->click($this->saveCard);
        }

        $this->finishCardSubmit($string);

        $orderPage->submitOrder();
    }

    private function useSavedCardToPay()
    {
        $orderPage = $this->choosePayment($this->cardPaymentLabel);

        $this->I->waitForPageLoad();
        $this->I->wait(30);
        $this->I->click($this->useSavedCardForPayment);

        $orderPage->submitOrder();
    }
    /**
     * @throws \Exception
     */
    private function finishCardSubmit(string $name): void
    {
        $fixtures = Fixtures::get($name);
        $this->I->waitForElement($this->cardNumberIframe);
        $this->I->switchToIFrame($this->cardNumberIframe);
        $this->I->wait(5);
        $this->I->fillField($this->cardNumberInput, $fixtures['cardnumber']);
        $this->I->switchToNextTab(1);
        $this->I->switchToIFrame($this->expireDateIframe);
        $this->I->fillField($this->expireDateInput, '12/' . date('y'));
        $this->I->switchToNextTab(1);
        $this->I->switchToIFrame($this->CVCIframe);
        $this->I->fillField($this->CVCInput, $fixtures['CVC']);
        $this->I->switchToFrame(null);
    }


    private function checkCreditCardPayment()
    {
        $this->I->waitForText($this->toCompleteAuthentication, 60);
        $this->I->click($this->toCompleteAuthentication);

        $this->checkSuccessfulPayment();
    }

    private function updateArticleStockAndFlag()
    {
        $article = Fixtures::get('product');
        $this->I->updateInDatabase(
            'oxarticles',
            ['OXSTOCK' => 15, 'OXSTOCKFLAG' => 1],
            ['OXID' => $article['id']]
        );
    }
}