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
        // in unzer sdk it's called orderId in oxid: oxunzerordernr
        if ($charge->getIban()) {
            $this->session->setVariable(
                $this->getSessionVariableName((string) $charge->getOrderId(), 'iban'),
                $charge->getIban()
            );
        }

        if ($charge->getBic()) {
            $this->session->setVariable(
                $this->getSessionVariableName((string) $charge->getOrderId(), 'bic'),
                $charge->getBic()
            );
        }

        if ($charge->getHolder()) {
            $this->session->setVariable(
                $this->getSessionVariableName((string) $charge->getOrderId(), 'holder'),
                $charge->getHolder()
            );
        }
    }

    public function getIban(string $unzerOrderNumber): ?string
    {
        return $this->getStringVarFromSession(
            $this->getSessionVariableName($unzerOrderNumber, 'iban')
        );
    }

    public function getBic(string $unzerOrderNumber): ?string
    {
        return $this->getStringVarFromSession(
            $this->getSessionVariableName($unzerOrderNumber, 'bic')
        );
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
            $this->getSessionVariableName($unzerOrderNumber, 'getDescriptor')
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
}
