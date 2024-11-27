<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Tests\Integration\Service;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\Unzer\Service\Payment as PaymentService;
use OxidSolutionCatalysts\Unzer\Service\PaymentExtensionLoader;
use OxidSolutionCatalysts\Unzer\Service\TmpOrderService;
use OxidSolutionCatalysts\Unzer\Service\Transaction as TransactionService;
use OxidSolutionCatalysts\Unzer\Service\Translator;
use OxidSolutionCatalysts\Unzer\Service\Unzer as UnzerService;
use OxidSolutionCatalysts\Unzer\Service\UnzerSDKLoader;

class PaymentTest extends UnitTestCase
{
    /**
     * @dataProvider removeTemporaryOrderDataProvider
     */
    public function testRemoveTemporaryOrder($sessionValue, $expectedResult): void
    {
        $order = oxNew(Order::class);
        $order->setId('temporaryOrderId');
        $order->save();

        $sessionMock = $this->getMockBuilder(Session::class)
            ->getMock();
        $sessionMock->method('getVariable')
            ->with('sess_challenge')
            ->willReturn($sessionValue);

        $sut = new PaymentService(
            $sessionMock,
            $this->createPartialMock(PaymentExtensionLoader::class, []),
            $this->createPartialMock(Translator::class, []),
            $this->createPartialMock(UnzerService::class, []),
            $this->createPartialMock(UnzerSDKLoader::class, []),
            $this->createPartialMock(TransactionService::class, []),
            $this->createPartialMock(TmpOrderService::class, [])
        );

        $this->assertSame($expectedResult, $sut->removeTemporaryOrder());
    }

    public function removeTemporaryOrderDataProvider(): array
    {
        return [
            ['temporaryOrderId', true],
            ['badOrderId', false],
            ['', false],
        ];
    }
}
