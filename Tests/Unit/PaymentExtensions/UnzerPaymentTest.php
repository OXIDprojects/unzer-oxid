<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Tests\Unit\Exception;

use OxidEsales\Eshop\Application\Model\Basket as BasketModel;
use OxidEsales\Eshop\Application\Model\User as UserModel;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\UnzerPayment;
use OxidSolutionCatalysts\Unzer\Service\SavedPayment\SavedPaymentSessionService;
use OxidSolutionCatalysts\Unzer\Service\TmpOrderService;
use OxidSolutionCatalysts\Unzer\Service\Unzer as UnzerService;
use PHPUnit\Framework\TestCase;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\PaymentTypes\Invoice as InvoiceAlias;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Unzer as UnzerSDK;

class UnzerPaymentTest extends TestCase
{
    public function testDefaultExecute(): void
    {
        $currency = new \stdClass();
        $currency->name = 'EUR';

        $userModel = $this->createPartialMock(UserModel::class, []);
        $basketModel = $this->createConfiguredMock(BasketModel::class, [
            'getPrice' => new \OxidEsales\Eshop\Core\Price(123),
            'getBasketCurrency' => $currency
        ]);
        $unzerBasket = $this->createPartialMock(Basket::class, []);

        $unzerServiceMock = $this->getMockBuilder(UnzerService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unzerServiceMock->method('getPaymentProcedure')->willReturn('charge');
        $unzerServiceMock->method('prepareOrderRedirectUrl')->willReturn('someRedirectUrl');
        $unzerServiceMock->method('getUnzerCustomer')->with($userModel)->willReturn($customer = new Customer());
        $unzerServiceMock->method('getShopMetadata')->willReturn($metadata = new Metadata());
        $unzerServiceMock->method('generateUnzerOrderId')->willReturn('1234567890');
        $unzerServiceMock->method('getUnzerBasket')->willReturn($unzerBasket);

        $chargeResult = $this->createPartialMock(Charge::class, []);
        $unzerServiceMock->expects($this->once())->method('setSessionVars')->with($chargeResult);

        $sdkMock = $this->createPartialMock(UnzerSDK::class, ['createCustomer', 'fetchCustomer']);
        $sdkMock->method('createCustomer')->willReturn($customer = new Customer());
        $sdkMock->method('fetchCustomer')->willReturn($customer = new Customer());

        $sut = $this->getMockForAbstractClass(
            UnzerPayment::class,
            [
                $sdkMock,
                $unzerServiceMock,
                new \OxidSolutionCatalysts\Unzer\Service\DebugHandler(
                    $this->createMock(\Monolog\Logger::class)
                ),
                $this->createMock(SavedPaymentSessionService::class),
                $this->createMock(TmpOrderService::class)
            ],
            '',
            true,
            true,
            true,
            [
                'getUnzerPaymentTypeObject'
            ]
        );
        $sut->method('getUnzerPaymentTypeObject')->willReturn(
            $paymentTypeMock = $this->createPartialMock(InvoiceAlias::class, ['charge'])
        );
        $paymentTypeMock->expects($this->atLeastOnce())
            ->method('charge')
            ->with(
                123,
                'EUR',
                'someRedirectUrl',
                $customer,
                '1234567890',
                $metadata,
                $unzerBasket
            )
            ->willReturn($chargeResult);

        $sut->execute($userModel, $basketModel);
    }
}
