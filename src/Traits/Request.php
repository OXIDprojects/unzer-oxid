<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Traits;

use OxidEsales\EshopCommunity\Core\Registry;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Card as UnzerSDKPaymentTypeCard;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;

trait Request
{
    protected function getUnzerStringRequestEscapedParameter(string $varName, string $defaultValue = ''): string
    {
        /** @phpstan-ignore argument.type */
        $value = Registry::getRequest()->getRequestEscapedParameter($varName, $defaultValue);
        return is_string($value) ? $value : '';
    }

    protected function getUnzerStringRequestParameter(string $varName, string $defaultValue = ''): string
    {
        /** @phpstan-ignore argument.type */
        $value = Registry::getRequest()->getRequestParameter($varName, $defaultValue);
        return is_string($value) ? $value : '';
    }

    protected function getUnzerArrayRequestParameter(string $varName, array $defaultValue = []): array
    {
        /** @phpstan-ignore argument.type */
        $value = Registry::getRequest()->getRequestParameter($varName, $defaultValue);
        return is_array($value) ? $value : [];
    }

    protected function getUnzerFloatRequestParameter(string $varName, float $defaultValue = 0.0): float
    {
        /** @phpstan-ignore argument.type */
        $value = Registry::getRequest()->getRequestParameter($varName, $defaultValue);
        $value = is_string($value) ? $this->normalizeNumber($value) : $value;
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function getUnzerBoolRequestParameter(string $varName, bool $defaultValue = false): bool
    {
        /** @phpstan-ignore argument.type */
        $value = Registry::getRequest()->getRequestParameter($varName, $defaultValue);
        return (bool)$value;
    }

    private function normalizeNumber(string $input): float
    {
        $input = str_replace(' ', '', $input);
        $lastCommaPos = strrpos($input, ',');
        $number = $input;
        if ($lastCommaPos !== false) {
            if (strpos($number, '.', $lastCommaPos) === false) {
                $number = substr_replace($input, '.', $lastCommaPos, 1);
            }
            $number = str_replace(',', '', $number);
        }

        return (float)$number;
    }
}
