<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\Unzer\Service\PrePaymentBankAccountService;
use OxidSolutionCatalysts\Unzer\Service\ModuleSettings;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ViewConfig extends ViewConfig_parent
{
    use ServiceContainer;

    /**
     * is this a "Flow"-Theme Compatible Theme?
     * @var bool $isFlowCompatibleTheme
     */
    protected $isFlowCompatibleTheme = null;

    /**
     * is this a "Wave"-Theme Compatible Theme?
     * @var bool $isWaveCompatibleTheme
     */
    protected $isWaveCompatibleTheme = null;

    /** @var ModuleSettings $moduleSettings */
    protected $moduleSettings;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
        $this->moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
    }

    /**
     * Returns System Mode live|sandbox.
     *
     * @return string
     */
    public function getUnzerSystemMode(): string
    {
        return $this->moduleSettings->getSystemMode();
    }

    /**
     * @return bool
     */
    public function isUnzerDebugMode(): bool
    {
        return $this->moduleSettings->isDebugMode();
    }

    /**
     * Returns unzer public key.
     *
     * @return string
     */
    public function getUnzerPubKey(): string
    {
        $paymentId = Registry::getSession()->getBasket()->getPaymentId();
        if ($paymentId === UnzerDefinitions::INVOICE_UNZER_PAYMENT_ID) {
            return $this->moduleSettings->getInvoicePublicKey();
        }

        if ($paymentId === UnzerDefinitions::INSTALLMENT_UNZER_PAYLATER_PAYMENT_ID) {
            return $this->moduleSettings->getInstallmentPublicKey();
        }

        return $this->moduleSettings->getStandardPublicKey();
    }

    /**
     * Returns unzer private key.
     *
     * @return string
     */
    public function getUnzerPrivKey(): string
    {
        $paymentId = Registry::getSession()->getBasket()->getPaymentId();
        if ($paymentId === UnzerDefinitions::INVOICE_UNZER_PAYMENT_ID) {
            return  $this->moduleSettings->getInvoicePrivateKey();
        }
        if ($paymentId === UnzerDefinitions::INSTALLMENT_UNZER_PAYLATER_PAYMENT_ID) {
            return $this->moduleSettings->getInstallmentPrivateKey();
        }

        return $this->moduleSettings->getStandardPrivateKey();
    }

    public function getUnzerB2BPubKey(): string
    {
        $key = $this->moduleSettings->getInvoicePublicKey('B2B');
        return $key;
    }

    public function getUnzerB2BPrivKey(): string
    {
        $key = $this->moduleSettings->getInvoicePrivateKey('B2B');
        return $key;
    }

    public function getUnzerB2CPubKey(): string
    {
        $key = $this->moduleSettings->getInvoicePublicKey();
        return $key;
    }

    public function getUnzerB2CPrivKey(): string
    {
        $key = $this->moduleSettings->getInvoicePrivateKey('B2C');
        return $key;
    }

    /**
     * retrieve additional payment information from session
     *
     * @return string
     */
    public function getSessionPaymentInfo(): string
    {
        /** @var string $addPaymentInfo */
        $addPaymentInfo = Registry::getSession()->getVariable('additionalPaymentInformation') ?? '';
        return $addPaymentInfo;
    }

    /**
     * Returns unzer Installment Rate.
     *
     * @return float
     */
    public function getUnzerInstallmentRate(): float
    {
        return $this->moduleSettings->getInstallmentRate();
    }

    /**
     * checks if jQuery should be imported
     *
     * @return bool
     */
    public function useModuleJQueryInFrontend(): bool
    {
        return $this->moduleSettings->useModuleJQueryInFrontend();
    }

    /**
     * Template variable getter. Check if active theme is a Flow Theme Compatible Theme
     *
     * @return boolean
     */
    public function isFlowCompatibleTheme() //phpcs:ignore no return type because extended class method doesn't have it
    {
        if (is_null($this->isFlowCompatibleTheme)) {
            $this->isFlowCompatibleTheme = $this->isCompatibleTheme('flow');
        }
        return $this->isFlowCompatibleTheme;
    }

    /**
     * Template variable getter. Check if active theme is a Wave Theme Compatible Theme
     *
     * @return boolean
     */
    public function isWaveCompatibleTheme() //phpcs:ignore no return type because extended class method doesn't have it
    {
        if (is_null($this->isWaveCompatibleTheme)) {
            $this->isWaveCompatibleTheme = $this->isCompatibleTheme('wave');
        }
        return $this->isWaveCompatibleTheme;
    }

    /**
     * Template variable getter. Check if is a ??? Theme Compatible Theme
     *
     * @param string|null $themeId
     *
     * @return boolean
     */
    public function isCompatibleTheme($themeId = null)
    {
        $result = false;
        if ($themeId) {
            $theme = oxNew(\OxidEsales\Eshop\Core\Theme::class);
            $theme->load($theme->getActiveThemeId());
            // check active theme or parent theme
            if (
                $theme->getActiveThemeId() == $themeId ||
                $theme->getInfo('parentTheme') == $themeId
            ) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function isB2CInvoiceEligibility(): bool
    {
        return $this->moduleSettings->isB2CInvoiceEligibility();
    }

    /**
     * @return bool
     */
    public function isB2BInvoiceEligibility(): bool
    {
        return $this->moduleSettings->isB2BInvoiceEligibility();
    }

    public function getBasketCurrencyName(): string
    {
        $currencyName = '';
        $basket = Registry::getSession()->getBasket();
        $currencyName = $basket->getBasketCurrency()->name;
        return $currencyName;
    }

    public function getPrePaymentIban(string $unzerOrderNumber): ?string
    {
        $prePaymentBankAccountService = $this->getPrePaymentBankAccountService();

        return $prePaymentBankAccountService->getIban($unzerOrderNumber);
    }

    public function getPrePaymentBic(string $unzerOrderNumber): ?string
    {
        $prePaymentBankAccountService = $this->getPrePaymentBankAccountService();

        return $prePaymentBankAccountService->getBic($unzerOrderNumber);
    }

    public function getPrePaymentHolder(string $unzerOrderNumber): ?string
    {
        $prePaymentBankAccountService = $this->getPrePaymentBankAccountService();

        return $prePaymentBankAccountService->getHolder($unzerOrderNumber);
    }

    public function getPrePaymentDescriptor(string $unzerOrderNumber): ?string
    {
        $prePaymentBankAccountService = $this->getPrePaymentBankAccountService();

        return $prePaymentBankAccountService->getDescriptor($unzerOrderNumber);
    }

    private function getPrePaymentBankAccountService(): PrePaymentBankAccountService
    {
        return $this->getServiceFromContainer(PrePaymentBankAccountService::class);
    }
}
