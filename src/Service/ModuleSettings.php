<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Service;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Exception\ModuleConfigurationNotFoundException;
use OxidEsales\EshopCommunity\Core\Exception\FileException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidEsales\Facts\Facts;
use OxidSolutionCatalysts\Unzer\Module;
use Exception;
use OxidEsales\EshopCommunity\Application\Model\User;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ModuleSettings
{
    public const SYSTEM_MODE_SANDBOX = 'sandbox';
    public const SYSTEM_MODE_PRODUCTION = 'production';
    public const PAYMENT_CHARGE = 'charge';
    public const PAYMENT_AUTHORIZE = 'authorize';

    public const APPLE_PAY_MERCHANT_CAPABILITIES = [
        'supportsCredit' => '1',
        'supportsDebit' => '1'
    ];

    public const APPLE_PAY_NETWORKS = [
        'maestro' => '1',
        'masterCard' => '1',
        'visa' => '1'
    ];

    private ModuleSettingBridgeInterface $moduleSettingBridge;
    private ModuleConfigurationDaoBridgeInterface $moduleInfoBridge;
    private Session $session;
    private Config $config;

    public function __construct(
        ModuleSettingBridgeInterface $moduleSettingBridge,
        ModuleConfigurationDaoBridgeInterface $moduleInfoBridge,
        Session $session,
        Config $config
    ) {
        $this->moduleSettingBridge = $moduleSettingBridge;
        $this->moduleInfoBridge = $moduleInfoBridge;
        $this->session = $session;
        $this->config = $config;
    }

    public function isDebugMode(): bool
    {
        return $this->getSettingValue('UnzerDebug') === true;
    }

    public function isSandboxMode(): bool
    {
        return $this->getSystemMode() === self::SYSTEM_MODE_SANDBOX;
    }

    public function getSystemMode(): string
    {
        if ($this->getSettingValue('UnzerSystemMode')) {
            return self::SYSTEM_MODE_PRODUCTION;
        }
        return self::SYSTEM_MODE_SANDBOX;
    }

    public function setSystemMode(string $systemMode): void
    {
        $this->saveSetting('UnzerSystemMode', $systemMode);
    }

    public function getInstallmentRate(): float
    {
        /** @var float $unzerOption */
        $unzerOption = $this->getSettingValue('UnzerOption_oscunzer_installment_rate');
        return $unzerOption;
    }

    public function getStandardPrivateKey(): string
    {
        /** @var string $unzerPrivateKey */
        $unzerPrivateKey = $this->getSettingValue($this->getSystemMode() . '-UnzerPrivateKey');
        return $unzerPrivateKey;
    }

    public function getStandardPublicKey(): string
    {
        /** @var string $unzerPublicKey */
        $unzerPublicKey = $this->getSettingValue($this->getSystemMode() . '-UnzerPublicKey');
        return $unzerPublicKey;
    }

    public function useModuleJQueryInFrontend(): bool
    {
        /** @var bool $unzerJQuery */
        $unzerJQuery = $this->getSettingValue('UnzerjQuery');
        return $unzerJQuery;
    }

    public function getPaymentProcedureSetting(string $paymentMethod): string
    {
        if (
            $paymentMethod === 'installment-secured' || $paymentMethod === 'paylater-installment' ||
            $this->getSettingValue('UnzerOption_oscunzer_' . $paymentMethod)
        ) {
            return self::PAYMENT_AUTHORIZE;
        }
        return self::PAYMENT_CHARGE;
    }

    public function getModuleVersion(): string
    {
        return $this->moduleInfoBridge->get(Module::MODULE_ID)->getVersion();
    }

    public function getGitHubName(): string
    {
        return Module::GITHUB_NAME;
    }

    public function isApplePayEligibility(): bool
    {
        return (
            $this->getApplePayMerchantCert() &&
            $this->getApplePayMerchantCertKey() &&
            $this->hasWebhookConfiguration('shop')
        );
    }

    public function getApplePayLabel(): mixed
    {
        return $this->getSettingValue('applepay_label') ?:
            $this->config->getActiveShop()->getFieldData('oxcompany') ?: 'default_label';
    }

    public function getApplePayMerchantCapabilities(): array
    {
        /** @var array $applepayMerchCaps */
        $applepayMerchCaps = $this->getSettingValue('applepay_merchant_capabilities');
        return array_merge(
            self::APPLE_PAY_MERCHANT_CAPABILITIES,
            $applepayMerchCaps
        );
    }

    public function getActiveApplePayMerchantCapabilities(): array
    {
        return array_keys(array_filter(
            $this->getApplePayMerchantCapabilities(),
            [$this, 'isActiveSetting']
        ));
    }

    public function getApplePayNetworks(): array
    {
        /** @var array $applepayNetworks */
        $applepayNetworks = $this->getSettingValue('applepay_networks');
        return array_merge(
            self::APPLE_PAY_NETWORKS,
            $applepayNetworks
        );
    }

    public function getActiveApplePayNetworks(): array
    {
        return array_keys(array_filter(
            $this->getApplePayNetworks(),
            [$this, 'isActiveSetting']
        ));
    }

    public function getApplePayMerchantIdentifier(): string
    {
        /** @var string $applepayMerchId */
        $applepayMerchId =
            $this->getSettingValue($this->getSystemMode() . '-applepay_merchant_identifier');
        return $applepayMerchId;
    }

    public function getApplePayMerchantCert(): string
    {
        $path = $this->getApplePayMerchantCertFilePath();
        if (file_exists($path)) {
            /** @var string $fileContest */
            $fileContest = file_get_contents($path);
            return $fileContest;
        }

        return '';
    }

    public function getApplePayMerchantCertKey(): string
    {
        $path = $this->getApplePayMerchantCertKeyFilePath();
        if (file_exists($path)) {
            /** @var string $fileContest */
            $fileContest = file_get_contents($path);
            return $fileContest;
        }

        return '';
    }

    /**
     * @throws \OxidEsales\EshopCommunity\Core\Exception\FileException
     */
    public function getApplePayPaymentPrivateKey(): string
    {
        $path = $this->getApplePayPaymentPrivateKeyFilePath();
        if (file_exists($path)) {
            /** @var string $fileContest */
            $fileContest = file_get_contents($path);
            return $fileContest;
        }

        return '';
    }

    public function getApplePayPaymentCertFilePath(): string
    {
        return $this->getFilesPath()
            . '/.applepay_payment_cert.'
            . $this->getSystemMode()
            . '.'
            . $this->config->getShopId();
    }

    public function saveApplePayMerchantCapabilities(array $capabilities): void
    {
        $this->saveSetting('applepay_merchant_capabilities', $capabilities);
    }

    /**
     * @throws FileException
     */
    public function getApplePayMerchantCertFilePath(): string
    {
        return $this->getFilesPath()
            . '/.applepay_merchant_cert.'
            . $this->getSystemMode()
            . '.'
            . $this->config->getShopId();
    }

    public function getApplePayMerchantCertKeyFilePath(): string
    {
        return $this->getFilesPath()
            . '/.applepay_merchant_cert_key.'
            . $this->getSystemMode()
            . '.'
            . $this->config->getShopId();
    }

    /**
     * @throws FileException
     */
    public function getApplePayPaymentPrivateKeyFilePath(): string
    {
        return $this->getFilesPath()
            . '/.applepay_payment_key.'
            . $this->getSystemMode()
            . '.'
            . $this->config->getShopId();
    }

    public function getFilesPath(): string
    {
        $facts = new Facts();
        $path = $facts->getShopRootPath() . DIRECTORY_SEPARATOR
            . 'var' . DIRECTORY_SEPARATOR
            . 'module' . DIRECTORY_SEPARATOR
            . Module::MODULE_ID . DIRECTORY_SEPARATOR
            . 'certs';

        if (!file_exists($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new Exception('could not create path: ' . $path);
        }

        return $path;
    }

    public function saveApplePayPaymentKeyId(string $paymentKeyId): void
    {
        $this->saveSetting($this->getSystemMode() . 'ApplePayPaymentKeyId', $paymentKeyId);
    }

    public function saveApplePayPaymentCertificateId(string $certificateId): void
    {
        $this->saveSetting($this->getSystemMode() . 'ApplePayPaymentCertificateId', $certificateId);
    }

    public function getApplePayPaymentKeyId(): string
    {
        /** @var string $paymentKeyId */
        $paymentKeyId = $this->getSettingValue($this->getSystemMode() . 'ApplePayPaymentKeyId');
        return $paymentKeyId;
    }

    public function getApplePayPaymentCertificateId(): string
    {
        /** @var string $certificateId */
        $certificateId = $this->getSettingValue($this->getSystemMode() . 'ApplePayPaymentCertificateId');
        return $certificateId;
    }

    public function getApplePayPaymentCert(): string
    {
        $path = $this->getApplePayPaymentCertFilePath();
        if (file_exists($path)) {
            /** @var string $fileContest */
            $fileContest = file_get_contents($path);
            return $fileContest;
        }

        return '';
    }

    public function saveApplePayNetworks(array $networks): void
    {
        $this->saveSetting('applepay_networks', $networks);
    }

    public function saveWebhookConfiguration(array $webhookConfig): void
    {
        $this->moduleSettingBridge->save('webhookConfiguration', $webhookConfig, Module::MODULE_ID);
    }

    public function getWebhookConfiguration(): array
    {
        /** @var array $webhookConfig */
        $webhookConfig = $this->getSettingValue('webhookConfiguration');
        return $webhookConfig;
    }

    public function getPrivateKeysWithContext(): array
    {
        $privateKeys = [];
        if ('' !== $this->getStandardPrivateKey()) {
            $privateKeys['shop'] = $this->getStandardPrivateKey();
        }
        if ('' !== $this->getInvoiceB2CEURPrivateKey()) {
            $privateKeys['b2ceur'] = $this->getInvoiceB2CEURPrivateKey();
        }
        if ('' !== $this->getInvoiceB2CCHFPrivateKey()) {
            $privateKeys['b2cchf'] = $this->getInvoiceB2CCHFPrivateKey();
        }
        if ('' !== $this->getInvoiceB2BEURPrivateKey()) {
            $privateKeys['b2beur'] = $this->getInvoiceB2BEURPrivateKey();
        }
        if ('' !== $this->getInvoiceB2BCHFPrivateKey()) {
            $privateKeys['b2bchf'] = $this->getInvoiceB2BCHFPrivateKey();
        }
        if ('' !== $this->getInstallmentB2CEURPrivateKey()) {
            $privateKeys['b2ceurinstallment'] = $this->getInstallmentB2CEURPrivateKey();
        }
        if ('' !== $this->getInstallmentB2CCHFPrivateKey()) {
            $privateKeys['b2cchfinstallment'] = $this->getInstallmentB2CCHFPrivateKey();
        }

        return $privateKeys;
    }

    private function saveSetting(string $name, bool|int|string|array $setting): void
    {
        $this->moduleSettingBridge->save($name, $setting, Module::MODULE_ID);
    }

    private function getSettingValue(string $key): mixed
    {
        $result = '';
        try {
            $result = $this->moduleSettingBridge->get($key, Module::MODULE_ID);
        } catch (ModuleConfigurationNotFoundException $exception) {
            //todo: improve
        }
        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function isActiveSetting(string $active): bool
    {
        return $active === '1';
    }

    public function isStandardEligibility(): bool
    {
        return (
            $this->getStandardPrivateKey() &&
            $this->getStandardPublicKey() &&
            $this->hasWebhookConfiguration('shop')
        );
    }

    public function isInstallmentEligibility(): bool
    {
        return (
            !$this->isB2BEligibility() && //B2C Customers only
            ($this->isB2CInstallmentEligibility() &&
                $this->hasWebhookConfiguration('b2ceurinstallment') ||
                $this->hasWebhookConfiguration('b2cchfinstallment'))
            );
    }

    public function isB2BEligibility(): bool
    {
        $user = oxNew(User::class)->getUser();
        $userCompany = $user->getFieldData('oxcompany');

        return !empty($userCompany);
    }

    public function isInvoiceEligibility(): bool
    {
        return (
                ($this->isB2CInvoiceEligibility() &&
                    $this->hasWebhookConfiguration('b2ceur') &&
                    $this->hasWebhookConfiguration('b2cchf'))
            ||
                ($this->isB2BInvoiceEligibility() &&
                    $this->hasWebhookConfiguration('b2beur') &&
                    $this->hasWebhookConfiguration('b2bchf'))
        );
    }

    public function isB2CInvoiceEligibility(): bool
    {
        return (
                $this->isBasketCurrencyCHF() &&
                !empty($this->getInvoiceB2CCHFPublicKey()) &&
                !empty($this->getInvoiceB2CCHFPrivateKey())
            ) ||
            (
                $this->isBasketCurrencyEUR() &&
                !empty($this->getInvoiceB2CEURPublicKey()) &&
                !empty($this->getInvoiceB2CEURPrivateKey())
            );
    }

    public function isB2CInstallmentEligibility(): bool
    {
        return (
                $this->isBasketCurrencyCHF() &&
                !empty($this->getInstallmentB2CCHFPublicKey()) &&
                !empty($this->getInstallmentB2CCHFPrivateKey())
            ) ||
            (
                $this->isBasketCurrencyEUR() &&
                !empty($this->getInstallmentB2CEURPublicKey()) &&
                !empty($this->getInstallmentB2CEURPrivateKey())
            );
    }

    public function isB2BInvoiceEligibility(): bool
    {
        return (
            $this->isBasketCurrencyCHF() &&
            !empty($this->getInvoiceB2BCHFPublicKey()) &&
            !empty($this->getInvoiceB2BCHFPrivateKey())
        ) ||
        (
            $this->isBasketCurrencyEUR() &&
            !empty($this->getInvoiceB2BEURPublicKey()) &&
            !empty($this->getInvoiceB2BEURPrivateKey())
        );
    }

    private function getInvoiceB2BEURPublicKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInvoiceB2BEURPublicKey');
        return $key;
    }

    private function getInvoiceB2CEURPrivateKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInvoiceB2CEURPrivateKey');
        return $key;
    }

    private function getInvoiceB2CCHFPublicKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInvoiceB2CCHFPublicKey');
        return $key;
    }

    private function getInvoiceB2CEURPublicKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInvoiceB2CEURPublicKey');
        return $key;
    }

    private function getInvoiceB2BEURPrivateKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInvoiceB2BEURPrivateKey');
        return $key;
    }

    private function getInvoiceB2CCHFPrivateKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInvoiceB2CCHFPrivateKey');
        return $key;
    }

    private function getInvoiceB2BCHFPrivateKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInvoiceB2BCHFPrivateKey');
        return $key;
    }

    private function getInvoiceB2BCHFPublicKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInvoiceB2BCHFPublicKey');
        return $key;
    }

    private function getInstallmentB2CEURPrivateKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInstallmentB2CEURPrivateKey');
        return $key;
    }

    private function getInstallmentB2CEURPublicKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInstallmentB2CEURPublicKey');
        return $key;
    }

    private function getInstallmentB2CCHFPrivateKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInstallmentB2CCHFPrivateKey');
        return $key;
    }

    private function getInstallmentB2CCHFPublicKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInstallmentB2CCHFPublicKey');
        return $key;
    }

    /**
     * @param string $customerType
     * @return string
     */
    public function getInvoicePublicKey(string $customerType = 'B2C'): string
    {
        $result = '';

        if ($customerType === 'B2C' && $this->isB2CInvoiceEligibility()) {
            if ($this->isBasketCurrencyCHF()) {
                $result = $this->getInvoiceB2CCHFPublicKey();
            }
            if ($this->isBasketCurrencyEUR()) {
                $result = $this->getInvoiceB2CEURPublicKey();
            }
        }

        if ($customerType === 'B2B' && $this->isB2BInvoiceEligibility()) {
            if ($this->isBasketCurrencyCHF()) {
                $result = $this->getInvoiceB2BCHFPublicKey();
            }
            if ($this->isBasketCurrencyEUR()) {
                $result = $this->getInvoiceB2BEURPublicKey();
            }
        }

        return $result;
    }

    /**
     * @param string $customerType
     * @return string
     */
    public function getInvoicePrivateKey(string $customerType = 'B2C'): string
    {
        $result = '';

        if ($customerType === 'B2C' && $this->isB2CInvoiceEligibility()) {
            if ($this->isBasketCurrencyCHF()) {
                $result = $this->getInvoiceB2CCHFPrivateKey();
            }
            if ($this->isBasketCurrencyEUR()) {
                $result = $this->getInvoiceB2CEURPrivateKey();
            }
        }

        if ($customerType === 'B2B' && $this->isB2BInvoiceEligibility()) {
            if ($this->isBasketCurrencyCHF()) {
                $result = $this->getInvoiceB2BCHFPrivateKey();
            }
            if ($this->isBasketCurrencyEUR()) {
                $result = $this->getInvoiceB2BEURPrivateKey();
            }
        }

        return $result;
    }

    public function getInstallmentPublicKey(): string
    {
        $result = '';

        if ($this->isB2CInstallmentEligibility()) {
            if ($this->isBasketCurrencyCHF()) {
                $result = $this->getInstallmentB2CCHFPublicKey();
            }
            if ($this->isBasketCurrencyEUR()) {
                $result = $this->getInstallmentB2CEURPublicKey();
            }
        }

        return $result;
    }
    public function getInstallmentPrivateKey(): string
    {
        $result = '';

        if ($this->isB2CInstallmentEligibility()) {
            if ($this->isBasketCurrencyCHF()) {
                $result = $this->getInstallmentB2CCHFPrivateKey();
            }
            if ($this->isBasketCurrencyEUR()) {
                $result = $this->getInstallmentB2CEURPrivateKey();
            }
        }

        return $result;
    }

    /**
     * @param string $customerType
     * @param string $currency
     * @return string
     */
    public function getInvoicePrivateKeyByCustomerTypeAndCurrency(
        string $customerType,
        string $currency
    ): string {
        $key = '';
        if ($customerType === 'B2C' && $currency === 'EUR') {
            $key = $this->getInvoiceB2CEURPrivateKey();
        } elseif ($customerType === 'B2C' && $currency === 'CHF') {
            $key = $this->getInvoiceB2CCHFPrivateKey();
        } elseif ($customerType === 'B2B' && $currency === 'EUR') {
            $key = $this->getInvoiceB2BEURPrivateKey();
        } elseif ($customerType === 'B2B' && $currency === 'CHF') {
            $key = $this->getInvoiceB2BCHFPrivateKey();
        }
        return $key;
    }
    /**
     * @param string $currency
     * @return string
     */
    public function getInstallmentPrivateKeyByCurrency(
        string $currency
    ): string {
        $key = '';
        if ($currency === 'EUR') {
            $key = $this->getInstallmentB2CEURPrivateKey();
        } elseif ($currency === 'CHF') {
            $key = $this->getInstallmentB2CCHFPrivateKey();
        }
        return $key;
    }

    /**
     * @return bool
     */
    private function isBasketCurrencyCHF(): bool
    {
        return $this->getBasketCurrency() === 'CHF';
    }

    /**
     * @return bool
     */
    private function isBasketCurrencyEUR(): bool
    {
        return $this->getBasketCurrency() === 'EUR';
    }

    /**
     * @return string
     */
    private function getBasketCurrency(): string
    {
        return $this->session->getBasket()->getBasketCurrency()->name;
    }

    /**
     * @param string $context
     * @return bool
     */
    private function hasWebhookConfiguration(string $context): bool
    {
        $privateKeysContext = $this->getPrivateKeysWithContext();
        return (!empty($privateKeysContext[$context]));
    }
}
