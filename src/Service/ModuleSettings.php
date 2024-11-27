<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Service;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\EshopCommunity\Application\Model\User;
use OxidEsales\EshopCommunity\Core\Exception\FileException;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleConfigurationDaoBridgeInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidEsales\Facts\Facts;
use OxidSolutionCatalysts\Unzer\Module;
use Exception;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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

    /** @var ModuleSettingBridgeInterface $moduleSettingBridge */
    private $moduleSettingBridge;

    /** @var ModuleConfigurationDaoBridgeInterface  */
    private $moduleInfoBridge;

    /** @var Session $session */
    private $session;

    /** @var Config $config */
    private $config;

    /**
     * @param ModuleSettingBridgeInterface $moduleSettingBridge
     * @param ModuleConfigurationDaoBridgeInterface $moduleInfoBridge
     * @param \OxidEsales\Eshop\Core\Session $session
     * @param \OxidEsales\Eshop\Core\Config $config
     */
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

    /**
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return $this->getSettingValue('UnzerDebug') === true;
    }

    /**
     * @return bool
     */
    public function isSandboxMode(): bool
    {
        return $this->getSystemMode() === self::SYSTEM_MODE_SANDBOX;
    }

    /**
     * @return string
     */
    public function getSystemMode(): string
    {
        if ($this->getSettingValue('UnzerSystemMode')) {
            return self::SYSTEM_MODE_PRODUCTION;
        }
        return self::SYSTEM_MODE_SANDBOX;
    }

    /**
     * @param string $systemMode
     * @return void
     */
    public function setSystemMode(string $systemMode): void
    {
        $this->saveSetting('UnzerSystemMode', $systemMode);
    }

    /**
     * @return float
     */
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

    /**
     * @return bool
     */
    public function useModuleJQueryInFrontend(): bool
    {
        /** @var bool $unzerJQuery */
        $unzerJQuery = $this->getSettingValue('UnzerjQuery');
        return $unzerJQuery;
    }

    /**
     * @param string $paymentMethod
     * @return string
     */
    public function getPaymentProcedureSetting(string $paymentMethod): string
    {
        if (
            $paymentMethod === 'installment-secured'
            || $paymentMethod === 'paylater-installment'
            || $this->getSettingValue('UnzerOption_oscunzer_' . $paymentMethod)
        ) {
            return self::PAYMENT_AUTHORIZE;
        }
        return self::PAYMENT_CHARGE;
    }

    /**
     * @return string
     */
    public function getModuleVersion(): string
    {
        return $this->moduleInfoBridge->get(Module::MODULE_ID)->getVersion();
    }

    /**
     * @return string
     */
    public function getGitHubName(): string
    {
        return Module::GITHUB_NAME;
    }

    /**
     * @return bool
     */
    public function isApplePayEligibility(): bool
    {
        return (
            $this->getApplePayMerchantCert() &&
            $this->getApplePayMerchantCertKey() &&
            $this->hasWebhookConfiguration('shop')
        );
    }

    /**
     * @return mixed
     */
    public function getApplePayLabel()
    {
        return $this->getSettingValue('applepay_label') ?:
            $this->config->getActiveShop()->getFieldData('oxcompany') ?: 'default_label';
    }

    /**
     * @return array associative array including capability key and active status
     */
    public function getApplePayMerchantCapabilities(): array
    {
        /** @var array $applepayMerchCaps */
        $applepayMerchCaps = $this->getSettingValue('applepay_merchant_capabilities');
        return array_merge(
            self::APPLE_PAY_MERCHANT_CAPABILITIES,
            $applepayMerchCaps
        );
    }

    /**
     * @return array array of active capability keys
     */
    public function getActiveApplePayMerchantCapabilities(): array
    {
        return array_keys(array_filter(
            $this->getApplePayMerchantCapabilities(),
            [$this, 'isActiveSetting']
        ));
    }

    /**
     * @return array associative array including network key and active status
     */
    public function getApplePayNetworks(): array
    {
        /** @var array $applepayNetworks */
        $applepayNetworks = $this->getSettingValue('applepay_networks');
        return array_merge(
            self::APPLE_PAY_NETWORKS,
            $applepayNetworks
        );
    }

    /**
     * @return array array of active network keys
     */
    public function getActiveApplePayNetworks(): array
    {
        return array_keys(array_filter(
            $this->getApplePayNetworks(),
            [$this, 'isActiveSetting']
        ));
    }

    /**
     * @return string
     */
    public function getApplePayMerchantIdentifier(): string
    {
        /** @var string $applepayMerchId */
        $applepayMerchId =
            $this->getSettingValue($this->getSystemMode() . '-applepay_merchant_identifier');
        return $applepayMerchId;
    }

    /**
     * @return string
     */
    public function getApplePayMerchantCert(): string
    {
        try {
            $path = $this->getApplePayMerchantCertFilePath();

            if (file_exists($path)) {
                /** @var string $fileContest */
                $fileContest = file_get_contents($path);
                return $fileContest;
            }
        } catch (FileException $e) {
            return '';
        }

        return '';
    }

    /**
     * @return string
     * @throws FileException
     */
    public function getApplePayPaymentCert(): string
    {
        try {
            $path = $this->getApplePayPaymentCertFilePath();
            if (file_exists($path)) {
                /** @var string $fileContest */
                $fileContest = file_get_contents($path);
                return $fileContest;
            }
        } catch (FileException $e) {
            return '';
        }

        return '';
    }

    /**
     * @return string
     */
    public function getApplePayPaymentPrivateKey(): string
    {
        try {
            $path = $this->getApplePayPaymentPrivateKeyFilePath();
            if (file_exists($path)) {
                /** @var string $fileContest */
                $fileContest = file_get_contents($path);
                return $fileContest;
            }
        } catch (FileException $e) {
            return '';
        }

        return '';
    }

    /**
     * @return string
     */
    public function getApplePayMerchantCertKey(): string
    {
        try {
            $path = $this->getApplePayMerchantCertKeyFilePath();
            if (file_exists($path)) {
                /** @var string $fileContest */
                $fileContest = file_get_contents($path);
                return $fileContest;
            }
        } catch (FileException $e) {
            return '';
        }

        return '';
    }

    /**
     * @param array $capabilities
     * @return void
     */
    public function saveApplePayMerchantCapabilities(array $capabilities): void
    {
        $this->saveSetting('applepay_merchant_capabilities', $capabilities);
    }

    /**
     * @return string
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

    /**
     * @return string
     * @throws FileException
     */
    public function getApplePayMerchantCertKeyFilePath(): string
    {
        return $this->getFilesPath()
            . '/.applepay_merchant_cert_key.'
            . $this->getSystemMode()
            . '.'
            . $this->config->getShopId();
    }

    /**
     * @return string
     * @throws FileException
     */
    public function getApplePayPaymentCertFilePath(): string
    {
        return $this->getFilesPath()
            . '/.applepay_payment_cert.'
            . $this->getSystemMode()
            . '.'
            . $this->config->getShopId();
    }

    /**
     * @return string
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

    /**
     * @return string
     * @throws FileException|Exception
     */
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

    /**
     * @param string $paymentKeyId
     * @return void
     */
    public function saveApplePayPaymentKeyId(string $paymentKeyId): void
    {
        $this->saveSetting($this->getSystemMode() . 'ApplePayPaymentKeyId', $paymentKeyId);
    }

    /**
     * @param string $certificateId
     * @return void
     */
    public function saveApplePayPaymentCertificateId(string $certificateId): void
    {
        $this->saveSetting($this->getSystemMode() . 'ApplePayPaymentCertificateId', $certificateId);
    }

    /**
     * @return string
     */
    public function getApplePayPaymentKeyId(): string
    {
        /** @var string $paymentKeyId */
        $paymentKeyId = $this->getSettingValue($this->getSystemMode() . 'ApplePayPaymentKeyId');
        return $paymentKeyId;
    }

    /**
     * @return string
     */
    public function getApplePayPaymentCertificateId(): string
    {
        /** @var string $certificateId */
        $certificateId = $this->getSettingValue($this->getSystemMode() . 'ApplePayPaymentCertificateId');
        return $certificateId;
    }

    /**
     * @param array $networks
     * @return void
     */
    public function saveApplePayNetworks(array $networks): void
    {
        $this->saveSetting('applepay_networks', $networks);
    }

    /**
     * @param array $webhookConfig
     * @return void
     */
    public function saveWebhookConfiguration(array $webhookConfig): void
    {
        $this->saveSetting('webhookConfiguration', $webhookConfig);
    }

    /**
     * @return array
     */
    public function getWebhookConfiguration(): array
    {
        /** @var array $webhookConfig */
        $webhookConfig = $this->getSettingValue('webhookConfiguration');
        return $webhookConfig;
    }

    /**
     * @return array
     */
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

    /**
     * @param string $name
     * @param bool|int|string|array $setting
     * @return void
     */
    private function saveSetting(string $name, $setting): void
    {
        $this->moduleSettingBridge->save($name, $setting, Module::MODULE_ID);
    }

    /**
     * @return mixed
     */
    private function getSettingValue(string $key)
    {
        return $this->moduleSettingBridge->get($key, Module::MODULE_ID);
    }

    /**
     * Intended to be used as callback function
     *
     * @param bool|int|string $active
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function isActiveSetting($active): bool
    {
        return $active === '1';
    }

    /**
     * @return bool
     */
    public function isStandardEligibility(): bool
    {
        return (
            $this->getStandardPrivateKey() &&
            $this->getStandardPublicKey() &&
            $this->hasWebhookConfiguration('shop')
        );
    }

    /**
     * @return bool
     */
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

    public function isB2BEligibility(): bool
    {
        $user = oxNew(User::class)->getUser();
        $userCompany = $user->getFieldData('oxcompany');

        return !empty($userCompany);
    }

    /**
     * @return bool
     */
    public function isInstallmentEligibility(): bool
    {
        return (
            !$this->isB2BEligibility() && //B2C Customers only
            ($this->isB2CInstallmentEligibility() &&
                $this->hasWebhookConfiguration('b2ceurinstallment') &&
                $this->hasWebhookConfiguration('b2cchfinstallment'))
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
    /**
     * @return bool
     */
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

    /**
     * @return bool
     */
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

    private function getInvoiceB2CEURPrivateKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInvoiceB2CEURPrivateKey');
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

    private function getInvoiceB2BEURPublicKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInvoiceB2BEURPublicKey');
        return $key;
    }

    private function getInvoiceB2CCHFPrivateKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInvoiceB2CCHFPrivateKey');
        return $key;
    }

    private function getInvoiceB2CCHFPublicKey(): string
    {
        /** @var string $key */
        $key = $this->getSettingValue($this->getSystemMode() . '-UnzerPayLaterInvoiceB2CCHFPublicKey');
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
        $customerType = $customerType === 'B2B' ? 'B2B' : 'B2C';
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
