<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Tests\Integration\Service;

use OxidSolutionCatalysts\Unzer\Service\TmpOrderService;
use OxidSolutionCatalysts\Unzer\Model\TmpOrder;
use OxidEsales\Eshop\Application\Model\Order;
use PHPUnit\Framework\TestCase;

class TmpOrderServiceTest extends TestCase
{
    private TmpOrderService $tmpOrderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpOrderService = new TmpOrderService();
    }

    public function testGetOrderBySessionOrderIdReturnsOrderIfLoaded(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('load')->with('sessionOrderId')->willReturn(true);

        $tmpOrderMock = $this->createMock(TmpOrder::class);
        $tmpOrderMock->expects($this->never())->method('getTmpOrderByOxOrderId');

        $this->tmpOrderService = $this->getMockBuilder(TmpOrderService::class)
            ->setMethods(['getTmpOrder'])
            ->getMock();

        $this->tmpOrderService
            ->expects($this->any())
            ->method('getTmpOrder')
            ->willReturn($tmpOrderMock);

        $result = $this->tmpOrderService->getOrderBySessionOrderId('sessionOrderId');

        $this->assertInstanceOf(Order::class, $result);
    }

    public function testGetOrderBySessionOrderIdFallsBackToTmpOrder(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('load')->with('sessionOrderId')->willReturn(false);

        $tmpOrderMock = $this->createMock(TmpOrder::class);
        $tmpOrderMock->expects($this->any())
            ->method('getTmpOrderByOxOrderId')
            ->with('sessionOrderId')
            ->willReturn($orderMock);

        $this->tmpOrderService = $this->getMockBuilder(TmpOrderService::class)
            ->setMethods(['getTmpOrder'])
            ->getMock();

        $this->tmpOrderService
            ->expects($this->any())
            ->method('getTmpOrder')
            ->willReturn($tmpOrderMock);

        $result = $this->tmpOrderService->getOrderBySessionOrderId('sessionOrderId');

        $this->assertInstanceOf(Order::class, $result);
    }

    public function testGetPaymentTypeReturnsOrderPaymentType(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getFieldData')
            ->with('oxpaymenttype')
            ->willReturn('PAYMENT_TYPE');

        $result = $this->tmpOrderService->getPaymentType('sessionOrderId', $orderMock);

        $this->assertSame('PAYMENT_TYPE', $result);
    }

    public function testGetPaymentTypeFallsBackToTmpOrder(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getFieldData')->with('oxpaymenttype')->willReturn('');

        $tmpOrderMock = $this->createMock(TmpOrder::class);
        $tmpOrderMock->expects($this->any())
            ->method('getTmpOrderByOxOrderId')
            ->with('sessionOrderId')
            ->willReturn($orderMock);

        $this->tmpOrderService = $this->getMockBuilder(TmpOrderService::class)
            ->setMethods(['getTmpOrder'])
            ->getMock();

        $this->tmpOrderService
            ->expects($this->any())
            ->method('getTmpOrder')
            ->willReturn($tmpOrderMock);

        $result = $this->tmpOrderService->getPaymentType('sessionOrderId', $orderMock);

        $this->assertSame('', $result);
    }

    public function testGetOrderCurrencyReturnsOrderCurrency(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getFieldData')->with('oxcurrency')->willReturn('EUR');

        $result = $this->tmpOrderService
            ->getOrderCurrency('sessionOrderId', $orderMock, 'paymentType');

        $this->assertSame('EUR', $result);
    }

    public function testGetOrderCurrencyFallsBackToTmpOrder(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getFieldData')->with('oxcurrency')->willReturn(null);

        $tmpOrderMock = $this->createMock(TmpOrder::class);
        $tmpOrderMock->expects($this->any())
            ->method('getTmpOrderByOxOrderId')
            ->with('sessionOrderId')
            ->willReturn($orderMock);

        $this->tmpOrderService = $this->getMockBuilder(TmpOrderService::class)
            ->setMethods(['getTmpOrder'])
            ->getMock();

        $this->tmpOrderService
            ->expects($this->any())
            ->method('getTmpOrder')
            ->willReturn($tmpOrderMock);

        $result = $this->tmpOrderService
            ->getOrderCurrency('sessionOrderId', $orderMock, 'paymentType');

        $this->assertEquals('', $result);
    }
}
