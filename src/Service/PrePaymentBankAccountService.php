<?php

namespace OxidSolutionCatalysts\Unzer\Service;

use UnzerSDK\Resources\TransactionTypes\Charge;
use OxidEsales\Eshop\Core\Session;

class PrePaymentBankAccountService
{
    private Session $session;

    private const SESSION_VARIABLE_PREFIX = 'PrePaymentBankAccountService';

    public function __construct(Session $session)
    {
        $this->session = $session;
    }
    public function persistBankAccountInfo(Charge $charge): void
    {
        $orderId = $charge->getOrderId() ?? '';
        // in unzer sdk it's called orderId in oxid: oxunzerordernr
        if ($charge->getIban()) {
            $this->session->setVariable(
                $this->getSessionVariableName($orderId, 'iban'),
                $charge->getIban()
            );
        }

        if ($charge->getBic()) {
            $this->session->setVariable(
                $this->getSessionVariableName($orderId, 'bic'),
                $charge->getBic()
            );
        }

        if ($charge->getHolder()) {
            $this->session->setVariable(
                $this->getSessionVariableName($orderId, 'holder'),
                $charge->getHolder()
            );
        }

        if ($charge->getDescriptor()) {
            $this->session->setVariable(
                $this->getSessionVariableName($orderId, 'descriptor'),
                $charge->getHolder()
            );
        }
    }

    public function getIban(string $unzerOrderNumber): ?string
    {
        return $this->getSessionVariableStringValue($unzerOrderNumber, 'iban');
    }

    public function getBic(string $unzerOrderNumber): ?string
    {
        return $this->getSessionVariableStringValue($unzerOrderNumber, 'bic');
    }

    public function getHolder(string $unzerOrderNumber): ?string
    {
        return $this->getStringVarFromSession(
            $this->getSessionVariableName($unzerOrderNumber, 'holder')
        );
    }

    public function getDescriptor(string $unzerOrderNumber): ?string
    {
        return $this->getStringVarFromSession(
            $this->getSessionVariableName($unzerOrderNumber, 'descriptor')
        );
    }

    private function getStringVarFromSession(string $varName): string
    {
        $result = $this->session->getVariable($varName);
        return is_string($result) ? $result : '';
    }

    private function getSessionVariableName(string $unzerOrderNumber, string $variableName): string
    {
        return self::SESSION_VARIABLE_PREFIX . '_' . $unzerOrderNumber . '_' . $variableName;
    }

    private function getSessionVariableStringValue(string $unzerOrderNumber, string $variableName): string
    {
        $value = $this->session->getVariable(
            $this->getSessionVariableName($unzerOrderNumber, $variableName)
        );

        if (!is_string($value)) {
            $value = '';
        }

        return $value;
    }
}
