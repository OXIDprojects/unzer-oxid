<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Service\ModuleConfiguration;

use OxidSolutionCatalysts\Unzer\Service\ModuleSettings;

class AppleMerchantCertificate
{
    private ModuleSettings $moduleSettings;

    public function __construct(ModuleSettings $moduleSettings)
    {
        $this->moduleSettings = $moduleSettings;
    }
    public function saveCertificate(?string $certificate): bool
    {
        if (is_null($certificate)) {
            return false;
        }

        return (bool) file_put_contents(
            $this->moduleSettings->getApplePayMerchantCertFilePath(),
            $certificate
        );
    }
    public function saveCertificateKey(?string $certificateKey): bool
    {
        if (is_null($certificateKey)) {
            return false;
        }

        return (bool) file_put_contents(
            $this->moduleSettings->getApplePayMerchantCertKeyFilePath(),
            $certificateKey
        );
    }
}
