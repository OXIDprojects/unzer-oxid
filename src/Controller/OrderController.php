<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Controller;

use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\Unzer\Exception\Redirect;
use OxidSolutionCatalysts\Unzer\Exception\RedirectWithMessage;
use OxidSolutionCatalysts\Unzer\Model\Payment;
use OxidSolutionCatalysts\Unzer\Model\TmpOrder;
use OxidSolutionCatalysts\Unzer\Service\ModuleSettings;
use OxidSolutionCatalysts\Unzer\Service\Payment as PaymentService;
use OxidSolutionCatalysts\Unzer\Service\ResponseHandler;
use OxidSolutionCatalysts\Unzer\Service\Translator;
use OxidSolutionCatalysts\Unzer\Service\Unzer;
use OxidSolutionCatalysts\Unzer\Service\UnzerSDKLoader;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use OxidSolutionCatalysts\Unzer\Service\UnzerDefinitions;
use OxidSolutionCatalysts\Unzer\Core\UnzerDefinitions as CoreUnzerDefinitions;
use UnzerSDK\Constants\PaymentState;
use UnzerSDK\Exceptions\UnzerApiException;

/**
 * TODO: Decrease count of dependencies to 13
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class OrderController extends OrderController_parent
{
    use ServiceContainer;

    /**
     * @var bool $blSepaMandateConfirmError
     */
    protected $blSepaMandateConfirmError = null;

    /** @var Order $actualOrder */
    protected $actualOrder = null;

    /** @var array $companyTypes */
    protected $companyTypes = null;

    /**
     * @inerhitDoc
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function render()
    {
        $lang = Registry::getLang();

        /** @var int $iLang */
        $iLang = $lang->getBaseLanguage();
        $sLang = $lang->getLanguageAbbr($iLang);
        $this->_aViewData['unzerLocale'] = $sLang;

        // generate always a new threat metrix session id
        $unzer = $this->getServiceFromContainer(Unzer::class);
        $this->_aViewData['unzerThreatMetrixSessionID'] = $unzer->generateUnzerThreatMetrixIdInSession();
        $this->_aViewData['uzrcurrency'] = $this->getActCurrency();

        $this->getSavedPayment();

        $paymentService = $this->getServiceFromContainer(PaymentService::class);

        if ($this->isPaymentCancelledAfterFirstTransaction($paymentService)) {
            $this->cleanUpCancelledPayments();
        }

        return parent::render();
    }

    /**
     * @inerhitDoc
     */
    public function execute()
    {
        $ret = parent::execute();

        if ($ret && str_starts_with($ret, 'thankyou')) {
            $this->saveUnzerTransaction();
        }

        $unzer = $this->getServiceFromContainer(Unzer::class);
        if ($unzer->isAjaxPayment()) {
            $response = $this->getServiceFromContainer(ResponseHandler::class)->response();
            if ($ret && !str_contains($ret, 'thankyou')) {
                $response->setUnauthorized()->sendJson();
            }

            $response->setData([
                'redirectUrl' => $unzer->prepareRedirectUrl('thankyou')
            ])->sendJson();
        }

        return $ret;
    }

    /**
     * @throws Redirect
     * @throws DatabaseErrorException
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function unzerExecuteAfterRedirect(): void
    {
        // get basket contents
        $oUser = $this->getUser();
        $oBasket = Registry::getSession()->getBasket();
        if ($oBasket->getProductsCount()) {
            $oDB = DatabaseProvider::getDb();

            /** @var \OxidSolutionCatalysts\Unzer\Model\Order $oOrder */
            $oOrder = $this->getActualOrder();

            $oDB->startTransaction();

            //finalizing ordering process (validating, storing order into DB, executing payment, setting status ...)
            $iSuccess = (int)$oOrder->finalizeUnzerOrderAfterRedirect($oBasket, $oUser);

            // performing special actions after user finishes order (assignment to special user groups)
            $oUser->onOrderExecute($oBasket, $iSuccess);

            $nextStep = $this->getNextStep($iSuccess);
            $unzerService = $this->getServiceFromContainer(Unzer::class);
            Registry::getSession()->setVariable('orderDisableSqlActiveSnippet', false);

            if ('thankyou' === $nextStep) {
                $oDB->commitTransaction();
                $paymentService = $this->getServiceFromContainer(PaymentService::class);

                if ($this->isPaymentCancelled($paymentService)) {
                    $this->cleanUpCancelledPayments();
                }

                if ($unzerService->ifImmediatePostAuthCollect($paymentService)) {
                    $paymentService->doUnzerCollect(
                        $oOrder,
                        $oUser->getId(),
                        $oBasket->getDiscountedProductsBruttoPrice()
                    );
                }

                throw new Redirect($unzerService->prepareRedirectUrl($nextStep));
            }

            $oDB->rollbackTransaction();
            $translator = $this->getServiceFromContainer(Translator::class);
            throw new RedirectWithMessage(
                $unzerService->prepareRedirectUrl($nextStep),
                $translator->translate('OSCUNZER_ERROR_DURING_CHECKOUT')
            );
        }
    }

    /**
     * @return bool|null
     */
    public function isSepaMandateConfirmationError()
    {
        return $this->blSepaMandateConfirmError;
    }

    /**
     * @return bool|null
     */
    public function isSepaPayment(): ?bool
    {
        $payment = $this->getPayment();

        return (
            $payment instanceof Payment &&
            (
                $payment->getId() === CoreUnzerDefinitions::SEPA_UNZER_PAYMENT_ID ||
                $payment->getId() === CoreUnzerDefinitions::SEPA_SECURED_UNZER_PAYMENT_ID
            )
        );
    }

    /**
     * @return bool|null
     */
    public function isSepaConfirmed(): ?bool
    {
        if ($this->isSepaPayment()) {
            $blSepaMandateConfirm = Registry::getRequest()->getRequestParameter('sepaConfirmation');
            if (!$blSepaMandateConfirm) {
                $this->blSepaMandateConfirmError = true;
                return false;
            }
        }
        return true;
    }

    /**
     * @return void
     */
    public function saveUnzerTransaction(): void
    {
        /** @var \OxidSolutionCatalysts\Unzer\Model\Order $order */
        $order = $this->getActualOrder();
        $order->initWriteTransactionToDB();
    }

    /**
     * @return mixed|string
     */
    public function getApplePayLabel()
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getApplePayLabel();
    }

    /**
     * @return array
     */
    public function getSupportedApplepayMerchantCapabilities(): array
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getActiveApplePayMerchantCapabilities();
    }

    /**
     * @return array
     */
    public function getSupportedApplePayNetworks(): array
    {
        return $this->getServiceFromContainer(ModuleSettings::class)->getActiveApplePayNetworks();
    }

    /**
     * @return string
     */
    public function getUserCountryIso(): string
    {
        $country = oxNew(Country::class);
        /** @var string $oxcountryid */
        $oxcountryid = Registry::getSession()->getUser()->getFieldData('oxcountryid');
        $country->load($oxcountryid);

        /** @var string $oxisoalpha2 */
        $oxisoalpha2 = $country->getFieldData('oxisoalpha2');
        return $oxisoalpha2;
    }

    /**
     * @return Order
     */
    public function getActualOrder(): Order
    {
        if (!($this->actualOrder instanceof \OxidSolutionCatalysts\Unzer\Model\Order)) {
            $this->actualOrder = oxNew(Order::class);
            /** @var string $sess_challenge */
            $sess_challenge = Registry::getSession()->getVariable('sess_challenge');
            $this->actualOrder->load($sess_challenge);
        }
        return $this->actualOrder;
    }

    /**
     * @return int|mixed
     */
    public function getPaymentSaveSetting()
    {
        $bSavedPayment = 1;

        // no guests allowed
        $user = $this->getUser();
        /** @var User $user */
        if (!$user || (!$user->getFieldData('oxpassword'))) {
            $bSavedPayment = 0;
        }
        return $bSavedPayment;
    }
    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getUnzerCompanyTypes(): array
    {
        if (empty($this->companyTypes)) {
            $this->companyTypes = [];
            $translator = $this->getServiceFromContainer(Translator::class);
            $unzerDefinitions = $this->getServiceFromContainer(UnzerDefinitions::class);

            foreach ($unzerDefinitions->getCompanyTypes() as $value) {
                $this->companyTypes[$value] = $translator->translate('OSCUNZER_COMPANY_FORM_' . $value);
            }
        }
        return $this->companyTypes;
    }

    /**
     * execute Unzer defined via getExecuteFnc
     */
    public function executeoscunzer(): ?string
    {
        if (!$this->isSepaConfirmed()) {
            return null;
        }

        if (!$this->validateTermsAndConditions()) {
            $this->_blConfirmAGBError = true;
            return null;
        }

        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        /** @var \OxidEsales\Eshop\Application\Model\Payment $payment */
        $payment = $this->getPayment();
        $paymentOk = $paymentService->executeUnzerPayment($payment);

        // all orders without redirect would be finalized now
        if ($paymentOk) {
            $this->unzerExecuteAfterRedirect();
        }

        return null;
    }

    /**
     * OXID-Core
     * @inheritDoc
     */
    public function getExecuteFnc()
    {
        /** @var Payment $payment */
        $payment = $this->getPayment();
        if (
            $payment->isUnzerPayment()
        ) {
            return 'executeoscunzer';
        }
        return parent::getExecuteFnc();
    }

    protected function getSavedPayment(): void
    {
        $UnzerSdk = $this->getServiceFromContainer(UnzerSDKLoader::class);
        $unzerSDK = $UnzerSdk->getUnzerSDK();

        $ids = $this->getTrancactionIds();
        $paymentTypes = false;
        if ($ids) {
            foreach ($ids as $typeId) {
                if (!empty($typeId['PAYMENTTYPEID'])) {
                    try {
                        $paymentType = $unzerSDK->fetchPaymentType($typeId['PAYMENTTYPEID']);
                    } catch (UnzerApiException $e) {
                        continue;
                    }

                    if (strpos($typeId['PAYMENTTYPEID'], 'crd')) {
                        $paymentTypes['card'][$typeId['PAYMENTTYPEID']] = $paymentType->expose();
                    }
                    if (strpos($typeId['PAYMENTTYPEID'], 'ppl')) {
                        $paymentTypes['paypal'][$typeId['PAYMENTTYPEID']] = $paymentType->expose();
                    }
                    if (strpos($typeId['PAYMENTTYPEID'], 'sdd')) {
                        $paymentTypes['sepa'][$typeId['PAYMENTTYPEID']] = $paymentType->expose();
                    }
                }
            }
        }

        $this->_aViewData['unzerPaymentType'] = $paymentTypes;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function getTrancactionIds(): array
    {
        $result = [];
        if ($this->getUser() && $this->getUser()->getId() !== null) {
            $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
            $result = $oDB->getAll(
                "SELECT PAYMENTTYPEID from oscunzertransaction
                     where OXUSERID = :oxuserid
                       AND PAYMENTTYPEID IS NOT NULL
                     GROUP BY PAYMENTTYPEID ",
                [':oxuserid' => $this->getUser()->getId()]
            );
        }
        return $result;
    }

    /**
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     */
    private function isPaymentCancelled(PaymentService $paymentService): bool
    {
        $paymentResource = $paymentService->getSessionUnzerPayment(true);

        if ($paymentResource !== null) {
            if ($paymentResource->getState() === 0) {
                return false;
            }

            if (in_array(
                $paymentResource->getState(),
                [
                    PaymentState::STATE_CANCELED,
                    \OxidSolutionCatalysts\Unzer\Service\Payment::STATUS_NOT_FINISHED
                ])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws \OxidSolutionCatalysts\Unzer\Exception\Redirect
     */
    private function redirectUserToCheckout(Unzer $unzerService, \OxidSolutionCatalysts\Unzer\Model\Order $order): void
    {
        $translator = $this->getServiceFromContainer(Translator::class);
        $unzerOrderNr = $order->getUnzerOrderNr();
        throw new RedirectWithMessage(
            $unzerService->prepareRedirectUrl('payment?payerror=-6'),
            sprintf($translator->translate('OSCUNZER_CANCEL_DURING_CHECKOUT'), $unzerOrderNr)
        );
    }

    private function cleanUpCancelledPayments(): void
    {
        $oUser = $this->getUser();
        $oBasket = Registry::getSession()->getBasket();
        if ($oBasket->getProductsCount()) {
            $oDB = DatabaseProvider::getDb();

            /** @var \OxidSolutionCatalysts\Unzer\Model\Order $oOrder */
            $oOrder = $this->getActualOrder();

            $oDB->startTransaction();

            //finalizing ordering process (validating, storing order into DB, executing payment, setting status ...)
            $iSuccess = (int)$oOrder->finalizeUnzerOrderAfterRedirect($oBasket, $oUser);

            // performing special actions after user finishes order (assignment to special user groups)
            $oUser->onOrderExecute($oBasket, $iSuccess);

            $unzerService = $this->getServiceFromContainer(Unzer::class);
            Registry::getSession()->setVariable('orderDisableSqlActiveSnippet', false);

            $oDB->commitTransaction();

            Registry::getSession()->setVariable('sess_challenge', $this->getUtilsObjectInstance()->generateUID());
            Registry::getSession()->setBasket($oBasket);
            $this->redirectUserToCheckout($unzerService, $oOrder);
        }
    }

    private function isPaymentCancelledAfterFirstTransaction(PaymentService $paymentService): bool
    {
        $paymentResource = $paymentService->getSessionUnzerPayment(true);

        if ($paymentResource === null || $paymentResource->getState() !== 0) {
            return false;
        }


        $tmpOrderArray = [];
        $orderId = $paymentResource->getOrderId();
        if ($orderId !== null) {
            $tmpOrderArray = oxNew(TmpOrder::class)->getTmpOrderByUnzerId($orderId);
        }

        if ($orderId === null) {
            return false;
        }

        $oTmpOrder = oxNew(TmpOrder::class);
        $tmpOrderArray = $oTmpOrder->getTmpOrderByUnzerId($orderId);

        if (empty($tmpOrderArray)) {
            return false;
        }


        if ($oTmpOrder->load($tmpOrderArray['OXID'])) {
            $oTmpOrder->delete();
        }

        return true;
    }
}
