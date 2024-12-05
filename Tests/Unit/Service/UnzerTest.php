<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Tests\Unit\Service;

use OxidEsales\Eshop\Application\Model\Basket as ShopBasketModel;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\Price;
use OxidEsales\EshopCommunity\Core\DatabaseProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Database\TransactionService;
use OxidSolutionCatalysts\Unzer\Service\Context;
use OxidSolutionCatalysts\Unzer\Service\ModuleSettings;
use OxidSolutionCatalysts\Unzer\Service\Payment;
use OxidSolutionCatalysts\Unzer\Service\PaymentExtensionLoader;
use OxidSolutionCatalysts\Unzer\Service\TmpOrderServiceInterface;
use OxidSolutionCatalysts\Unzer\Service\Transaction;
use OxidSolutionCatalysts\Unzer\Service\Translator;
use OxidSolutionCatalysts\Unzer\Service\Unzer;
use OxidSolutionCatalysts\Unzer\Service\UnzerSDKLoader;
use OxidSolutionCatalysts\Unzer\Service\UnzerVoucherBasketItems;
use PHPUnit\Framework\TestCase;

class UnzerTest extends TestCase
{
    /**
     * @dataProvider prepareRedirectUrlDataProvider
     */
    public function testprepareRedirectUrl($shopUrl, $destination, $expectedShopUrl)
    {
        $sut = $this->getSut();

        Registry::set(
            Config::class,
            $this->createConfiguredMock(Config::class, ['getSslShopUrl' => $shopUrl])
        );

        $this->assertSame(
            $expectedShopUrl,
            $sut->prepareRedirectUrl($destination)
        );
    }

    public function prepareRedirectUrlDataProvider(): array
    {
        return [
            ['value/', 'order', 'value/index.php?cl=order'],
            ['value/', 'thankyou', 'value/index.php?cl=thankyou']
        ];
    }

    /**
     * @dataProvider prepareOrderRedirectUrlDataProvider
     */
    public function testprepareOrderRedirectUrl($shopUrl, $addPending, $expectedShopUrl)
    {
        $sut = $this->getSut();

        Registry::set(Config::class, $this->createConfiguredMock(Config::class, ['getSslShopUrl' => $shopUrl]));

        $this->assertSame(
            $expectedShopUrl,
            $sut->prepareOrderRedirectUrl($addPending)
        );
    }

    public function prepareOrderRedirectUrlDataProvider(): array
    {
        return [
            ['value/', false, 'value/index.php?cl=order'],
            ['value/', true, 'value/index.php?cl=order&fnc=unzerExecuteAfterRedirect']
        ];
    }

    /**
     * @dataProvider getPaymentProcedureDataProvider
     * @covers \OxidSolutionCatalysts\Unzer\Service\Unzer::getPaymentProcedure
     */
    public function testGetPaymentProcedure($paymentId, $expectedProcedure)
    {
        $sut = $this->getSut([
            ModuleSettings::class => $this->createConfiguredMock(ModuleSettings::class, [
                'getPaymentProcedureSetting' => 'special'
            ])
        ]);

        $this->assertSame($expectedProcedure, $sut->getPaymentProcedure($paymentId));
    }

    public function getPaymentProcedureDataProvider(): array
    {
        return [
            ['paypal', 'special'],
            ['card', 'special'],
            ['applepay', 'special'],
            ['installment-secured', 'authorize'],
            ['paylater-installment', 'authorize'],
            ['paylater-invoice', 'authorize'],
            ['other', ModuleSettings::PAYMENT_CHARGE],
        ];
    }

    public function testGetPaymentProcedureAuthorizeForListedPayment(): void
    {
        $paymentType = 'paypal';

        $moduleSettings = $this->createPartialMock(
            ModuleSettings::class,
            ['getPaymentProcedureSetting']
        );

        $moduleSettings->method('getPaymentProcedureSetting')
            ->willReturn(ModuleSettings::PAYMENT_AUTHORIZE);

        $sut = $this->getSut([ModuleSettings::class => $moduleSettings]);

        $paymentProcedure = $sut->getPaymentProcedure($paymentType);
        $this->assertEquals(ModuleSettings::PAYMENT_AUTHORIZE, $paymentProcedure);
    }

    public function testGetPaymentProcedureChargeForListedPayment(): void
    {
        $paymentType = 'paypal';

        $moduleSettings = $this->createPartialMock(
            ModuleSettings::class,
            ['getPaymentProcedureSetting']
        );

        $moduleSettings->method('getPaymentProcedureSetting')
            ->willReturn(ModuleSettings::PAYMENT_CHARGE);

        $sut = $this->getSut([ModuleSettings::class => $moduleSettings]);

        $paymentProcedure = $sut->getPaymentProcedure($paymentType);
        $this->assertEquals(ModuleSettings::PAYMENT_CHARGE, $paymentProcedure);
    }

    public function testGetPaymentProcedureChargeForNonlistedPayment(): void
    {
        $paymentType = 'unlisted_payment';

        $moduleSettings = $this->createPartialMock(
            ModuleSettings::class,
            ['getPaymentProcedureSetting']
        );

        $moduleSettings->method('getPaymentProcedureSetting')
            ->willReturn(ModuleSettings::PAYMENT_AUTHORIZE);

        $sut = $this->getSut([ModuleSettings::class => $moduleSettings]);

        $paymentProcedure = $sut->getPaymentProcedure($paymentType);
        $this->assertEquals(ModuleSettings::PAYMENT_CHARGE, $paymentProcedure);
    }

    public function testGetPaymentProcedureChargeForUnlistedPayment(): void
    {
        $paymentType = 'dummy_payment';

        $moduleSettings = $this->createPartialMock(
            ModuleSettings::class,
            ['getPaymentProcedureSetting']
        );
        $moduleSettings->method('getPaymentProcedureSetting')
            ->willReturn(ModuleSettings::PAYMENT_CHARGE);

        $sut = $this->getSut([ModuleSettings::class => $moduleSettings]);

        $paymentProcedure = $sut->getPaymentProcedure($paymentType);
        $this->assertEquals(ModuleSettings::PAYMENT_CHARGE, $paymentProcedure);
    }

    public function testGetBasicUnzerBasket(): void
    {
        $currency = new \stdClass();
        $currency->name = 'EUR';

        $price = oxNew(Price::class);
        $price1 = oxNew(Price::class);
        $price1->setPrice(234.56);
        $shopBasketModel = $this->createPartialMock(ShopBasketModel::class, [
            'getNettoSum',
            'getBruttoSum',
            'getPrice',
            'getBasketCurrency',
            'getTotalDiscount',
            'getDeliveryCost',
            'getVoucherDiscount'
        ]);
        $shopBasketModel->method('getNettoSum')->willReturn(123.45);
        $shopBasketModel->method('getBruttoSum')->willReturn(234.56);
        $shopBasketModel->method('getPrice')->willReturn($price1);
        $shopBasketModel->method('getBasketCurrency')->willReturn($currency);
        $shopBasketModel->method('getTotalDiscount')->willReturn($price);
        $shopBasketModel->method('getDeliveryCost')->willReturn($price);
        $shopBasketModel->method('getVoucherDiscount')->willReturn($price);

        $sut = $this->getSut();
        $result = $sut->getUnzerBasket('someOrderId', $shopBasketModel);

        $this->assertInstanceOf(\UnzerSDK\Resources\Basket::class, $result);
        $this->assertSame(234.56, $result->getAmountTotalGross());
        $this->assertSame('EUR', $result->getCurrencyCode());
        $this->assertSame('someOrderId', $result->getOrderId());
    }

    public function testGetContentUnzerBasket(): void
    {
        $currency = new \stdClass();
        $currency->name = 'EUR';

        $price = oxNew(Price::class);
        $priceMock = $this->createPartialMock(Price::class, [
            'getVatValue'
        ]);
        $priceMock->method('getVatValue')->willReturn(19.0);

        $basketItem1 = $this->createConfiguredMock(BasketItem::class, [
            'getTitle' => 'basket item title 1',
            'getUnitPrice' => new Price(20),
            'getAmount' => 5,
            'getPrice' => $priceMock
        ]);

        $basketItem2 = $this->createConfiguredMock(BasketItem::class, [
            'getTitle' => 'basket item title 2',
            'getUnitPrice' => new Price(10),
            'getAmount' => 4,
            'getPrice' => $priceMock
        ]);
        $price1 = oxNew(Price::class);
        $price1->setPrice(234.56);
        $shopBasketModel = $this->createPartialMock(ShopBasketModel::class, [
            'getNettoSum',
            'getBruttoSum',
            'getPrice',
            'getBasketCurrency',
            'getContents',
            'getTotalDiscount',
            'getDeliveryCost',
            'getVoucherDiscount'
        ]);
        $shopBasketModel->method('getNettoSum')->willReturn(123.45);
        $shopBasketModel->method('getBruttoSum')->willReturn(234.56);
        $shopBasketModel->method('getPrice')->willReturn($price1);
        $shopBasketModel->method('getContents')->willReturn([$basketItem1, $basketItem2]);
        $shopBasketModel->method('getBasketCurrency')->willReturn($currency);
        $shopBasketModel->method('getTotalDiscount')->willReturn($price);
        $shopBasketModel->method('getDeliveryCost')->willReturn($price);
        $shopBasketModel->method('getVoucherDiscount')->willReturn($price);

        $sut = $this->getSut();
        $result = $sut->getUnzerBasket("someOrderId", $shopBasketModel);

        $this->assertSame(3, $result->getItemCount()); //two goods, no delivery. no voucher

        /** @var \UnzerSDK\Resources\EmbeddedResources\BasketItem[] $items */
        $items = $result->getBasketItems();

        $this->assertSame('basket item title 1', $items[0]->getTitle());
        $this->assertSame(20.0, $items[0]->getAmountPerUnitGross());
        $this->assertSame(5, $items[0]->getQuantity());
    }

    public function testGetUnzerPaymentIdFromRequest(): void
    {
        $mockSession = $this->createMock(Session::class);
        $mockTranslator = $this->createMock(Translator::class);
        $mockContext = $this->createMock(Context::class);
        $mockModuleSettings = $this->createMock(ModuleSettings::class);
        $mockUnzerVoucherBasketItemsService = $this->createMock(UnzerVoucherBasketItems::class);

        // Create a partial mock of the Unzer class
        $sut = $this->getMockBuilder(Unzer::class)
            ->setConstructorArgs([
                $mockSession,
                $mockTranslator,
                $mockContext,
                $mockModuleSettings,
                $mockUnzerVoucherBasketItemsService
            ])
            ->onlyMethods(['getUnzerStringRequestParameter']) // Mock only the method you need
            ->getMock();

        // Define what the mocked method should return
        $sut->method('getUnzerStringRequestParameter')
            ->with('paymentData')
            ->willReturn(json_encode(['id' => 'someId']));

        $this->assertSame('someId', $sut->getUnzerPaymentIdFromRequest());
    }

    public function testGetUnzerPaymentIdFromRequestFailure(): void
    {
        $mockSession = $this->createMock(Session::class);
        $mockTranslator = $this->createMock(Translator::class);
        $mockContext = $this->createMock(Context::class);
        $mockModuleSettings = $this->createMock(ModuleSettings::class);
        $mockUnzerVoucherBasketItemsService = $this->createMock(UnzerVoucherBasketItems::class);

        // Create a partial mock of the Unzer class
        $sut = $this->getMockBuilder(Unzer::class)
            ->setConstructorArgs([
                $mockSession,
                $mockTranslator,
                $mockContext,
                $mockModuleSettings,
                $mockUnzerVoucherBasketItemsService
            ])
            ->onlyMethods(['getUnzerStringRequestParameter']) // Mock only the method you need
            ->getMock();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('oscunzer_WRONGPAYMENTID');

        $sut->getUnzerPaymentIdFromRequest();
    }

    private function getSut(array $settings = []): Unzer
    {
        $translatorMock = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translatorMock->expects($this->any())
            ->method('translate')
            ->willReturn('Shipping costs');
        $voucherBasketItemsMock = $this->getMockBuilder(UnzerVoucherBasketItems::class)
            ->disableOriginalConstructor()
            ->getMock();
        $voucherBasketItemsMock->expects($this->any())
            ->method('getVoucherBasketItems')
            ->willReturn([]);

        return new Unzer(
            $this->createPartialMock(Session::class, []),
            $translatorMock,
            $this->createPartialMock(Context::class, []),
            $settings[ModuleSettings::class] ?:
                $this->createPartialMock(ModuleSettings::class, []),
            $voucherBasketItemsMock
        );
    }
}
