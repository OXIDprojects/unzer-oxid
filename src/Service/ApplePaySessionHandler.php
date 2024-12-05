<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Service;

use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use Psr\Log\LoggerInterface;
use UnzerSDK\Adapter\ApplepayAdapter;
use UnzerSDK\Resources\ExternalResources\ApplepaySession;

class ApplePaySessionHandler
{
    use ServiceContainer;

    private ApplepaySession $session;
    private ApplepayAdapter $adapter;
    private ModuleSettings $moduleSettings;
    private DebugHandler $logger;

    /**
     * @throws \OxidEsales\EshopCommunity\Core\Exception\FileException
     */
    public function __construct(ModuleSettings $moduleSettings, DebugHandler $logger)
    {
        $this->moduleSettings = $moduleSettings;
        $this->initialize();
        $this->logger = $logger;
    }

    /**
     * @throws \OxidEsales\EshopCommunity\Core\Exception\FileException
     */
    private function initialize(): void
    {
        $domainName = rtrim(
            str_replace(
                ['http://', 'https://'],
                '',
                Registry::getConfig()->getSslShopUrl()
            ),
            '/'
        );
        // if we have no credentials we could not initiate
        if ($this->moduleSettings->isApplePayEligibility()) {
            /** @var string $applePatLabel */
            $applePatLabel = $this->moduleSettings->getApplePayLabel();
            $this->session = new ApplepaySession(
                $this->moduleSettings->getApplePayMerchantIdentifier(),
                $applePatLabel,
                $domainName
            );
            $this->adapter = new ApplepayAdapter();
            $this->adapter->init(
                $this->moduleSettings->getApplePayMerchantCertFilePath(),
                $this->moduleSettings->getApplePayMerchantCertKeyFilePath()
            );
        }
    }

    public function validateMerchant(string $validationUrl): ?array
    {
        // if we have no credentials we could not validate Merchant
        if (!$this->moduleSettings->isApplePayEligibility()) {
            return null;
        }

        try {
            /** @var string $validApplePayMerch */
            $validApplePayMerch = $this->adapter->validateApplePayMerchant($validationUrl, $this->session);
            /** @var array $jsonDecoded */
            $jsonDecoded = json_decode(
                $validApplePayMerch,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            return $jsonDecoded;
        } catch (\Throwable $e) {
            $this->logger->log($e->getMessage());
            return null;
        }
    }
}
