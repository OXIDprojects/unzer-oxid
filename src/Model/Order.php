<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Model;

use Exception;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Application\Model\UserPayment;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSolutionCatalysts\Unzer\Service\DebugHandler;
use OxidSolutionCatalysts\Unzer\Service\Payment as PaymentService;
use OxidSolutionCatalysts\Unzer\Service\Transaction as TransactionService;
use OxidSolutionCatalysts\Unzer\Service\Unzer;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use UnzerSDK\Constants\PaymentState;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;

/**
 * TODO: Decrease count of dependencies to 13
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Order extends Order_parent
{
    use ServiceContainer;

    /**
     * @param Basket $oBasket
     * @param User $oUser
     * @param array $params
     * @return int|bool
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function finalizeUnzerOrderAfterRedirect(
        Basket $oBasket,
        User $oUser,
        array $params = []
    ) {
        $orderId = Registry::getSession()->getVariable('sess_challenge');
        $orderId = is_string($orderId) ? $orderId : '';
        $iRet = self::ORDER_STATE_PAYMENTERROR;

        if (!$orderId) {
            return $iRet;
        }

        if ($this->_checkOrderExist($orderId)) {
            $logger = $this->getServiceFromContainer(DebugHandler::class);
            $logger->log('finalizeUnzerOrderAfterRedirect: Order already exists, no need to save again: ' . $orderId);
            return self::ORDER_STATE_ORDEREXISTS;
        }

        $this->setId($orderId);
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $unzerService = $this->getServiceFromContainer(Unzer::class);
        $unzerPaymentStatus = $paymentService->getUnzerPaymentStatus();

        $this->_setUser($oUser);
        $this->_loadFromBasket($oBasket);
        $oUserPayment = $this->_setPayment($oBasket->getPaymentId());
        $this->_setFolder();
        $this->save();

        if (!$this->getFieldData('oxordernr')) {
            $this->_setNumber();
        }

        $unzerOrderId = $paymentService->getUnzerOrderId();
        $this->setUnzerOrderNr($unzerOrderId);
        $unzerService->resetUnzerOrderId();
        Registry::getSession()->deleteVariable('ordrem');
        $this->_updateOrderDate();

        $oBasket->setOrderId($orderId);
        $this->_updateWishlist($oBasket->getContents(), $oUser);
        $this->_updateNoticeList($oBasket->getContents(), $oUser);
        $this->_markVouchers($oBasket, $oUser);

        $this->_setOrderStatus($unzerPaymentStatus);

        if ($paymentService->isPrepayment() || $paymentService->isInvoice()) {
            $iRet = $this->sendOrderConfirmationEmail($oUser, $oBasket, $oUserPayment);
        } else {
            if ($unzerPaymentStatus === PaymentService::STATUS_OK) {
                $this->markUnzerOrderAsPaid();
                $this->setTmpOrderStatus($unzerOrderId, 'FINISHED');
                // send order by email to shop owner and current user
                // don't let order fail due to stock check while sending out the order mail
                $iRet = $this->sendOrderConfirmationEmail($oUser, $oBasket, $oUserPayment);
            } else {
                if ($unzerPaymentStatus !== PaymentService::STATUS_NOT_FINISHED) {
                    Registry::getSession()->setVariable('orderCancellationProcessed', true);
                }

                $this->_setOrderStatus($unzerPaymentStatus);
                $this->setTmpOrderStatus($unzerOrderId, $unzerPaymentStatus);

                $isError = $unzerPaymentStatus === PaymentService::STATUS_ERROR;
                if (!$isError && !isset($params['finalizeCancellation'])) {
                    //  then we consider this is a payment with only auth mode and the order is completed
                    $this->sendOrderConfirmationEmail($oUser, $oBasket, $oUserPayment);
                }
                if (!$isError) {
                    $iRet = 1;
                }
            }
        }

        $this->initWriteTransactionToDB(
            $paymentService->getSessionUnzerPayment(true)
        );

        return $iRet;
    }

    /**
     * @param Basket $oBasket
     * @param User $oUser
     * @param string $unzerOrderId
     * @return int|bool
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function createTmpOrder(
        Basket $oBasket,
        User $oUser,
        string $unzerOrderId
    ) {
        $orderId = Registry::getSession()->getVariable('sess_challenge');
        $orderId = is_string($orderId) ? $orderId : '';
        $iRet = self::ORDER_STATE_PAYMENTERROR;

        if (!$orderId) {
            return $iRet;
        }
        if ($this->_checkOrderExist($orderId)) {
            return self::ORDER_STATE_ORDEREXISTS;
        }
        $this->setId($orderId);
        $this->_setUser($oUser);
        $this->_loadFromBasket($oBasket);
        $oUserPayment = $this->_setPayment($oBasket->getPaymentId());
        $this->_oPayment = $oUserPayment;
        $this->_setFolder();

        $this->setUnzerOrderNr($unzerOrderId);

        $blRet = $this->_executePayment($oBasket, $oUserPayment);
        if ($blRet !== true) {
            return $blRet;
        }

        $this->_updateOrderDate();
        $oBasket->setOrderId($orderId);
        $this->_updateWishlist($oBasket->getContents(), $oUser);
        $this->_updateNoticeList($oBasket->getContents(), $oUser);
        $this->_markVouchers($oBasket, $oUser);
        $this->_setOrderStatus(PaymentService::STATUS_NOT_FINISHED);
        $tmpOrder = oxNew(TmpOrder::class);
        $tmpOrder->saveTmpOrder($this);

        Registry::getSession()->setVariable('oxOrderIdOfTmpOrder', $orderId);

        return $iRet;
    }

    public function getUnzerOrderNr(): int
    {
        $value = $this->getFieldData('oxunzerordernr');
        return is_numeric($value) ? (int)$value : 0;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function setUnzerOrderNr(string $unzerOrderId): string
    {
        /** @var QueryBuilderFactoryInterface $queryBuilderFactory */
        $queryBuilderFactory = $this->getServiceFromContainer(QueryBuilderFactoryInterface::class);

        $queryBuilder = $queryBuilderFactory->create();

        $query = $queryBuilder
            ->update('oxorder')
            ->set("oxunzerordernr", ":oxunzerordernr")
            ->where("oxid = :oxid");

        $parameters = [
            ':oxunzerordernr' => $unzerOrderId,
            ':oxid' => $this->getId()
        ];

        $query->setParameters($parameters)->execute();

        // TODO fixme Access to an undefined property
        // OxidSolutionCatalysts\Unzer\Model\Order::$oxorder__oxunzerordernr.
        /** @phpstan-ignore-next-line */
        $this->oxorder__oxunzerordernr = new Field($unzerOrderId);

        return $unzerOrderId;
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function markUnzerOrderAsPaid(): void
    {
        $oxpaid = $this->getFieldData('oxpaid');
        if ($oxpaid === '0000-00-00 00:00:00' || is_null($oxpaid)) {
            $utilsDate = Registry::getUtilsDate();
            $date = date('Y-m-d H:i:s', $utilsDate->getTime());
            $this->setFieldData('oxpaid', $date);
            $this->save();
        }
        // e.g. prepayments start with "NO_FINISHED", if they are marked as paid,
        // we set the status to OK here
        $this->_setOrderStatus('OK');
    }

    /**
     * Update order oxtransid
     */
    public function setUnzerTransId(string $sTransId): void
    {
        $this->setFieldData('oxtransid', $sTransId);
        $this->save();
    }

    /**
     * @param \UnzerSDK\Resources\Payment|null $unzerPayment
     * @return bool
     * @throws UnzerApiException
     * @throws Exception
     */
    public function initWriteTransactionToDB($unzerPayment = null): bool
    {
        /** @var string $oxpaymenttype */
        $oxpaymenttype = $this->getFieldData('oxpaymenttype');
        if (
            strpos($oxpaymenttype, "oscunzer") !== false
        ) {
            return $this->getServiceFromContainer(TransactionService::class)
                ->writeTransactionToDB(
                    $this->getId(),
                    $this->getOrderUser()->getId() ?: '',
                    $unzerPayment instanceof \UnzerSDK\Resources\Payment ?
                        $unzerPayment :
                        $this->getServiceFromContainer(PaymentService::class)->getSessionUnzerPayment()
                );
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getUnzerInvoiceNr()
    {
        /** @var int $number */
        $number = $this->getFieldData('OXINVOICENR') !== 0 ?
            $this->getFieldData('OXINVOICENR') :
            $this->getFieldData('OXORDERNR');
        return $number;
    }

    /**
     * @param $unzerPayment Payment
     * @return void
     * @throws Exception
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function finalizeTmpOrder(Payment $unzerPayment, bool $error = false): void
    {
        // set order in any case as Paid
        $this->markUnzerOrderAsPaid();

        if ($error === true) {
            $this->_setOrderStatus(PaymentService::STATUS_ERROR);
        } else {
            switch ($unzerPayment->getState()) {
                case PaymentState::STATE_PENDING:
                    $this->_setOrderStatus(PaymentService::STATUS_NOT_FINISHED);
                    break;
                case PaymentState::STATE_CANCELED:
                    $this->cancelOrder();
                    break;
            }
        }

        if (!$this->getFieldData('oxordernr')) {
            $this->_setNumber();
        }

        $this->initWriteTransactionToDB($unzerPayment);
        $this->save();
    }

    /**
     * @inerhitDoc
     *
     * @param string $sOxId Ordering ID (default null)
     *
     * @return bool
     */
    public function delete($sOxId = null)
    {
        $sOxId = $sOxId ?? $this->getId();

        // delete transaction-list too
        $transactionList = oxNew(TransactionList::class);
        $transactionList->getTransactionList($sOxId);
        if ($transactionList->count()) {
            /** @var Transaction $transaction */
            foreach ($transactionList as $transaction) {
                $transaction->delete();
            }
        }

        return parent::delete($sOxId);
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param int $dataType
     * @return false|void
     */
    public function setFieldData($fieldName, $value, $dataType = Field::T_TEXT)
    {
        return parent::_setFieldData($fieldName, $value, $dataType);
    }

    private function setTmpOrderStatus(string $unzerOrderId, string $status): void
    {
        $tmpOrder = oxNew(TmpOrder::class);
        $tmpData = $tmpOrder->getTmpOrderByUnzerId($unzerOrderId);
        if ($tmpOrder->load($tmpData['OXID'])) {
            $tmpOrder->assign(['status' => $status]);
            $tmpOrder->save();
        }
    }

    private function sendOrderConfirmationEmail(User $oUser, Basket $oBasket, UserPayment $oUserPayment): int
    {
        Registry::getSession()->setVariable('blDontCheckProductStockForUnzerMails', true);
        $iRet = $this->_sendOrderByEmail($oUser, $oBasket, $oUserPayment);
        Registry::getSession()->deleteVariable('blDontCheckProductStockForUnzerMails');
        return (int)$iRet;
    }
}
