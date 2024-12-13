<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Tests\Unit\Service;

use OxidEsales\Eshop\Core\UtilsDate;
use OxidSolutionCatalysts\Unzer\Model\Transaction;
use OxidSolutionCatalysts\Unzer\Service\Context;
use OxidSolutionCatalysts\Unzer\Service\Transaction as TransactionService;
use PHPUnit\Framework\TestCase;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\EmbeddedResources\Amount;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;

class TransactionTest extends TestCase
{
    public function testEmptyPaymentWriteTransactionToDB(): void
    {
        $model = $this->createPartialMock(Transaction::class, ['assign']);
        $sut = $this->getTransactionServiceMock($model);

        $model->expects($this->once())->method('assign')->with([
            'oxorderid' => 'orderId',
            'oxuserid' => 'userId',
            'oxshopid' => 5,
            'oxactiondate' => '2021-12-10 16:44:54',
            'customertype' => '',
            'metadata' => '""',
        ]);

        $sut->writeTransactionToDB("orderId", "userId", null);
    }

    public function testNotEmptyPaymentWriteTransactionToDB(): void
    {
        $model = $this->createPartialMock(Transaction::class, ['assign']);
        $sut = $this->getTransactionServiceMock($model);

        $payment = $this->createConfiguredMock(Payment::class, [
            'getAmount' => $this->createConfiguredMock(Amount::class, ['getTotal' => 10.20]),
            'getCurrency' => 'specialCurrency',
            'getId' => 'unzerPaymentId',
            'getStateName' => 'statename',
            'getInitialTransaction' => $this->createConfiguredMock(AbstractTransactionType::class, [
                'getShortID' => 'unzerShortId',
                'getTraceID' => null
            ]),
            'getMetadata' => $this->createConfiguredMock(Metadata::class, [
                'jsonSerialize' => 'metadataJson'
            ]),
            'getCustomer' => $this->createConfiguredMock(Customer::class, [
                'getId' => 'unzerCustomerId'
            ])
        ]);

        $model->expects($this->at(0))->method('assign')->with([
            'oxorderid' => 'orderId',
            'shortid' => 'unzerShortId',
            'traceid' => null,
            'oxshopid' => 5,
            'oxuserid' => 'userId',
            'oxactiondate' => '2021-12-10 16:44:54',
            'amount' => 10.20,
            'currency' => 'specialCurrency',
            'typeid' => 'unzerPaymentId',
            'oxaction' => 'statename',
            'metadata' => 'metadataJson',
            'customerid' => 'unzerCustomerId',
            'customertype' => '',
            'remaining' => 0.0,
        ]);

        $this->assertTrue($sut->writeTransactionToDB("orderId", "userId", $payment));
        $this->assertFalse($sut->writeTransactionToDB("orderId", "userId", $payment));
    }

    private function getTransactionServiceMock($model): TransactionService
    {
        $sut = $this->getMockBuilder(TransactionService::class)->setConstructorArgs([
            $this->createConfiguredMock(Context::class, ['getCurrentShopId' => 5]),
            $this->createConfiguredMock(UtilsDate::class, ['getTime' => 1639151094])
        ])->onlyMethods(['getNewTransactionObject'])->getMock();
        $sut->method('getNewTransactionObject')->willReturn($model);
        return $sut;
    }

    public function testGetNewTransactionObject(): void
    {
        $transactionService = new class (
            $this->createPartialMock(Context::class, []),
            $this->createConfiguredMock(UtilsDate::class, [])
        ) extends \OxidSolutionCatalysts\Unzer\Service\Transaction {
            public function testGetNewTransactionObject()
            {
                return $this->getNewTransactionObject();
            }
        };

        $item = $transactionService->testGetNewTransactionObject();
        $this->assertInstanceOf(Transaction::class, $item);
        $this->assertNull($item->getId());
    }
}
