<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Controller;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Session;
use OxidSolutionCatalysts\Unzer\Model\TmpOrder;
use OxidSolutionCatalysts\Unzer\Service\UnzerSDKLoader;
use OxidSolutionCatalysts\Unzer\Service\UserRepository;
use OxidSolutionCatalysts\Unzer\Service\UnzerDefinitions as UnzerDefinitionsService;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Registry;
use UnzerSDK\Constants\PaymentState;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Unzer;

class PaymentController extends PaymentController_parent
{
    use ServiceContainer;

    /**
     * Executes parent method parent::render().
     */
    public function render()
    {
        $template = parent::render();
        $this->checkForUnzerPaymentErrors();
        return $template;
    }

    /**
     * Template variable getter. Returns paymentlist
     *
     * @return array<array-key, mixed>|object
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getPaymentList()
    {
        $paymentList = (array)parent::getPaymentList();
        $unzerDefinitions = $this->getServiceFromContainer(UnzerDefinitionsService::class)
            ->getDefinitionsArray();
        $actShopCurrency = Registry::getConfig()->getActShopCurrencyObject();
        $userRepository = $this->getServiceFromContainer(UserRepository::class);
        $userCountryIso = $userRepository->getUserCountryIso();

        $paymentListRaw = $paymentList;
        $paymentList = [];

        /**
         * @var \OxidSolutionCatalysts\Unzer\Model\Payment $payment
         */
        foreach ($paymentListRaw as $key => $payment) {
            if (!is_object($payment)) {
                continue;
            }
            // any non-unzer payment ...
            if (!$payment->isUnzerPayment()) {
                $paymentList[$key] = $payment;
                continue;
            }

            if (
                (
                    $payment->isUnzerPaymentHealthy()
                ) &&
                (
                    empty($unzerDefinitions[$key]['currencies']) ||
                    in_array($actShopCurrency->name, $unzerDefinitions[$key]['currencies'], true)
                ) &&
                (
                    empty($unzerDefinitions[$key]['countries']) ||
                    in_array($userCountryIso, $unzerDefinitions[$key]['countries'], true)
                )
            ) {
                $paymentList[$key] = $payment;
            }
        }

        return $paymentList;
    }

    /**
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function checkForUnzerPaymentErrors(): void
    {
        $session = Registry::getSession();
        $this->checkForDuplicateOrderAttempt($session);
        /** @var \OxidSolutionCatalysts\Unzer\Model\Payment $payment */
        $payment = oxNew(Payment::class);
        $actualPaymentId = $this->getCheckedPaymentId();
        if (
            $this->getPaymentError() &&
            (is_string($actualPaymentId)) &&
            $payment->load($actualPaymentId) &&
            $payment->isUnzerPayment()
        ) {
            $orderId = is_string($session->getVariable('sess_challenge')) ?
                $session->getVariable('sess_challenge') :
                '';
            if ($orderId) {
                $order = oxNew(Order::class);
                $order->delete($orderId);
                $session->deleteVariable('sess_challenge');
            }
        }
    }

    /**
     * @param $session Session
     * @return void
     */
    private function checkForDuplicateOrderAttempt(Session $session): void
    {
        $oxOrderIdOfTmpOrder = is_string($session->getVariable('oxOrderIdOfTmpOrder')) ?
            $session->getVariable('oxOrderIdOfTmpOrder') :
            '';

        $unzerPaymentId = is_string($session->getVariable('UnzerPaymentId')) ?
            $session->getVariable('UnzerPaymentId') :
            '';

        $unzerSDK = $this->getUnzerSDKFromTmpOrder($oxOrderIdOfTmpOrder);

        if ($unzerSDK && $unzerPaymentId) {
            try {
                $unzerPayment = $unzerSDK->fetchPayment($unzerPaymentId);
                $unzerOrderId = $unzerPayment->getOrderId();
                $sessionUnzerOrderId = $session->getVariable('UnzerOrderId');
                if (
                    (int) $unzerOrderId === $sessionUnzerOrderId &&
                    ($unzerPayment->getState() === PaymentState::STATE_COMPLETED ||
                        $unzerPayment->getState() === PaymentState::STATE_PENDING)
                ) {
                    $session->deleteVariable('UnzerPaymentId');
                    $session->deleteVariable('UnzerOrderId');
                }
            } catch (UnzerApiException $e) {
                Registry::getLogger()->warning(
                    'Payment not found with key: ' . $unzerPaymentId . ' and message: ' . $e->getMessage()
                );
            }

            $session->deleteVariable('sess_challenge');
            $session->deleteVariable('oxOrderIdOfTmpOrder');
        }
    }

    /**
     * @param $oxOrderIdOfTmpOrder string
     * @return Unzer|null
     */
    private function getUnzerSDKFromTmpOrder(string $oxSessionOrderId): ?Unzer
    {
        $result = null;
        /** @var Order $tmpOrder */
        $tmpOrder = oxNew(TmpOrder::class)->getTmpOrderByOxOrderId($oxSessionOrderId);
        if ($tmpOrder) {
            $unzerSDK = $this->getServiceFromContainer(UnzerSDKLoader::class);

            $paymentId = $tmpOrder->getFieldData('oxpaymenttype');
            $paymentId = is_string($paymentId) ? $paymentId : '';
            $currency = $tmpOrder->getFieldData('oxcurrency');
            $currency = is_string($currency) ? $currency : '';

            $customerType = $this->getCustomerTypeFromTmpOrder($tmpOrder);

            $result = $unzerSDK->getUnzerSDK(
                $paymentId,
                $currency,
                $customerType
            );
        }
        return $result;
    }

    private function getCustomerTypeFromTmpOrder(Order $tmpOrder): string
    {
        if (
            empty($tmpOrder->getFieldData('oxdelcompany'))
            && empty($tmpOrder->getFieldData('oxbillcompany'))
            && empty($tmpOrder->oxorder__oxbillcompany->value)
            && empty($tmpOrder->oxorder__oxdelcompany->value)
        ) {
            return 'B2C';
        }

        return 'B2B';
    }
}
