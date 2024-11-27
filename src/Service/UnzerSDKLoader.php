<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Service;

use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidSolutionCatalysts\Unzer\Exception\UnzerException;
use RuntimeException;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidSolutionCatalysts\Unzer\Core\UnzerDefinitions;
use UnzerSDK\Unzer;

class UnzerSDKLoader
{
    /**
     * @var ModuleSettings
     */
    private $moduleSettings;

    /**
     * @var DebugHandler
     */
    private DebugHandler $debugHandler;


    /**
     * @param ModuleSettings $moduleSettings
     * @param DebugHandler $debugHandler
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function __construct(
        ModuleSettings $moduleSettings,
        DebugHandler $debugHandler
    ) {
        $this->moduleSettings = $moduleSettings;
        $this->debugHandler = $debugHandler;
    }

    /**
     * @param string $paymentId
     * @param string $currency
     * @param string $customerType
     * @return Unzer
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @throws UnzerException
     */
    public function getUnzerSDK(string $paymentId = '', string $currency = '', string $customerType = ''): Unzer
    {
        if (UnzerDefinitions::INVOICE_UNZER_PAYMENT_ID === $paymentId) {
            if (empty($customerType) || empty($currency)) {
                throw new UnzerException('Customer type and currency must be set for invoice payment');
            }
            $key = $this->moduleSettings->getInvoicePrivateKeyByCustomerTypeAndCurrency(
                $customerType,
                $currency
            );
        } elseif (UnzerDefinitions::INSTALLMENT_UNZER_PAYLATER_PAYMENT_ID === $paymentId) {
            $key = $this->moduleSettings->getInstallmentPrivateKeyByCurrency(
                $currency
            );
        } else {
            $key = $this->moduleSettings->getStandardPrivateKey();
        }

        try {
            $sdk = $this->getUnzerSDKbyKey($key);
        } catch (UnzerException $e) {
            $logEntry = sprintf(
                'Try to get the SDK with the Key "%s" defined by paymentId "%s", currency "%s", customerType "%s"',
                $key,
                $paymentId,
                $currency,
                $customerType
            );
            $this->debugHandler->log($logEntry);
            throw new UnzerException($logEntry);
        }

        return $sdk;
    }

    /**
     * Creates an UnzerSDK object based upon a specific private key.
     * @param string $key
     * @return Unzer
     * @throws UnzerException
     */
    public function getUnzerSDKbyKey(string $key): Unzer
    {
        try {
            $sdk = oxNew(Unzer::class, $key);
            if ($this->moduleSettings->isDebugMode()) {
                $sdk->setDebugMode(true)->setDebugHandler($this->debugHandler);
            }
        } catch (RuntimeException $e) {
            $this->debugHandler->log($e->getMessage());
            throw new UnzerException($e->getMessage());
        }
        return $sdk;
    }

    /**
     * Initialize UnzerSDK from a payment id
     * @param string $sPaymentId
     * @return Unzer
     *
     * @throws UnzerException|DatabaseConnectionException
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getUnzerSDKbyPaymentType(string $sPaymentId): Unzer
    {
        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $row = $oDB->getRow("SELECT u.CURRENCY, o.OXDELCOMPANY, o.OXBILLCOMPANY, o.OXPAYMENTTYPE
                            FROM oscunzertransaction u
                            LEFT JOIN oxorder o ON u.OXORDERID = o.OXID
                            WHERE u.TYPEID = :typeid
                            ORDER BY u.OXTIMESTAMP DESC LIMIT 1", [':typeid' => $sPaymentId]);

        $customerType = '';
        $currency = '';
        $paymentId = '';
        if ($row) {
            $currency = $row['CURRENCY'];
            $paymentId = $row['OXPAYMENTTYPE'];
            if ($paymentId === UnzerDefinitions::INVOICE_UNZER_PAYMENT_ID) {
                $customerType = 'B2C';
                if (!empty($row['OXDELCOMPANY']) || !empty($row['OXBILLCOMPANY'])) {
                    $customerType = 'B2B';
                }
            }
        }

        return $this->getUnzerSDK($paymentId, $currency, $customerType);
    }
}
