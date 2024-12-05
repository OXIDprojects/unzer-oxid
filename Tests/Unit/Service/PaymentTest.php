<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Tests\Unit\Service;

use OxidEsales\Eshop\Application\Model\Payment as PaymentModel;
use OxidEsales\Eshop\Application\Model\User as UserModel;
use OxidEsales\Eshop\Application\Model\Basket as BasketModel;
use OxidEsales\Eshop\Core\Session;
use OxidSolutionCatalysts\Unzer\Exception\Redirect;
use OxidSolutionCatalysts\Unzer\Exception\RedirectWithMessage;
use OxidSolutionCatalysts\Unzer\PaymentExtensions\UnzerPayment;
use OxidSolutionCatalysts\Unzer\Service\Payment as PaymentService;
use OxidSolutionCatalysts\Unzer\Service\SavedPayment\SavedPaymentSessionService;
use OxidSolutionCatalysts\Unzer\Service\TmpOrderService;
use OxidSolutionCatalysts\Unzer\Service\TmpOrderServiceInterface;
use OxidSolutionCatalysts\Unzer\Service\Unzer as UnzerService;
use OxidSolutionCatalysts\Unzer\Service\PaymentExtensionLoader;
use OxidSolutionCatalysts\Unzer\Service\Transaction as TransactionService;
use OxidSolutionCatalysts\Unzer\Service\Translator;
use OxidSolutionCatalysts\Unzer\Service\UnzerSDKLoader;
use PHPUnit\Framework\TestCase;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Unzer;

class PaymentTest extends TestCase
{
    /**
     * @dataProvider executePaymentStatusDataProvider
     */
    public function testRegularExecuteUnzerPaymentFlow(string $expectedValue): void
    {
        $expectedResult = ($expectedValue !== 'ERROR');

        $sut = $this->getPaymentServiceMock(
            $this->getTranslatorMock(),
            $expectedValue,
            $this->getUnzerSDKPaymentMock(),
            $expectedResult,
            null
        );

        $paymentModelMock = $this->getPaymentModelMock();
        $this->assertSame($expectedResult, $sut->executeUnzerPayment($paymentModelMock));
    }

    public function executePaymentStatusDataProvider(): array
    {
        return [
            ['OK'],
            ['NOT_FINISHED'],
            ['ERROR']
        ];
    }

    public function testUnzerRedirectReThrownFlow(): void
    {
        $sut = $this->getPaymentServiceMock(
            $this->getTranslatorMock(),
            'ERROR',
            $this->getUnzerSDKPaymentMock(),
            null,
            $this->getRedirectException()
        );

        $this->expectException(Redirect::class);

        try {
            $sut->executeUnzerPayment($this->getPaymentModelMock());
        } catch (Redirect $exception) {
            $this->assertSame("someUrl", $exception->getDestination());

            throw $exception;
        } catch (\Exception $e) {
            $this->assertSame($e, $this->getRedirectException(), 'Exception Type mot matching');
        }
    }

    public function testUnzerApiExceptionCaseConvertedToRedirectWithMessage(): void
    {
        $sut = $this->getPaymentServiceMock(
            $this->getTranslatorMock(),
            'ERROR',
            $this->getUnzerSDKPaymentMock(),
            null,
            $this->getUnzerApiException()
        );

        $this->expectException(RedirectWithMessage::class);

        try {
            $sut->executeUnzerPayment($this->getPaymentModelMock());
        } catch (RedirectWithMessage $exception) {
            $this->assertSame("someUrl", $exception->getDestination());
            $this->assertSame("specialTranslation", $exception->getMessageKey());

            throw $exception;
        }
    }

    public function testRegularExceptionCaseConvertedToRedirectWithMessage(): void
    {
        $sut = $this->getPaymentServiceMock(
            $this->getTranslatorMock(),
            'ERROR',
            $this->getUnzerSDKPaymentMock(),
            null,
            $this->getException()
        );

        $this->expectException(RedirectWithMessage::class);

        try {
            $sut->executeUnzerPayment($this->getPaymentModelMock());
        } catch (RedirectWithMessage $exception) {
            $destination = $exception->getDestination();
            $this->assertSame("someUrl", $destination);
            $this->assertSame("clientMessage", $exception->getMessageKey());

            throw $exception;
        }
    }

    protected function getSessionMock()
    {
        $sessionStub = $this->createPartialMock(Session::class, ['getVariable', 'getBasket', 'getUser']);
        $sessionStub->method('getVariable')
            ->willReturnCallback(function ($param) {
                return $param === 'PaymentId' ? 'examplePaymentId' : 'someValue';
            });

        $sessionStub->method('getBasket')->willReturn(
            $this->createConfiguredMock(BasketModel::class, [
                'getBasketCurrency' => $this->getBasketCurrency()
            ])
        );
        $sessionStub->method('getUser')->willReturn(
            $this->createConfiguredMock(UserModel::class, [
                'getId' => 'someId'
            ])
        );
        return $sessionStub;
    }

    protected function getBasketModelMock()
    {
        return $this->createConfiguredMock(BasketModel::class, [
            'getBasketCurrency' => $this->getBasketCurrency()
        ]);
    }

    protected function getTransactionServiceMock()
    {
        $transactionMock = $this->createPartialMock(TransactionService::class, ['writeTransactionToDB']);
        $transactionMock->method('writeTransactionToDB')
            ->withAnyParameters()
            ->willReturn(true);

        return $transactionMock;
    }

    protected function getPaymentServiceMock(
        $translatorMock,
        $getUnzerPaymentStatusReturn,
        $getSessionUnzerPaymentReturn,
        $executeUnzerPaymentReturn,
        $executeWillThrowException
    ) {
        $methodsToMock = ['removeTemporaryOrder', 'getUnzerPaymentStatus', 'getSessionUnzerPayment'];
        if (null !== $executeUnzerPaymentReturn) {
            $methodsToMock[] = 'executeUnzerPayment';
        }
        $sut = $this->getMockBuilder(PaymentService::class)
            ->setConstructorArgs([
                $this->getSessionMock(),
                $this->getExtensionLoaderMock($executeWillThrowException),
                $translatorMock,
                $this->getUnzerServiceMock(),
                $this->getUnzerSDKLoaderMock(),
                $this->getTransactionServiceMock(),
                $this->createMock(TmpOrderServiceInterface::class)
            ])
            ->onlyMethods($methodsToMock)
            ->getMock();

        $sut->method('getUnzerPaymentStatus')->willReturn($getUnzerPaymentStatusReturn);
        $sut->method('getSessionUnzerPayment')->willReturn($getSessionUnzerPaymentReturn);
        if (null !== $executeUnzerPaymentReturn) {
            $sut->method('executeUnzerPayment')->willReturn($executeUnzerPaymentReturn);
        }

        return $sut;
    }

    protected function getExtensionLoaderMock(
        $executeWillThrowException
    ) {
        $paymentModel = $this->getPaymentModelMock();

        $cfgPaymentMock = ['getUnzerPaymentTypeObject', 'execute'];
        $paymentExtension = $this->getPaymentExtensionMock($cfgPaymentMock);
        if (null !== $executeWillThrowException) {
            $paymentExtension->method('execute')
                ->willThrowException($executeWillThrowException);
        } else {
            $paymentExtension->method('execute')
                ->willReturn(true);
        }

        $extensionLoader = $this->getMockBuilder(PaymentExtensionLoader::class)
            ->setConstructorArgs([
                $this->getUnzerSDKLoaderMock(),
                $this->getUnzerServiceMock(),
                $this->getLoggerMock(),
                $this->createMock(SavedPaymentSessionService::class),
                $this->createMock(TmpOrderService::class)
            ])->onlyMethods(['getPaymentExtension', 'getPaymentExtensionByCustomerTypeAndCurrency'])
            ->getMock();

        $extensionLoader
            ->method('getPaymentExtension')
            ->with($paymentModel)
            ->willReturn($paymentExtension);

        $extensionLoader
            ->method('getPaymentExtensionByCustomerTypeAndCurrency')
            ->with($paymentModel, '', 'EUR')
            ->willReturn($paymentExtension);

        return $extensionLoader;
    }

    protected function getTranslatorMock()
    {
        $translatorMock = $this->createPartialMock(Translator::class, ['translateCode']);
        $translatorMock->method('translateCode')
            ->with("No error id provided", "clientMessage")
            ->willReturn("specialTranslation");
        return $translatorMock;
    }

    protected function getPaymentExtensionMock($configuration = [])
    {
        return $this->createPartialMock(UnzerPayment::class, $configuration);
    }

    protected function getPaymentModelMock()
    {
        return $this->createConfiguredMock(PaymentModel::class, []);
    }

    protected function getUnzerServiceMock()
    {
        $unzerServiceMock = $this->createPartialMock(UnzerService::class, ['prepareOrderRedirectUrl']);
        $unzerServiceMock->method('prepareOrderRedirectUrl')
            ->willReturn('someUrl');
        return $unzerServiceMock;
    }

    protected function getUnzerSDKMock()
    {
        return $this->createConfiguredMock(Unzer::class, []);
    }

    protected function getUnzerSDKPaymentMock()
    {
        return $this->createConfiguredMock(Payment::class, []);
    }

    protected function getUnzerSDKLoaderMock()
    {
        $UnzerSDKMock = $this->getUnzerSDKMock();
        $unzerSDKLoaderMock = $this->createPartialMock(UnzerSDKLoader::class, [
            'getUnzerSDK'
        ]);

        $basketCurrency = $this->getBasketCurrency();
        $currencyName = $basketCurrency->name;

        $unzerSDKLoaderMock->method('getUnzerSDK')
            ->with('', $currencyName)
            ->willReturn($UnzerSDKMock);
        return $unzerSDKLoaderMock;
    }
    protected function getBasketCurrency()
    {
        $basketCurrency = new \stdClass();
        $basketCurrency->name = 'EUR';
        return $basketCurrency;
    }

    protected function getRedirectException()
    {
        return new Redirect("someUrl");
    }

    protected function getRedirectWithMessageException()
    {
        return new RedirectWithMessage("someUrl", "clientMessage");
    }

    protected function getUnzerApiException()
    {
        return new UnzerApiException(
            "merchantMessage",
            "clientMessage",
            "specialCode"
        );
    }

    protected function getException()
    {
        return new \Exception("clientMessage");
    }

    private function getLoggerMock()
    {
        return new \OxidSolutionCatalysts\Unzer\Service\DebugHandler(
            $this->createMock(\Monolog\Logger::class)
        );
    }
}
