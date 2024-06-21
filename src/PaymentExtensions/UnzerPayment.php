<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\PaymentExtensions;

use Exception;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Request;
use OxidSolutionCatalysts\Unzer\Core\UnzerDefinitions;
use OxidSolutionCatalysts\Unzer\Service\DebugHandler;
use OxidSolutionCatalysts\Unzer\Service\Transaction as TransactionService;
use OxidSolutionCatalysts\Unzer\Service\Payment as PaymentService;
use OxidSolutionCatalysts\Unzer\Service\Translator;
use OxidSolutionCatalysts\Unzer\Service\Unzer as UnzerService;
use OxidSolutionCatalysts\Unzer\Service\UnzerSDKLoader;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Card as UnzerSDKPaymentTypeCard;
use UnzerSDK\Resources\PaymentTypes\Paypal as PayPalPaymentType;
use UnzerSDK\Resources\PaymentTypes\PaylaterInstallment;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Unzer;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class UnzerPayment implements UnzerPaymentInterface
{
    use ServiceContainer;

    /** @var Unzer */
    protected $unzerSDK;

    /** @var UnzerService */
    protected $unzerService;

    protected string $unzerOrderId = '';

    /** @var string */
    protected $paymentMethod = '';

    /** @var bool */
    protected $needPending = false;

    /** @var bool */
    protected $ajaxResponse = false;

    /** @var array */
    protected $allowedCurrencies = [];

    private DebugHandler $logger;

    /**
     * @throws Exception
     */
    public function __construct(
        Unzer $unzerSDK,
        UnzerService $unzerService,
        DebugHandler $logger
    ) {
        $this->unzerSDK = $unzerSDK;
        $this->unzerService = $unzerService;

        $this->unzerOrderId = $this->unzerService->generateUnzerOrderId();

        $this->unzerService->setIsAjaxPayment($this->ajaxResponse);
        $this->logger = $logger;
    }

    public function getUnzerOrderId(): string
    {
        return $this->unzerOrderId;
    }
    /**
     * @return array
     */
    public function getPaymentCurrencies(): array
    {
        return $this->allowedCurrencies;
    }

    /**
     * @return bool
     */
    public function redirectUrlNeedPending(): bool
    {
        return $this->needPending;
    }

    /**
     * @return BasePaymentType
     */
    abstract public function getUnzerPaymentTypeObject(): BasePaymentType;

    /**
     * @throws \JsonException
     * @throws \OxidSolutionCatalysts\Unzer\Exception\UnzerException
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function execute(
        User $userModel,
        Basket $basketModel
    ): bool {
        $this->throwExceptionIfPaymentDataError();
        $request = Registry::getRequest();
        $paymentType = $this->getUnzerPaymentTypeObject();
        if ($paymentType instanceof PayPalPaymentType) {
            $this->setPaypalPaymentDataId($request, $paymentType);
            Registry::getSession()->setVariable('oscunzersavepayment_paypal', "1");
        }
        /** @var string $companyType */
        $companyType = $request->getRequestParameter('unzer_company_form', '');

        $customer = $this->unzerService->getUnzerCustomer(
            $userModel,
            null,
            $companyType
        );

        // first try to fetch customer, secondly create anew if not found in unzer
        try {
            $customer = $this->unzerSDK->fetchCustomer($customer);
            // for comparison and update, the original object must be recreated
            $originalCustomer = $this->unzerService->getUnzerCustomer(
                $userModel,
                null,
                $companyType
            );
            if ($this->unzerService->updateUnzerCustomer($customer, $originalCustomer)) {
                $customer = $this->unzerSDK->updateCustomer($customer);
            }
        } catch (UnzerApiException $apiException) {
            $customer = $this->unzerSDK->createCustomer($customer);
        }
//first_transaction
        $transaction = $this->doTransactions($basketModel, $customer, $userModel, $paymentType);
        $this->unzerService->setSessionVars($transaction);

        if ($request->getRequestParameter('birthdate')) {
            $userModel->save();
        }

        $savePayment = Registry::getRequest()->getRequestParameter('oscunzersavepayment');
        if ($this->existsInSavedPaymentsList($userModel) || $savePayment === "0") {
            $savePayment = "0";
        }
        Registry::getSession()->setVariable('oscunzersavepayment', $savePayment);

        if ($userModel->getId()) {
            $this->savePayment($userModel);
        }

        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function doTransactions(
        Basket $basketModel,
        Customer $customer,
        User $userModel,
        BasePaymentType $paymentType
    ): AbstractTransactionType {
        $this->throwExceptionIfPaymentDataError();
        $paymentProcedure = $this->unzerService->getPaymentProcedure($this->paymentMethod);
        $uzrBasket = $this->unzerService->getUnzerBasket($this->unzerOrderId, $basketModel);
        /** @var $paymentType PaylaterInstallment */
        if ($paymentType instanceof PaylaterInstallment) {
            $auth = oxNew(Authorization::class);
            $auth->setAmount($basketModel->getPrice()->getPrice());
            $currency = $basketModel->getBasketCurrency();
            $auth->setCurrency($currency->name);
            $auth->setReturnUrl($this->unzerService->prepareOrderRedirectUrl($this->redirectUrlNeedPending()));
            $auth->setOrderId($this->unzerOrderId);

            $uzrRiskData = $this->unzerService->getUnzerRiskData(
                $customer,
                $userModel
            );
            $auth->setRiskData($uzrRiskData);
            try {
                /** @var UnzerSDKLoader $loader */
                $loader = $this->getServiceFromContainer(UnzerSDKLoader::class);
                $UnzerSdk = $loader->getUnzerSDK(
                    UnzerDefinitions::INSTALLMENT_UNZER_PAYLATER_PAYMENT_ID,
                    $currency->name
                );
                $transaction = $UnzerSdk->performAuthorization(
                    $auth,
                    $paymentType,
                    $customer,
                    $this->unzerService->getShopMetadata($this->paymentMethod),
                    $uzrBasket
                );
            } catch (UnzerApiException $e) {
                throw new UnzerApiException($e->getMerchantMessage(), $e->getClientMessage());
            }
        } else {
            $priceObj = $basketModel->getPrice();
            $price = $priceObj ? $priceObj->getPrice() : 0;
            $transaction = $paymentType->{$paymentProcedure}(
                $price,
                $basketModel->getBasketCurrency()->name,
                $this->unzerService->prepareOrderRedirectUrl($this->redirectUrlNeedPending()),
                $customer,
                $this->unzerOrderId,
                $this->unzerService->getShopMetadata($this->paymentMethod),
                $uzrBasket
            );
        }
        return $transaction;
    }

    /**
     * proves if there are errors coming from unzer like: credit card has been expired, to send this error to the
     * customer and log it
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Exception
     */
    private function throwExceptionIfPaymentDataError(): void
    {
        /** @var UnzerService $unzerService */
        $unzerService = $this->getServiceFromContainer(UnzerService::class);
        $unzerPaymentData = $unzerService->getUnzerPaymentDataFromRequest();

        if ($unzerPaymentData->isSuccess === false && $unzerPaymentData->isError === true) {
            $this->logger
                ->log(
                    sprintf(
                        'Could not process Transaction for paymentId %s,'
                        . 'traceId: %s, timestamp: %s, customerMessage: %s',
                        $unzerPaymentData->id,
                        $unzerPaymentData->traceId,
                        $unzerPaymentData->timestamp,
                        $unzerPaymentData->getFirstErrorMessage()
                    )
                );
            throw new Exception(
                $unzerPaymentData->getFirstErrorMessage() ?? $this->getDefaultExceptionMessage()
            );
        }
    }

    private function getDefaultExceptionMessage(): string
    {
        $translator = $this->getServiceFromContainer(Translator::class);
        return $translator->translate('OSCUNZER_ERROR_DURING_CHECKOUT');
    }

    /**
     * @throws \JsonException
     */
    private function setPaypalPaymentDataId(Request $request, Paypal $paymentType): void
    {
        $paymentDataRaw = $request->getRequestParameter('paymentData');
        $paymentData = is_string($paymentDataRaw) ? $paymentDataRaw : '';
        if (!empty($paymentData) && is_string($paymentDataRaw)) {
            $aPaymentData = json_decode($paymentData, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($aPaymentData) && isset($aPaymentData['id'])) {
                $paymentType->setId($aPaymentData['id']);
            }
        }
    }

    private function savePayment(User $user): void
    {
        /** @var TransactionService $transactionService */
        $transactionService = $this->getServiceFromContainer(
            TransactionService::class
        );
        $payment = $this->getServiceFromContainer(PaymentService::class)
            ->getSessionUnzerPayment();
        try {
            $transactionService->writeTransactionToDB(
                Registry::getSession()->getSessionChallengeToken(),
                $user->getId(),
                $payment
            );
        } catch (Exception $e) {
            $this->logger
                ->log('Could not save Transaction for PaymentID (savePayment): ' . $e->getMessage());
        }
    }

    public function existsInSavedPaymentsList(User $user): bool
    {
        /** @var TransactionService $transactionService */
        $transactionService = $this->getServiceFromContainer(TransactionService::class);
        $ids = $transactionService->getTrancactionIds($user);
        $savedUserPayments = [];
        if ($ids) {
            $savedUserPayments = $transactionService->getSavedPaymentsForUser($user, $ids, true);
        }

        $currentPayment = $this->getServiceFromContainer(PaymentService::class)
            ->getSessionUnzerPayment();

        if ($currentPayment) {
            $currentPaymentType = $currentPayment->getPaymentType();
            foreach ($savedUserPayments as $savedPayment) {
                if ($currentPaymentType instanceof UnzerSDKPaymentTypeCard) {
                    if ($this->areCardsEqual($currentPaymentType, $savedPayment)) {
                        return true;
                    }
                }
                if ($currentPaymentType instanceof Paypal) {
                    if ($this->arePayPalAccountsEqual($currentPaymentType, $savedPayment)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function areCardsEqual(UnzerSDKPaymentTypeCard $card1, array $card2): bool
    {
        foreach ($card2 as $card) {
            if ( $card1->getNumber() === $card['number'] &&
                $card1->getExpiryDate() === $card['expiryDate'] &&
                $card1->getCardHolder() === $card['cardHolder'] ) {
                return true;
            }
        }
        return false;
    }

    private function arePayPalAccountsEqual(Paypal $currentPaymentType, $savedPayment)
    {
        foreach ($savedPayment as $paypalAccount) {
            if ( $currentPaymentType->getEmail()  === $paypalAccount['email'] ) {
                return true;
            }
        }
        return false;
    }
}
