<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Tests\Unit\Service;

use Monolog\Logger;
use OxidEsales\Eshop\Core\Session;
use OxidSolutionCatalysts\Unzer\Service\DebugHandler;
use OxidSolutionCatalysts\Unzer\Service\ModuleSettings;
use OxidSolutionCatalysts\Unzer\Service\UnzerSDKLoader;
use PHPUnit\Framework\TestCase;
use UnzerSDK\Unzer;

class UnzerSDKLoaderTest extends TestCase
{
    public function testSimpleSDKLoading(): void
    {
        $sut = $this->getSut([
            'getStandardPrivateKey' => 's-priv-someExampleOfGoodKey',
            'isDebugMode' => false
        ]);

        $loadedSdk = $sut->getUnzerSDK();
        $this->assertInstanceOf(Unzer::class, $loadedSdk);
        $this->assertNull($loadedSdk->getDebugHandler());
        $this->assertFalse($loadedSdk->isDebugMode());
    }

    public function testSimpleSDKLoadingWithWrongKey(): void
    {
        $sut = $this->getSut([
            'isDebugMode' => false
        ]);

        $this->expectExceptionMessage(
            'Try to get the SDK with the Key "" defined by paymentId "", currency "", customerType ""'
        );
        $this->assertInstanceOf(Unzer::class, $sut->getUnzerSDK());
    }

    public function testDebugSDKLoading(): void
    {
        $sut = $this->getSut([
            'getStandardPrivateKey' => 's-priv-someExampleOfGoodKey',
            'isDebugMode' => true
        ]);

        $loadedSdk = $sut->getUnzerSDK();
        $this->assertInstanceOf(DebugHandler::class, $loadedSdk->getDebugHandler());
        $this->assertTrue($loadedSdk->isDebugMode());
    }

    protected function getSut($moduleSettingValues): UnzerSDKLoader
    {
        $moduleSettings = $this->createConfiguredMock(ModuleSettings::class, $moduleSettingValues);
        $debugHandler = $this->getMockBuilder(DebugHandler::class)
            ->setConstructorArgs([$this->createMock(Logger::class)])
            ->getMock();

        return new UnzerSDKLoader($moduleSettings, $debugHandler);
    }
}
