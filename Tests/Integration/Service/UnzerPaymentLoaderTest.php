<?php

namespace OxidSolutionCatalysts\Unzer\Tests\Integration\Service;

use OxidEsales\Eshop\Application\Model\Payment as PaymentModel;
use OxidEsales\Eshop\Core\Session;
use OxidSolutionCatalysts\Unzer\Service\UnzerPaymentLoader;
use PHPUnit\Framework\TestCase;

class UnzerPaymentLoaderTest extends TestCase
{
    /**
     * @dataProvider checkPaymentTypeCanBeLoadedDataProvider
     */
    public function testPaymentTypeCanBeLoaded($paymentId, $paymentClass): void
    {
        $paymentStub = $this->createPartialMock(PaymentModel::class, ['getId']);
        $paymentStub->method('getId')->willReturn($paymentId);

        $sessionStub = $this->createPartialMock(Session::class, []);
        $sut = new UnzerPaymentLoader($sessionStub);

        $loadedPaymentType = $sut->getUnzerPayment($paymentStub);
        $this->assertInstanceOf($paymentClass, $loadedPaymentType);
        $this->assertSame($paymentId, $loadedPaymentType->getID());
    }

    public function checkPaymentTypeCanBeLoadedDataProvider(): array
    {
        $testCases = [];

        foreach (UnzerPaymentLoader::UNZERCLASSNAMEMAPPING as $key => $className) {
            $testCases[] = [
                'paymentId' => $key,
                'paymentClass' => $className
            ];
        }

        return $testCases;
    }
}