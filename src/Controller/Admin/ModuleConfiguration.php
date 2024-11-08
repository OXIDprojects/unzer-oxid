<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Controller\Admin;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\Unzer\Exception\UnzerException;
use OxidSolutionCatalysts\Unzer\Module;
use OxidSolutionCatalysts\Unzer\Service\ApiClient;
use OxidSolutionCatalysts\Unzer\Service\ModuleConfiguration\ApplePaymentProcessingCertificate;
use OxidSolutionCatalysts\Unzer\Service\ModuleSettings;
use OxidSolutionCatalysts\Unzer\Service\Translator;
use OxidSolutionCatalysts\Unzer\Service\UnzerWebhooks;
use OxidSolutionCatalysts\Unzer\Traits\Request;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use Throwable;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ModuleConfiguration extends ModuleConfiguration_parent
{
    use ServiceContainer;
    use Request;

    protected Translator $translator;
    protected ModuleSettings $moduleSettings;
    protected UnzerWebhooks $unzerWebhooks;
    protected string $_sModuleId; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    public function __construct()
    {
        parent::__construct();
        $this->translator = $this->getServiceFromContainer(Translator::class);
        $this->moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $this->unzerWebhooks = $this->getServiceFromContainer(UnzerWebhooks::class);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function render()
    {
        $template = parent::render();

        if ($this->_sModuleId == Module::MODULE_ID) {
            try {
                $this->_aViewData["webhookConfiguration"] = $this->moduleSettings->getWebhookConfiguration();
                $this->_aViewData['applePayMC'] = $this->moduleSettings->getApplePayMerchantCapabilities();
                $this->_aViewData['applePayNetworks'] = $this->moduleSettings->getApplePayNetworks();
                $this->_aViewData['applePayMerchantCert'] = $this->moduleSettings->getApplePayMerchantCert();
                $this->_aViewData['applePayMerchantCertKey'] = $this->moduleSettings->getApplePayMerchantCertKey();
                $this->_aViewData['applePayPaymentProcessingCert'] = $this->moduleSettings->getApplePayPaymentCert();

                $this->_aViewData['applePayPaymentProcessingCertKey'] =
                    $this->moduleSettings->getApplePayPaymentPrivateKey();

                $this->_aViewData['systemMode'] = $this->moduleSettings->getSystemMode();
            } catch (Throwable $loggerException) {
                Registry::getUtilsView()->addErrorToDisplay(
                    $this->translator->translateCode(
                        (string)$loggerException->getCode(),
                        $loggerException->getMessage()
                    )
                );
            }
        }
        return $template;
    }

    public function registerWebhooks(): void
    {
        try {
            $this->unzerWebhooks->setPrivateKeys(
                $this->moduleSettings->getPrivateKeysWithContext()
            );
            $this->unzerWebhooks->registerWebhookConfiguration();
        } catch (Throwable $loggerException) {
            Registry::getUtilsView()->addErrorToDisplay(
                $loggerException->getMessage()
            );
        }
    }

    /**
     * @throws UnzerApiException
     */
    public function unregisterWebhooks(): void
    {
        try {
            $this->unzerWebhooks->setPrivateKeys(
                $this->moduleSettings->getPrivateKeysWithContext()
            );
            $this->unzerWebhooks->unregisterWebhookConfiguration();
        } catch (Throwable $loggerException) {
            Registry::getUtilsView()->addErrorToDisplay(
                $loggerException->getMessage()
            );
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function transferApplePayPaymentProcessingData(): void
    {
        $systemMode = $this->moduleSettings->getSystemMode();
        $keyReqName = $systemMode . '-' . 'applePayPaymentProcessingCertKey';
        $key = $this->getUnzerStringRequestParameter($keyReqName);
        $certReqName = $systemMode . '-' . 'applePayPaymentProcessingCert';
        $cert = $this->getUnzerStringRequestParameter($certReqName);
        $errorMessage = !$key || !$cert ? 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_SET_CERT' : null;

        $apiClient = $this->getServiceFromContainer(ApiClient::class);
        $applePayKeyId = null;
        $applePayCertId = null;

        // save Apple Pay processing cert and key
        if (is_null($errorMessage)) {
            $appleCertService = $this->getServiceFromContainer(
                ApplePaymentProcessingCertificate::class
            );
            $appleCertService->saveCertificate($cert);
            $appleCertService->saveCertificateKey($key);
        }

        // Upload Key
        if (is_null($errorMessage)) {
            try {
                $response = $apiClient->uploadApplePayPaymentKey($key);
                if (!$response || $response->getStatusCode() !== 201) {
                    $errorMessage = 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_SET_KEY';
                } else {
                    /** @var array{'id': string} $responseBody */
                    $responseBody = json_decode($response->getBody()->__toString(), true);
                    $applePayKeyId = $responseBody['id'];
                }
            } catch (Throwable $loggerException) {
                $errorMessage = 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_SET_KEY';
            }
        }

        // Upload Certificate
        if ($applePayKeyId && is_null($errorMessage)) {
            try {
                $response = $apiClient->uploadApplePayPaymentCertificate($cert, $applePayKeyId);
                if (!$response || $response->getStatusCode() !== 201) {
                    $errorMessage = 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_SET_CERT';
                } else {
                    /** @var array{'id': string} $responseBody */
                    $responseBody = json_decode($response->getBody()->__toString(), true);
                    $applePayCertId = $responseBody['id'];
                }
            } catch (Throwable $loggerException) {
                $errorMessage = 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_SET_CERT';
            }
        }

        // Activate Certificate
        if ($applePayKeyId && $applePayCertId && is_null($errorMessage)) {
            try {
                $response = $apiClient->activateApplePayPaymentCertificate($applePayCertId);
                if (!$response || $response->getStatusCode() !== 200) {
                    $errorMessage = 'OSCUNZER_ERROR_ACTIVATE_APPLEPAY_PAYMENT_CERT';
                } else {
                    $this->moduleSettings->saveApplePayPaymentKeyId($applePayKeyId);
                    $this->moduleSettings->saveApplePayPaymentCertificateId($applePayCertId);
                }
            } catch (Throwable $loggerException) {
                $errorMessage = 'OSCUNZER_ERROR_ACTIVATE_APPLEPAY_PAYMENT_CERT';
            }
        }

        if ($errorMessage) {
            Registry::getUtilsView()->addErrorToDisplay(
                oxNew(
                    UnzerException::class,
                    $this->translator->translate(
                        $errorMessage
                    )
                )
            );
        }
    }

    public function getApplePayPaymentProcessingKeyExists(): bool
    {
        $keyExists = false;
        $keyId = $this->moduleSettings->getApplePayPaymentKeyId();
        if ($this->moduleSettings->getApplePayMerchantCertKey() && $keyId) {
            try {
                $response = $this->getServiceFromContainer(ApiClient::class)
                    ->requestApplePayPaymentCert($keyId);
                if (!$response) {
                    $this->addErrorTransmittingKey();
                    return false;
                }
                $keyExists = $response->getStatusCode() === 200;
            } catch (GuzzleException $guzzleException) {
                $this->addErrorTransmittingKey();
            }
        }
        return $keyExists;
    }

    /**
     * @throws GuzzleException
     */
    public function getApplePayPaymentProcessingCertExists(): bool
    {
        $certExists = false;
        $certId = $this->moduleSettings->getApplePayPaymentCertificateId();
        if ($this->moduleSettings->getApplePayMerchantCert() && $certId) {
            try {
                $response = $this->getServiceFromContainer(ApiClient::class)
                    ->requestApplePayPaymentCert($certId);
                if (!$response) {
                    $this->addErrorTransmittingCertificate();
                    return false;
                }
                $certExists = $response->getStatusCode() === 200;
            } catch (GuzzleException $guzzleException) {
                $this->addErrorTransmittingCertificate();
            }
        }
        return $certExists;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @throws \OxidEsales\EshopCommunity\Core\Exception\FileException
     */
    public function saveConfVars(): void
    {
        $request = Registry::getRequest();
        if (
            $request->getRequestEscapedParameter('oxid') &&
            $request->getRequestEscapedParameter('oxid') === 'osc-unzer'
        ) {
            $systemMode = $this->moduleSettings->getSystemMode();
            $applePayMC = $request->getRequestEscapedParameter('applePayMC');
            if (is_array($applePayMC)) {
                $this->moduleSettings->saveApplePayMerchantCapabilities($applePayMC);
            }
            $applePayNetworks = $request->getRequestEscapedParameter('applePayNetworks');
            if (is_array($applePayNetworks)) {
                $this->moduleSettings->saveApplePayNetworks($applePayNetworks);
            }
            $certConfigKey = $this->moduleSettings->getSystemMode() . '-' . 'applePayMerchantCert';
            $applePayMerchantCert = $request->getRequestEscapedParameter($certConfigKey);
            file_put_contents(
                $this->moduleSettings->getApplePayMerchantCertFilePath(),
                $applePayMerchantCert
            );
            $keyConfigKey = $this->moduleSettings->getSystemMode() . '-' . 'applePayMerchantCertKey';
            $applePayMerchCertKey = $request->getRequestEscapedParameter($keyConfigKey);
            file_put_contents(
                $this->moduleSettings->getApplePayMerchantCertKeyFilePath(),
                $applePayMerchCertKey
            );

            $appleCerService = $this->getServiceFromContainer(
                ApplePaymentProcessingCertificate::class
            );

            $appleCerService->saveCertificate(
                $this->getUnzerStringRequestEscapedParameter(
                    $systemMode . '-' . 'applePayPaymentProcessingCert'
                )
            );

            $appleCerService->saveCertificateKey(
                $this->getUnzerStringRequestEscapedParameter(
                    $systemMode . '-' . 'applePayPaymentProcessingCertKey'
                )
            );
        }

        $this->moduleSettings->saveWebhookConfiguration([]);
        $this->registerWebhooks();

        parent::saveConfVars();
    }

    private function addErrorTransmittingCertificate(): void
    {
        Registry::getUtilsView()->addErrorToDisplay(
            oxNew(
                UnzerException::class,
                $this->translator->translate(
                    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_GET_CERT'
                )
            )
        );
    }

    private function addErrorTransmittingKey(): void
    {
        Registry::getUtilsView()->addErrorToDisplay(
            oxNew(
                UnzerException::class,
                $this->translator->translate(
                    'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_GET_KEY'
                )
            )
        );
    }
}
