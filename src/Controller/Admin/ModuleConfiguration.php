<?php

namespace OxidSolutionCatalysts\Unzer\Controller\Admin;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Exception\FileException;
use OxidSolutionCatalysts\Unzer\Service\ModuleConfiguration\AppleMerchantCertificate;
use OxidSolutionCatalysts\Unzer\Traits\Request;
use OxidSolutionCatalysts\Unzer\Service\ModuleConfiguration\ApplePaymentProcessingCertificate;
use OxidSolutionCatalysts\Unzer\Exception\UnzerException;
use OxidSolutionCatalysts\Unzer\Module;
use OxidSolutionCatalysts\Unzer\Service\ApiClient;
use OxidSolutionCatalysts\Unzer\Service\ModuleSettings;
use OxidSolutionCatalysts\Unzer\Service\Translator;
use OxidSolutionCatalysts\Unzer\Service\UnzerWebhooks;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use Throwable;

/**
 * Order class wrapper for Unzer module
 *
 * TODO: Fix all the suppressed warnings
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ModuleConfiguration extends ModuleConfiguration_parent
{
    use ServiceContainer;
    use Request;

    protected Translator $translator;
    protected ModuleSettings $moduleSettings;
    protected UnzerWebhooks $unzerWebhooks;

    protected string $_sModuleId; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

    private string $errorPaymentMissing = 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_MISSING';
    private bool $isUpdate;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
        $this->translator = $this->getServiceFromContainer(Translator::class);
        $this->moduleSettings = $this->getServiceFromContainer(ModuleSettings::class);
        $this->unzerWebhooks = $this->getServiceFromContainer(UnzerWebhooks::class);
    }

    /**
     * @inheritDoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function render()
    {
        $result = parent::render();

        if ($this->_sModuleId === Module::MODULE_ID) {
            try {
                $this->_aViewData["webhookConfiguration"] =
                    $this->moduleSettings->getWebhookConfiguration();
                $this->_aViewData['applePayMC'] =
                    $this->moduleSettings->getApplePayMerchantCapabilities();
                $this->_aViewData['applePayNetworks'] =
                    $this->moduleSettings->getApplePayNetworks();
                $this->_aViewData['applePayMerchantCert'] =
                    $this->moduleSettings->getApplePayMerchantCert();
                $this->_aViewData['applePayMerchantCertKey'] =
                    $this->moduleSettings->getApplePayMerchantCertKey();
                $this->_aViewData['applePayPaymentProcessingCert'] =
                    $this->moduleSettings->getApplePayPaymentCert();
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
        return $result;
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

        $key = $this->getUnzerStringRequestParameter($systemMode . '-' . 'applePayPaymentProcessingCertKey');
        $cert = $this->getUnzerStringRequestParameter($systemMode . '-' . 'applePayPaymentProcessingCert');
        $errorMessage = $key && $cert ? null : $this->errorPaymentMissing;

        $this->saveMerchantCert($systemMode);
        $this->saveMerchantKey($systemMode);
        $this->saveMerchantIdentifier($systemMode);
        $this->savePaymentCert($systemMode);
        $this->savePaymentKey($systemMode);

        $apiClient = $this->getServiceFromContainer(ApiClient::class);
        $applePayKeyId = null;
        $applePayCertId = null;

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
                    $this->moduleSettings->saveApplePayPaymentCertificateId($applePayCertId);
                    $this->moduleSettings->saveApplePayPaymentKeyId($applePayKeyId);
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

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \OxidEsales\EshopCommunity\Core\Exception\FileException
     */
    public function getApplePayPaymentProcessingKeyExists(): bool
    {
        $keyId = $this->moduleSettings->getApplePayPaymentKeyId();
        if ($this->moduleSettings->getApplePayMerchantCertKey() && $keyId) {
            try {
                $response = $this->getServiceFromContainer(ApiClient::class)
                    ->requestApplePayPaymentKey($keyId);
                if (!$response) {
                    return false;
                }
                return $response->getStatusCode() === 200;
            } catch (GuzzleException | JsonException $guzzleException) {
                $this->addErrorToDisplay('OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_GET_KEY');
            }
        }
        return false;
    }

    /**
     * @throws GuzzleException
     * @throws FileException
     */
    public function getApplePayPaymentProcessingCertExists(): bool
    {
        $certId = $this->moduleSettings->getApplePayPaymentCertificateId();
        if ($this->moduleSettings->getApplePayMerchantCert() && $certId) {
            try {
                $response = $this->getServiceFromContainer(ApiClient::class)
                    ->requestApplePayPaymentCert($certId);

                if (!$response) {
                    $this->addErrorToDisplay('OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_GET_CERT');
                    return false;
                }

                return $response->getStatusCode() === 200;
            } catch (GuzzleException | JsonException $guzzleException) {
                $this->addErrorToDisplay('OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_GET_CERT');
                return false;
            }
        }
        return false;
    }

    /**
     * @return void
     * @throws FileException
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function saveConfVars()
    {
        $moduleId = $this->getUnzerStringRequestEscapedParameter('oxid');
        if ($moduleId === Module::MODULE_ID) {
            $systemMode = $this->moduleSettings->getSystemMode();

            $applePayMC = $this->getUnzerArrayRequestParameter('applePayMC');
            $this->moduleSettings->saveApplePayMerchantCapabilities($applePayMC);
            $applePayNetworks = $this->getUnzerArrayRequestParameter('applePayNetworks');

            $this->moduleSettings->saveApplePayNetworks($applePayNetworks);

            $this->saveMerchantCert($systemMode);
            $this->saveMerchantKey($systemMode);
            $this->saveMerchantIdentifier($systemMode);
            $this->savePaymentCert($systemMode);
            $this->savePaymentKey($systemMode);

            $this->moduleSettings->saveWebhookConfiguration([]);
            $this->registerWebhooks();
        }
        parent::saveConfVars();
    }

    /**
     * @throws \OxidEsales\EshopCommunity\Core\Exception\FileException
     */
    private function saveMerchantKey(string $systemMode): void
    {
        $errorIds = [
            'onEmpty' => 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_KEY_EMPTY',
            'onShort' => 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_KEY_TOO_SHORT'
        ];

        $newValue = $this->getUnzerStringRequestEscapedParameter(
            $systemMode . '-' . 'applePayMerchantCertKey'
        );

        $oldValue = $this->moduleSettings->getApplePayMerchantCertKey();

        $this->setIsUpdate($oldValue, $newValue);

        $isValidMerchantKey = $this->validateCredentialsForSaving($newValue, $errorIds);

        if ($isValidMerchantKey) {
            $service = $this->getServiceFromContainer(AppleMerchantCertificate::class);
            $service->saveCertificateKey($newValue);
        }
    }

    private function saveMerchantIdentifier(string $systemMode): void
    {
        $errorIds = [
            'onEmpty' => 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_ID_EMPTY',
            'onShort' => 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_ID_TOO_SHORT'
        ];

        $newValue = $this->getUnzerStringRequestEscapedParameter(
            $systemMode . '-' . 'applepay_merchant_identifier'
        );

        $oldValue = $this->moduleSettings->getApplePayMerchantIdentifier();

        $this->setIsUpdate($oldValue, $newValue);
        $isValid = $this->validateCredentialsForSaving($newValue, $errorIds);

        if ($isValid) {
            $this->moduleSettings->setApplePayMerchantIdentifier($newValue);
        }
    }

    private function saveMerchantCert(string $systemMode): void
    {
        $errorIds = [
            'onEmpty' => 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_CERT_EMPTY',
            'onShort' => 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_MERCHANT_CERT_TOO_SHORT'
        ];

        $newValue = $this->getUnzerStringRequestEscapedParameter(
            $systemMode . '-' . 'applePayMerchantCert'
        );

        $oldValue = $this->moduleSettings->getApplePayMerchantCert();

        $this->setIsUpdate($oldValue, $newValue);

        $isValidMerchantCert = $this->validateCredentialsForSaving($newValue, $errorIds);

        if ($isValidMerchantCert) {
            $service = $this->getServiceFromContainer(AppleMerchantCertificate::class);
            $service->saveCertificate($newValue);
        }
    }

    /**
     * @throws \OxidEsales\EshopCommunity\Core\Exception\FileException
     */
    private function savePaymentKey(string $systemMode): void
    {
        $errorIds = [
            'onEmpty' => 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_KEY_EMPTY',
            'onShort' => 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_KEY_TOO_SHORT'
        ];

        $newValue = $this->getUnzerStringRequestEscapedParameter(
            $systemMode . '-' . 'applePayPaymentProcessingCertKey'
        );

        $oldValue = $this->moduleSettings->getApplePayPaymentPrivateKey();

        $this->setIsUpdate($oldValue, $newValue);
        $isValidPaymentKey = $this->validateCredentialsForSaving($newValue, $errorIds);

        if ($isValidPaymentKey) {
            $service = $this->getServiceFromContainer(ApplePaymentProcessingCertificate::class);
            $service->saveCertificateKey($newValue);
        }
    }

    /**
     * @throws \OxidEsales\EshopCommunity\Core\Exception\FileException
     */
    private function savePaymentCert(string $systemMode): void
    {
        $errorIds = [
            'onEmpty' => 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_CERT_EMPTY',
            'onShort' => 'OSCUNZER_ERROR_TRANSMITTING_APPLEPAY_PAYMENT_CERT_TOO_SHORT'
        ];

        $newValue = $this->getUnzerStringRequestEscapedParameter(
            $systemMode . '-' . 'applePayPaymentProcessingCert'
        );

        $oldValue = $this->moduleSettings->getApplePayPaymentCert();

        $this->setIsUpdate($oldValue, $newValue);

        $isValidPaymentKey = $this->validateCredentialsForSaving($newValue, $errorIds);

        if ($isValidPaymentKey) {
            $service = $this->getServiceFromContainer(ApplePaymentProcessingCertificate::class);
            $service->saveCertificate($newValue);
        }
    }

    private function validateCredentialsForSaving(?string $string, array $errors): bool
    {
        if ($string === null || strlen($string) === 0) {
            if ($this->getIsUpdate()) {
                $this->addErrorToDisplay($errors['onEmpty']);
            }
            return true;
        }

        if (strlen($string) > 1 && strlen($string) < 32) {
            $this->addErrorToDisplay($errors['onShort']);
            return false;
        }

        return true;
    }

    private function addErrorToDisplay(string $translateId): void
    {
        Registry::getUtilsView()->addErrorToDisplay(
            oxNew(
                UnzerException::class,
                $this->translator->translate(
                    $translateId
                )
            )
        );
    }

    private function setIsUpdate(?string $oldValue, ?string $newValue): bool
    {
        $this->isUpdate = ($oldValue !== $newValue) && !empty($newValue);
        return $this->isUpdate;
    }

    private function getIsUpdate(): bool
    {
        return $this->isUpdate;
    }
}
