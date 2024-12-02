<?php

namespace OxidSolutionCatalysts\Unzer\Tests\Unit\Service;

use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\Unzer\Core\UnzerDefinitions as CoreUnzerDefinitions;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidSolutionCatalysts\Unzer\Service\BasketPayableService;
use PHPUnit\Framework\TestCase;

class BasketPayableServiceTest extends TestCase
{
    private $basketPayableService;
    private $sessionMock;
    private $basketMock;
    private $paymentMock;

    protected function setUp(): void
    {
        $this->basketPayableService = new BasketPayableService();

        // Mock the session and basket
        $this->sessionMock = $this->createMock(\OxidEsales\Eshop\Core\Session::class);
        $this->basketMock = $this->createMock(Basket::class);
        $this->paymentMock = $this->createMock(Payment::class);

        Registry::set(\OxidEsales\Eshop\Core\Session::class, $this->sessionMock);
    }

    /**
     * @covers \OxidSolutionCatalysts\Unzer\Service\BasketPayableService::basketIsPayable
     */
    public function testBasketIsPayableWhenBruttoIsGreaterThanMinimalPaymentAndMinimalPayableAmount()
    {
        $bruttoSum = 100;
        $minimalPayment = 50;

        $this->basketMock->method('getBruttoSum')->willReturn($bruttoSum);
        $this->paymentMock->method('getFieldData')
            ->with('oxpayments__oxfromamount')
            ->willReturn($minimalPayment);

        $this->sessionMock->method('getBasket')->willReturn($this->basketMock);

        $result = $this->basketPayableService->basketIsPayable($this->paymentMock);

        $this->assertTrue($result);
    }

    /**
     * @covers \OxidSolutionCatalysts\Unzer\Service\BasketPayableService::basketIsPayable
     */
    public function testBasketIsNotPayableWhenBruttoIsLessThanMinimalPayment()
    {
        $bruttoSum = 40;
        $minimalPayment = 50;

        $this->basketMock->method('getBruttoSum')->willReturn($bruttoSum);
        $this->paymentMock->method('getFieldData')
            ->with('oxpayments__oxfromamount')
            ->willReturn($minimalPayment);

        $this->sessionMock->method('getBasket')->willReturn($this->basketMock);

        $result = $this->basketPayableService->basketIsPayable($this->paymentMock);

        $this->assertFalse($result);
    }

    /**
     * @covers \OxidSolutionCatalysts\Unzer\Service\BasketPayableService::basketIsPayable
     */
    public function testBasketIsNotPayableWhenBruttoIsLessThanMinimalPayableAmount()
    {
        $bruttoSum = CoreUnzerDefinitions::MINIMAL_PAYABLE_AMOUNT - 1;
        $minimalPayment = 1;

        $this->basketMock->method('getBruttoSum')->willReturn($bruttoSum);
        $this->paymentMock->method('getFieldData')
            ->with('oxpayments__oxfromamount')
            ->willReturn($minimalPayment);

        $this->sessionMock->method('getBasket')->willReturn($this->basketMock);

        $result = $this->basketPayableService->basketIsPayable($this->paymentMock);

        $this->assertFalse($result);
    }

    /**
     * @covers \OxidSolutionCatalysts\Unzer\Service\BasketPayableService::basketIsPayable
     */
    public function testBasketIsPayableWhenBruttoIsEqualToMinimalPaymentAndMinimalPayableAmount()
    {
        $bruttoSum = CoreUnzerDefinitions::MINIMAL_PAYABLE_AMOUNT;
        $minimalPayment = CoreUnzerDefinitions::MINIMAL_PAYABLE_AMOUNT;

        $this->basketMock->method('getBruttoSum')->willReturn($bruttoSum);
        $this->paymentMock->method('getFieldData')
            ->with('oxpayments__oxfromamount')
            ->willReturn($minimalPayment);

        $this->sessionMock->method('getBasket')->willReturn($this->basketMock);

        $result = $this->basketPayableService->basketIsPayable($this->paymentMock);

        $this->assertTrue($result);
    }
}
