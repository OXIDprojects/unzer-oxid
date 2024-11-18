<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\PaymentExtensions;

use Exception;
use JsonException;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\Unzer\Service\PrePaymentBankAccountService;
use OxidSolutionCatalysts\Unzer\Core\UnzerDefinitions;
use OxidSolutionCatalysts\Unzer\Service\DebugHandler;
use OxidSolutionCatalysts\Unzer\Service\SavedPayment\SavedPaymentSessionService;
use OxidSolutionCatalysts\Unzer\Service\Transaction as TransactionService;
use OxidSolutionCatalysts\Unzer\Service\Payment as PaymentService;
use OxidSolutionCatalysts\Unzer\Service\Translator;
use OxidSolutionCatalysts\Unzer\Service\Unzer as UnzerService;
use OxidSolutionCatalysts\Unzer\Service\UnzerSDKLoader;
use OxidSolutionCatalysts\Unzer\Traits\Request;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use UnzerSDK\Constants\RecurrenceTypes;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Basket as UnzerResourceBasket;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Card as UnzerSDKPaymentTypeCard;
use UnzerSDK\Resources\PaymentTypes\PaylaterInstallment;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Unzer;

/**
 *  TODO: Decrease count of dependencies to 13
 *  TODO: Decrease overall complexity below 50
 *  TODO: Fix all the suppressed warnings
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
abstract class UnzerPayment
{
    use ServiceContainer;
    use Request;

    /** @var Unzer */
    protected $unzerSDK;

    /** @var UnzerService */
    protected $unzerService;

    /** @var string */
    protected $unzerOrderId = '';

    /** @var string */
    protected $paymentMethod = '';

    /** @var bool */
    protected $needPending = false;

    /** @var bool */
    protected $ajaxResponse = false;

    /** @var array */
    protected $allowedCurrencies = [];

    /** @var DebugHandler $logger */
    private $logger;

    /** @var SavedPaymentSessionService $savedPaymentSessionService */
    private $savedPaymentSessionService;

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
        $this->savedPaymentSessionService = $this->getServiceFromContainer(SavedPaymentSessionService::class);
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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @throws JsonException
     * @throws \OxidSolutionCatalysts\Unzer\Exception\UnzerException
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     */
    public function execute(
        User $userModel,
        Basket $basketModel
    ): bool {
        $this->throwExceptionIfPaymentDataError();

        $paymentType = $this->getUnzerPaymentTypeObject();
        //payment type here is saved payment
        if ($paymentType instanceof Paypal) {
            $this->setPaypalPaymentDataId($paymentType);
        }
        $companyType = $this->getUnzerStringRequestParameter('unzer_company_form', '');

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

        $transaction = $this->doTransactions($basketModel, $customer, $userModel, $paymentType);
        $this->unzerService->setSessionVars($transaction);

        if ($transaction instanceof Charge) {
            $prePaymentBankAccountService = $this->getServiceFromContainer(PrePaymentBankAccountService::class);
            $prePaymentBankAccountService->persistBankAccountInfo($transaction);
        }

        if ($this->getUnzerStringRequestParameter('birthdate')) {
            $userModel->save();
        }

        if ($userModel->getId()) {
            $this->savePayment($userModel);
        }

        return true;
    }

    public function getUnzerOrderId(): string
    {
        return $this->unzerOrderId;
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
            $sdkPaymentID = UnzerDefinitions::INSTALLMENT_UNZER_PAYLATER_PAYMENT_ID;
            try {
                $loader = $this->getServiceFromContainer(UnzerSDKLoader::class);
                $UnzerSdk = $loader->getUnzerSDK(
                    $sdkPaymentID,
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
            if ($this->isSavedPayment()) {
                $transaction = $this->performTransactionForSavedPayment(
                    $paymentType,
                    $paymentProcedure,
                    $price,
                    $basketModel,
                    $customer,
                    $uzrBasket
                );
            } else {
                $transaction = $this->performDefaultTransaction(
                    $paymentType,
                    $paymentProcedure,
                    $price,
                    $basketModel,
                    $customer,
                    $uzrBasket
                );
            }
        }
        return $transaction;
    }

    /**
     * proves if there are errors coming from unzer like: credit card has been expired, to send this error to the
     * customer and log it
     *
     * @return void
     */
    private function throwExceptionIfPaymentDataError()
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
     * @throws JsonException
     */
    private function setPaypalPaymentDataId(Paypal $paymentType): void
    {
        $paymentData = $this->getUnzerStringRequestParameter('paymentData');
        if ($paymentData) {
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
            /** @var string $orderId */
            $orderId = Registry::getSession()->getVariable('sess_challenge');
            $transactionService->writeTransactionToDB(
                $orderId,
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
        $ids = $transactionService->getTransactionIds($user);
        $savedUserPayments = [];
        if ($ids) {
            $savedUserPayments = $transactionService->getSavedPaymentsForUser($user, $ids, true);
        }

        $currentPayment = $this->getServiceFromContainer(PaymentService::class)
            ->getSessionUnzerPayment();

        if ($currentPayment) {
            $currentPaymentType = $currentPayment->getPaymentType();
            foreach ($savedUserPayments as $savedPayment) {
                if (
                    $currentPaymentType instanceof UnzerSDKPaymentTypeCard
                    && $this->areCardsEqual($currentPaymentType, $savedPayment)
                ) {
                        return true;
                }
                if (
                    ($currentPaymentType instanceof Paypal) &&
                    $this->arePayPalAccountsEqual($currentPaymentType, $savedPayment)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    private function areCardsEqual(UnzerSDKPaymentTypeCard $card1, array $card2): bool
    {
        foreach ($card2 as $card) {
            if (
                $card1->getNumber() === $card['number'] &&
                $card1->getExpiryDate() === $card['expiryDate'] &&
                $card1->getCardHolder() === $card['cardHolder']
            ) {
                return true;
            }
        }
        return false;
    }

    private function arePayPalAccountsEqual(Paypal $currentPaymentType, array $savedPayment): bool
    {
        foreach ($savedPayment as $paypalAccount) {
            if ($currentPaymentType->getEmail() === $paypalAccount['email']) {
                return true;
            }
        }
        return false;
    }

    private function isSavedPayment(): bool
    {
        return $this->getUnzerBoolRequestParameter('is_saved_payment_in_action');
    }

    private function performDefaultTransaction(
        BasePaymentType $paymentType,
        string $paymentProcedure,
        float $price,
        Basket $basketModel,
        Customer $customer,
        UnzerResourceBasket $uzrBasket
    ): AbstractTransactionType {
        return $paymentType->{$paymentProcedure}(
            $price,
            $basketModel->getBasketCurrency()->name,
            $this->unzerService->prepareOrderRedirectUrl($this->redirectUrlNeedPending()),
            $customer,
            $this->unzerOrderId,
            $this->unzerService->getShopMetadata($this->paymentMethod),
            $uzrBasket,
            null,
            null,
            null,
            $this->savedPaymentSessionService->isSavedPayment()
                ? RecurrenceTypes::ONE_CLICK : null
        );
    }

    private function performTransactionForSavedPayment(
        BasePaymentType $paymentType,
        string $paymentProcedure,
        float $price,
        Basket $basketModel,
        Customer $customer,
        UnzerResourceBasket $uzrBasket
    ): AbstractTransactionType {
        if ($paymentType instanceof Paypal) {
            return $paymentType->{$paymentProcedure}(
                $price,
                $basketModel->getBasketCurrency()->name,
                $this->unzerService->prepareOrderRedirectUrl($this->redirectUrlNeedPending()),
                $customer,
                $this->unzerOrderId,
                $this->unzerService->getShopMetadata($this->paymentMethod),
                $uzrBasket,
                false,
                null,
                null,
                RecurrenceTypes::ONE_CLICK
            );
        }

        return $paymentType->{$paymentProcedure}(
            $price,
            $basketModel->getBasketCurrency()->name,
            $this->unzerService->prepareOrderRedirectUrl($this->redirectUrlNeedPending()),
            $customer,
            $this->unzerOrderId,
            $this->unzerService->getShopMetadata($this->paymentMethod),
            $uzrBasket,
            true,
            null,
            null,
            RecurrenceTypes::ONE_CLICK
        );
    }
}
