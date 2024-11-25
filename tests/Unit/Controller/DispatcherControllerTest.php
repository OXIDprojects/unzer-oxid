<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Tests\Unit\Controller;

use OxidSolutionCatalysts\Unzer\Model\Order;
use OxidSolutionCatalysts\Unzer\Service\FlexibleSerializer;
use OxidSolutionCatalysts\Unzer\Service\UnzerWebhooks;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use OxidSolutionCatalysts\Unzer\Controller\DispatcherController;
use OxidSolutionCatalysts\Unzer\Model\TmpOrder;
use OxidSolutionCatalysts\Unzer\Service\UnzerSDKLoader;
use UnzerSDK\Resources\Payment as UnzerResourcePayment;

class DispatcherControllerTest extends TestCase
{
    public function testFinalizeTmpOrderExecutesCorrectly()
    {
        $tmpOrderMock = $this->createMock(TmpOrder::class);
        $tmpOrderMock->method('load')->willReturn(true);

        $paymentMock = $this->createMock(UnzerResourcePayment::class);

        $order = \oxNew(Order::class);
        $orderId = 'testOrderId';
        $order->assign(['OXID' => $orderId]);

        $tmpData = [
            'oxid' => 'testTmpOrderId',
            'tmporder' => base64_encode(serialize(['order' => $order]))
        ];

        $controller = $this->getDispatcherControllerPartialMock();

        $result = $controller->finalizeTmpOrder($paymentMock, $tmpOrderMock, $tmpData, false);

        $this->assertEquals('success', $result);
    }

    public function testFinalizeTmpOrderReturnsErrorOnInvalidData()
    {
        $tmpOrderMock = $this->createMock(TmpOrder::class);
        $tmpOrderMock->method('load')->willReturn(false);

        $paymentMock = $this->createMock(UnzerResourcePayment::class);

        $tmpData = [
            'oxid' => 'testTmpOrderId',
            'tmporder' => base64_encode(serialize(['invalid_data' => 'value']))
        ];

        $controller = $this->getDispatcherControllerPartialMock();

        $result = $controller->finalizeTmpOrder($paymentMock, $tmpOrderMock, $tmpData, false);

        $this->assertEquals('error', $result);
    }

    private function getDispatcherControllerPartialMock(): MockObject
    {
        $controller = $this->getMockBuilder(DispatcherController::class)
            ->onlyMethods([
                'getUnzerWebhooks',
                'getUnzerSdkLoader',
                'getFlexibleSerializer',
                'returnError',
                'returnSuccess'
            ])
            ->getMock();

        $unzerWebhooksMock = $this->createMock(UnzerWebhooks::class);
        $controller->method('getUnzerWebhooks')
            ->willReturn($unzerWebhooksMock);

        $controller->method('getUnzerSdkLoader')
            ->willReturn($this->createMock(UnzerSDKLoader::class));

        $controller->method('returnError')
            ->willReturn('error');

        $controller->method('returnSuccess')
            ->willReturn('success');

        $flexibleSerializer = new FlexibleSerializer();
        $controller->method('getFlexibleSerializer')
            ->willReturn($flexibleSerializer);

        return $controller;
    }
}
