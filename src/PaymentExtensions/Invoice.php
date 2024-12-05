<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\PaymentExtensions;

use OxidSolutionCatalysts\Unzer\Traits\Request;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\PaylaterInvoice as UnzerPaylaterInvoice;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;

class Invoice extends UnzerPayment
{
    use Request;

    protected string $paymentMethod = 'paylater-invoice';

    protected array $allowedCurrencies = ['EUR', 'CHF'];

    /**
     * @return BasePaymentType
     * @throws UnzerApiException
     */
    public function getUnzerPaymentTypeObject(): BasePaymentType
    {
        return $this->unzerSDK->createPaymentType(
            new UnzerPaylaterInvoice()
        );
    }

    /**
     * @param User $userModel
     * @param Basket $basketModel
     * @return bool
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function execute(
        User $userModel,
        Basket $basketModel
    ): bool {
        $companyType = $this->getUnzerStringRequestParameter('unzer_company_form');

        $customerObj = $this->unzerService->getUnzerCustomer(
            $userModel,
            null,
            $companyType
        );
        $uzrBasket = $this->unzerService->getUnzerBasket(
            $this->unzerOrderId,
            $basketModel
        );

        // resource from frontend
        $typeId = $this->getUnzerStringRequestParameter('unzer_type_id');
        // first try to fetch customer, secondly create anew if not found in unzer
        try {
            $customerObj = $this->unzerSDK->fetchCustomer($customerObj);
            // for comparison and update, the original object must be recreated
            $originalCustomer = $this->unzerService->getUnzerCustomer(
                $userModel,
                null,
                $companyType
            );
            if ($this->unzerService->updateUnzerCustomer($customerObj, $originalCustomer)) {
                $customerObj = $this->unzerSDK->updateCustomer($customerObj);
            }
        } catch (UnzerApiException $apiException) {
            $customerObj = $this->unzerSDK->createCustomer($customerObj);
        }
        // get risk data for customer
        $uzrRiskData = $this->unzerService->getUnzerRiskData(
            $customerObj,
            $userModel
        );

        $basketObj = $this->unzerSDK->createBasket($uzrBasket);
        $authObj = new Authorization(
            $basketModel->getPrice()->getBruttoPrice(),
            $basketModel->getBasketCurrency()->name,
            $this->unzerService->prepareOrderRedirectUrl($this->redirectUrlNeedPending())
        );
        $authObj->setRiskData($uzrRiskData);
        $authObj->setOrderId($this->unzerOrderId);
        $metadataObj = $this->unzerService->getShopMetadata($this->paymentMethod);

        $transaction = $this->unzerSDK->performAuthorization(
            $authObj,
            $typeId,
            $customerObj,
            $metadataObj,
            $basketObj
        );

        $this->unzerService->setSessionVars($transaction);

        if ($this->getUnzerBoolRequestParameter('birthdate')) {
            $userModel->save();
        }

        return true;
    }
}
