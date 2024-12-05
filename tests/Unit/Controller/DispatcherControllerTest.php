<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Tests\Unit\Controller;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
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

        $order = oxNew(Order::class);
        $testUserId = $this->createTestUser();
        $order->assign(
            [
                'oxid' => 'testOrderId',
                'oxuserid' => $testUserId,
                'oxordernr' => '42',
                'oxpaymenttype' => 'dummy_oscunzer'
            ]
        );

        $tmpData = [
            'oxid' => 'testTmpOrderId',
            'tmporder' => serialize($order)
        ];

        /** @var DispatcherController $controller */
        $controller = $this->getDispatcherControllerPartialMock();
        $result = $controller->finalizeTmpOrder($paymentMock, $tmpOrderMock, $tmpData, false);

        $this->assertEquals('success', $result);
    }

    public function testFinalizeTmpOrderExecutesAndGetDataInside()
    {
        // Create a mock for TmpOrder
        $tmpOrderMock = $this->createMock(TmpOrder::class);
        $tmpOrderMock->method('load')->willReturn(true);

        // Create a mock for UnzerResourcePayment
        $paymentMock = $this->createMock(UnzerResourcePayment::class);

        // Create a partial mock for Order, overriding initWriteTransactionToDB
        $orderMock = $this->getMockBuilder(Order::class)
            ->onlyMethods(['getFieldData'])
            ->getMock();

        $orderMock->method('getFieldData')
            ->willReturn('dummy_oscunzer');

        // Assign necessary fields to the order
        $orderMock->assign([
            'oxid' => 'testOrderId',
            'oxuserid' => 'testUserId',
            'oxordernr' => '42',
            'oxpaymenttype' => 'dummy_oscunzer'
        ]);

        // Serialize the order
        $tmpData = [
            'oxid' => 'testTmpOrderId',
            'tmporder' => serialize($orderMock)
        ];

        // Create a partial mock for DispatcherController
        $controllerMock = $this->getMockBuilder(DispatcherController::class)
            ->onlyMethods(['getFlexibleSerializer', 'returnSuccess', 'returnError'])
            ->getMock();

        $controllerMock->method('getFlexibleSerializer')
            ->willReturn(new FlexibleSerializer());

        $controllerMock->method('returnSuccess')
            ->willReturn('success');

        $controllerMock->method('returnError')
            ->willReturn('error');

        // Execute the method under test
        $result = $controllerMock->finalizeTmpOrder($paymentMock, $tmpOrderMock, $tmpData, false);

        // Assert the result
        $this->assertEquals('success', $result);
    }

    public function testFinalizeTmpOrderReturnsErrorOnInvalidData()
    {
        $tmpOrderMock = $this->createMock(TmpOrder::class);
        $tmpOrderMock->method('load')->willReturn(false);

        $paymentMock = $this->createMock(UnzerResourcePayment::class);

        $tmpData = [
            'oxid' => 'testTmpOrderId',
            'tmporder' => serialize(['invalid_data' => 'value'])
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

    private function createTestUser(): string
    {
        $user = \oxNew(User::class);
        $user->assign(
            [
                'oxusername' => 'testUser',
                'oxpassword' => 'testPassword',
                'oxactive' => 1,
                'oxcountryid' => '8f241f11096877ac0.98748826',
            ]
        );
        $user->save();

        return $user->getId();
    }
}
