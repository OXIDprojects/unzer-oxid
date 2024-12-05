<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

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
     * @return int|bool
     * @throws \UnzerSDK\Exceptions\UnzerApiException
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function finalizeUnzerOrderAfterRedirect(
        Basket $oBasket,
        User $oUser
    ): bool|int {
        $orderId = Registry::getSession()->getVariable('sess_challenge');
        $orderId = is_string($orderId) ? $orderId : '';
        $iRet = self::ORDER_STATE_PAYMENTERROR;

        if (!$orderId) {
            return $iRet;
        }

        if ($this->checkOrderExist($orderId)) {
            $logger = $this->getServiceFromContainer(DebugHandler::class);
            $logger->log('finalizeUnzerOrderAfterRedirect: Order already exists, no need to save again: ' . $orderId);
            return self::ORDER_STATE_ORDEREXISTS;
        }

        $this->setId($orderId);
        $paymentService = $this->getServiceFromContainer(PaymentService::class);
        $unzerService = $this->getServiceFromContainer(Unzer::class);
        $unzerPaymentStatus = $paymentService->getUnzerPaymentStatus();

        $this->setUser($oUser);

        $this->assignUserInformation($oUser);
        $this->loadFromBasket($oBasket);

        $oUserPayment = $this->setPayment($oBasket->getPaymentId());
        $this->setFolder();
        $this->save();

        if (!$this->getFieldData('oxordernr')) {
            $this->setNumber();
        }

        // setUnzerOrderId
        $unzerOrderId = (string)$paymentService->getUnzerOrderId();
        $this->setUnzerOrderNr((string)$unzerOrderId);
        $unzerService->resetUnzerOrderId();

        Registry::getSession()->deleteVariable('ordrem');
        $this->updateOrderDate();
        $oBasket->setOrderId($orderId);
        $this->updateWishlist($oBasket->getContents(), $oUser);
        $this->updateNoticeList($oBasket->getContents(), $oUser);
        $this->markVouchers($oBasket, $oUser);
        $this->setOrderStatus($unzerPaymentStatus);

        if ($paymentService->isPrepayment() || $paymentService->isInvoice()) {
            $iRet = $this->sendOrderConfirmationEmail($oUser, $oBasket, $oUserPayment);
        } else {
            if ($unzerPaymentStatus === 'OK') {
                $this->markUnzerOrderAsPaid();
                $this->setTmpOrderStatus($unzerOrderId, 'FINISHED');
                $iRet = $this->sendOrderConfirmationEmail($oUser, $oBasket, $oUserPayment);
            } else {
                if ($unzerPaymentStatus !== PaymentService::STATUS_NOT_FINISHED) {
                    Registry::getSession()->setVariable('orderCancellationProcessed', true);
                    $iRet = 1; //TODO: not sure if this is correct - this is hardcoded for the Paypal cancellaction
                }
                $this->setOrderStatus($unzerPaymentStatus); //ERROR if paypal
                $this->setTmpOrderStatus($unzerOrderId, $unzerPaymentStatus);
            }
        }

        $this->initWriteTransactionToDB(
            $paymentService->getSessionUnzerPayment(false)
        );

        return $iRet;
    }

    public function createTmpOrder(
        Basket $oBasket,
        User $oUser,
        string $unzerOrderId
    ): bool|int {
        $orderId = Registry::getSession()->getVariable('sess_challenge');
        $orderId = is_string($orderId) ? $orderId : '';
        $iRet = self::ORDER_STATE_PAYMENTERROR;

        if (!$orderId) {
            return $iRet;
        }

        if ($this->checkOrderExist($orderId)) {
            // we might use this later, this means that somebody clicked like mad on order button
            return self::ORDER_STATE_ORDEREXISTS;
        }

        $this->setId($orderId);

        // copies user info
        $this->setUser($oUser);
        $this->assignUserInformation($oUser);

        // copies basket info
        $this->loadFromBasket($oBasket);

        $oUserPayment = $this->setPayment($oBasket->getPaymentId());
        $this->_oPayment = $oUserPayment;

        // set folder information, order is new
        $this->setFolder();

        // setUnzerOrderId
        $this->setUnzerOrderNr($unzerOrderId);

        $blRet = $this->executePayment($oBasket, $oUserPayment);
        if ($blRet !== true) {
            return $blRet;
        }
        // deleting remark info only when order is finished

        //#4005: Order creation time is not updated when order processing is complete
        $this->updateOrderDate();

        $oBasket->setOrderId($orderId);

        // updating wish lists
        $this->updateWishlist($oBasket->getContents(), $oUser);

        // updating users notice list
        $this->updateNoticeList($oBasket->getContents(), $oUser);

        // marking vouchers as used and sets them to $this->_aVoucherList (will be used in order email)
        $this->markVouchers($oBasket, $oUser);

        $this->setOrderStatus('NOT_FINISHED');
        $tmpOrder = oxNew(TmpOrder::class);
        $tmpOrder->saveTmpOrder($this);
        Registry::getSession()->setVariable('oxOrderIdOfTmpOrder', $this->getId());

        return $iRet;
    }

    public function getUnzerOrderNr(): string
    {
        $value = $this->getFieldData('oxunzerordernr');
        return is_string($value) ? $value : '';
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
     * @throws Exception
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function finalizeTmpOrder(\UnzerSDK\Resources\Payment $unzerPayment, bool $error = false): void
    {
        // set order in any case as Paid
        $this->markUnzerOrderAsPaid();

        if ($error === true) {
            $this->setOrderStatus('ERROR');
        } else {
            switch ($unzerPayment->getState()) {
                case PaymentState::STATE_PENDING:
                    $this->setOrderStatus('NOT_FINISHED');
                    break;
                case PaymentState::STATE_CANCELED:
                    $this->cancelOrder();
                    break;
            }
        }

        if (!$this->getFieldData('oxordernr')) {
            $this->setNumber();
        }

        $this->initWriteTransactionToDB($unzerPayment);
        $this->save();
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
        $this->setOrderStatus('OK');
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
     * @throws UnzerApiException
     * @throws Exception
     */
    public function initWriteTransactionToDB(\UnzerSDK\Resources\Payment|null $unzerPayment = null): void
    {
        /** @var string $oxpaymenttype */
        $oxpaymenttype = $this->getFieldData('oxpaymenttype');
        if ($oxpaymenttype !== null && str_contains($oxpaymenttype, "oscunzer")) {
            $transactionService = $this->getServiceFromContainer(TransactionService::class);

            $unzerPayment = $unzerPayment instanceof \UnzerSDK\Resources\Payment ?
                $unzerPayment :
                $this->getServiceFromContainer(PaymentService::class)->getSessionUnzerPayment(true);

            $transactionService->writeTransactionToDB(
                $this->getId(),
                $this->getOrderUser()->getId() ?: '',
                $unzerPayment
            );
        }
    }

    /**
     * @return mixed
     */
    public function getUnzerInvoiceNr()
    {
        /** @var int $number */
        $number = $this->getFieldData('oxinvoicenr') !== 0 ?
            $this->getFieldData('oxinvoicenr') :
            $this->getFieldData('oxordernr');
        return $number;
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


    public function setFieldData($fieldName, $value, $dataType = Field::T_TEXT): void
    {
        parent::setFieldData($fieldName, $value, $dataType);
    }

    private function setTmpOrderStatus(string $unzerOrderId, string $status): void
    {
        $tmpOrder = oxNew(TmpOrder::class);
        $tmpData = $tmpOrder->getTmpOrderByUnzerId($unzerOrderId);
        if ($tmpOrder->load($tmpData['oxid'])) {
            $tmpOrder->assign(['status' => $status]);
            $tmpOrder->save();
        }
    }

    private function sendOrderConfirmationEmail(User $oUser, Basket $oBasket, UserPayment $oUserPayment): int
    {
        Registry::getSession()->setVariable('blDontCheckProductStockForUnzerMails', true);
        $iRet = $this->sendOrderByEmail($oUser, $oBasket, $oUserPayment);
        Registry::getSession()->deleteVariable('blDontCheckProductStockForUnzerMails');
        return (int)$iRet;
    }
}
