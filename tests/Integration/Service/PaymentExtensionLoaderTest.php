<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Tests\Integration\Service;

use OxidEsales\Eshop\Application\Model\Payment as PaymentModel;
use OxidSolutionCatalysts\Unzer\Service\DebugHandler;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidSolutionCatalysts\Unzer\Service\PaymentExtensionLoader;
use OxidSolutionCatalysts\Unzer\Service\Unzer;
use OxidSolutionCatalysts\Unzer\Service\UnzerSDKLoader;

class PaymentExtensionLoaderTest extends IntegrationTestCase
{
    private DebugHandler $logger;
    public function setUp(): void
    {
        $this->logger = new DebugHandler($this->createMock(\Monolog\Logger::class));
    }
    /**
     * @dataProvider checkPaymentTypeCanBeLoadedDataProvider
     */
    public function testPaymentTypeCanBeLoaded($paymentId, $paymentClass): void
    {
        $paymentStub = $this->createPartialMock(PaymentModel::class, ['getId']);
        $paymentStub->method('getId')->willReturn($paymentId);

        $sdkLoaderMock = $this->createPartialMock(UnzerSDKLoader::class, ['getUnzerSDK']);
        $sdkLoaderMock->method('getUnzerSDK')->willReturn(
            $this->createPartialMock(\UnzerSDK\Unzer::class, [])
        );

        $sut = new PaymentExtensionLoader(
            $sdkLoaderMock,
            $this->getMockBuilder(Unzer::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this->logger
        );

        $loadedPaymentType = $sut->getPaymentExtension($paymentStub);
        $this->assertInstanceOf($paymentClass, $loadedPaymentType);
    }

    public function checkPaymentTypeCanBeLoadedDataProvider(): array
    {
        $testCases = [];

        foreach (PaymentExtensionLoader::UNZERCLASSNAMEMAPPING as $key => $className) {
            $testCases[] = [
                'paymentId' => $key,
                'paymentClass' => $className
            ];
        }

        return $testCases;
    }
}
