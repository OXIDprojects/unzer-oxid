<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\ResultStatement;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidSolutionCatalysts\Unzer\Exception\UnzerException;
use OxidEsales\Eshop\Application\Model\Order;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsDate;
use OxidSolutionCatalysts\Unzer\Model\Transaction as TransactionModel;
use UnzerSDK\Constants\PaymentState;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\PaylaterInstallment;
use UnzerSDK\Resources\PaymentTypes\PaylaterInvoice;
use UnzerSDK\Resources\PaymentTypes\Paypal as UnzerSDKPaypal;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Shipment;
use UnzerSDK\Resources\PaymentTypes\Card as UnzerResourceCard;
use UnzerSDK\Resources\PaymentTypes\Paypal as UnzerResourcePaypal;

/**
 * TODO: Decrease count of dependencies to 13
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * TODO: Decrease overall complexity below 50
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Transaction
{
    use ServiceContainer;

    protected Context $context;

    protected UtilsDate $utilsDate;

    private array $paymentTypes = [];

    private array $transPaymentTypeIds = [
        'crd' => 'card',
        'ppl' => 'paypal',
        'sdd' => 'sepa'
    ];

    private array $transActionConst = [
        PaymentState::STATE_NAME_COMPLETED,
        PaymentState::STATE_NAME_CANCELED,
        PaymentState::STATE_NAME_CHARGEBACK,
        PaymentState::STATE_NAME_PENDING
    ];

    /**
     * @param Context $context
     * @param UtilsDate $utilsDate
     */
    public function __construct(
        Context $context,
        UtilsDate $utilsDate
    ) {
        $this->context = $context;
        $this->utilsDate = $utilsDate;
    }

    public function writeTransactionToDB(
        string $orderid,
        string $userId,
        ?Payment $unzerPayment,
        ?Shipment $unzerShipment = null
    ): bool {

        $oOrder = oxNew(Order::class);
        $oOrder->load($orderid);

        $params = [
            'oxorderid' => $orderid,
            'oxshopid' => $this->context->getCurrentShopId(),
            'oxuserid' => $userId,
            'oxactiondate' => date('Y-m-d H:i:s', $this->utilsDate->getTime()),
            'customertype' => $this->getCustomerTypeByOrder($oOrder)
        ];

        if ($unzerPayment) {
            $unzerPaymentData = $unzerShipment !== null ?
                $this->getUnzerShipmentData($unzerShipment) :
                $this->getUnzerPaymentData($unzerPayment);
            $params = array_merge($params, $unzerPaymentData);
        }

        if ($this->saveTransaction($params, $oOrder)) {
            $this->deleteInitOrder($params);

            // Fallback: set ShortID as OXTRANSID
            $shortId = $params['shortid'] ?? '';
            $oOrder->setUnzerTransId($shortId);

            return true;
        }

        return false;
    }

    /**
     * @param (int|mixed|string)[] $params
     *
     */
    public function deleteInitOrder(array $params): void
    {
        $transaction = $this->getNewTransactionObject();

        $oxid = $this->getInitOrderOxid($params);
        if ($transaction->load($oxid)) {
            $transaction->delete();
        }
    }

    /**
     * @param string $orderid
     * @param string $userId
     * @param \UnzerSDK\Resources\TransactionTypes\Cancellation|null $unzerCancel
     * @return bool
     * @throws \Exception
     */
    public function writeCancellationToDB(
        string $orderid,
        string $userId,
        ?Cancellation $unzerCancel,
        Order $oOrder
    ): bool {
        $unzerCancelReason = '';
        if ($unzerCancel !== null) {
            $unzerCancelReason = $unzerCancel->getReasonCode() ?? '';
        }

        $params = [
            'oxorderid' => $orderid,
            'oxshopid' => $this->context->getCurrentShopId(),
            'oxuserid' => $userId,
            'oxactiondate' => date('Y-m-d H:i:s', $this->utilsDate->getTime()),
            'cancelreason' => $unzerCancelReason,
            'customertype' => $this->getCustomerTypeByOrder($oOrder),
        ];

        if ($unzerCancel instanceof Cancellation) {
            $params = array_merge($params, $this->getUnzerCancelData($unzerCancel));
        }

        return $this->saveTransaction($params, $oOrder);
    }

    /**
     * @param string $orderid
     * @param string $userId
     * @param \UnzerSDK\Resources\TransactionTypes\Charge|null $unzerCharge
     * @throws \Exception
     * @return bool
     */
    public function writeChargeToDB(string $orderid, string $userId, ?Charge $unzerCharge, Order $oOrder): bool
    {
        $params = [
            'oxorderid' => $orderid,
            'oxshopid' => $this->context->getCurrentShopId(),
            'oxuserid' => $userId,
            'oxactiondate' => date('Y-m-d H:i:s', $this->utilsDate->getTime()),
            'customertype' => $this->getCustomerTypeByOrder($oOrder)
        ];

        if ($unzerCharge instanceof Charge) {
            $params = array_merge($params, $this->getUnzerChargeData($unzerCharge));
        }

        return $this->saveTransaction($params, $oOrder);
    }

    /**
     * @param array $params
     * @return string
     */
    protected function prepareTransactionOxid(array $params): string
    {
        unset($params['oxactiondate']);
        unset($params['serialized_basket']);
        unset($params['customertype']);

        /** @var string $jsonEncode */
        $jsonEncode = json_encode($params);
        return md5($jsonEncode);
    }

    protected function saveTransaction(array $params, Order $oOrder): bool
    {
        $transaction = $this->getNewTransactionObject();

        //check if metadata exists
        $params['metadata'] = $params['metadata'] ?? json_encode('', JSON_THROW_ON_ERROR);

        // building oxid from unique index columns
        // only write to DB if oxid doesn't exist to prevent multiple entries of the same transaction
        $oxid = $this->prepareTransactionOxid($params);
        if (!$transaction->load($oxid)) {
            if ($oOrder->getFieldData('oxtransstatus') === 'ABORTED') {
                $transaction->setTransStatus('aborted');
            }

            $transaction->assign($params);
            $transaction->setId($oxid);
            $transaction->save();

            return true;
        }

        return false;
    }

    protected function getInitOrderOxid(array $params): string
    {
        $params['oxaction'] = "init";
        return $this->prepareTransactionOxid($params);
    }

    /**
     * @throws UnzerApiException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getUnzerPaymentData(
        Payment $unzerPayment,
        ?AbstractTransactionType $transaction = null
    ): array {
        $oxaction = preg_replace(
            '/[^a-z]/',
            '',
            strtolower($unzerPayment->getStateName())
        );
        $params = [
            'amount'    => $this->getTransactionAmount($unzerPayment, $transaction),
            'remaining' => $unzerPayment->getAmount()->getRemaining(),
            'currency'  => $unzerPayment->getCurrency(),
            'typeid'    => $unzerPayment->getId(),
            'oxaction'  => $oxaction,
            'traceid'   => $unzerPayment->getTraceId()
        ];
        $savePayment = Registry::getSession()->getVariable('oscunzersavepayment');

        $paymentType = $unzerPayment->getPaymentType();
        $firstPaypalCall = Registry::getSession()->getVariable('oscunzersavepayment_paypal');

        if (
            ($savePayment && ($paymentType instanceof UnzerResourcePaypal && !$firstPaypalCall))
            || ($savePayment && $paymentType instanceof UnzerResourceCard)
        ) {
            $typeId = $paymentType->getId();
            $params['paymenttypeid'] = $typeId;
        }
        Registry::getSession()->setVariable('oscunzersavepayment_paypal', false);

        $initialTransaction = $unzerPayment->getInitialTransaction();
        $params['shortid'] = !is_null($initialTransaction) && !is_null($initialTransaction->getShortId()) ?
            $initialTransaction->getShortId() :
            Registry::getSession()->getVariable('ShortId');

        $metadata = $unzerPayment->getMetadata();
        if ($metadata instanceof Metadata) {
            $params['metadata'] = $metadata->jsonSerialize();
        }

        $unzerCustomer = $unzerPayment->getCustomer();
        if ($unzerCustomer instanceof Customer) {
            $params['customerid'] = $unzerCustomer->getId();
        }

        return $params;
    }

    protected function getUnzerChargeData(Charge $unzerCharge): array
    {
        $payment = $unzerCharge->getPayment();
        $customerId = $payment?->getCustomer()?->getId();
        $typeId = $payment?->getPaymentType()?->getId();

        return [
            'amount'            => $unzerCharge->getAmount(),
            'currency'          => $unzerCharge->getCurrency(),
            'typeid'            => $unzerCharge->getId(),
            'paymenttypeid'     => $typeId,
            'oxaction'          => 'charged',
            'customerid'        => $customerId,
            'traceid'           => $unzerCharge->getTraceId(),
            'shortid'           => $unzerCharge->getShortId(),
            'status'            => $this->getUzrStatus($unzerCharge),
        ];
    }

    protected function getUnzerCancelData(Cancellation $unzerCancel): array
    {
        $currency = '';
        $customerId = '';
        $payment = $unzerCancel->getPayment();
        if (is_object($payment)) {
            $currency = $payment->getCurrency();
            $customer = $payment->getCustomer();
            if (is_object($customer)) {
                $customerId = $customer->getId();
            }
        }
        return [
            'amount'     => $unzerCancel->getAmount(),
            'currency'   => $currency,
            'typeid'     => $unzerCancel->getId(),
            'oxaction'   => 'canceled',
            'customerid' => $customerId,
            'traceid'    => $unzerCancel->getTraceId(),
            'shortid'    => $unzerCancel->getShortId(),
            'status'     => $this->getUzrStatus($unzerCancel),
        ];
    }

    protected function getUnzerShipmentData(Shipment $unzerShipment): array
    {
        $currency = '';
        $customerId = '';
        $payment = $unzerShipment->getPayment();
        if (is_object($payment)) {
            $currency = $payment->getCurrency();
            $customer = $payment->getCustomer();
            if (is_object($customer)) {
                $customerId = $customer->getId();
            }
        }
        return [
            'amount'     => $unzerShipment->getAmount(),
            'currency'   => $currency,
            'fetchedAt'  => $unzerShipment->getFetchedAt(),
            'typeid'     => $unzerShipment->getId(),
            'oxaction'   => 'shipped',
            'customerid' => $customerId,
            'shortid'    => $unzerShipment->getShortId(),
            'traceid'    => $unzerShipment->getTraceId(),
            'metadata'   => json_encode(["InvoiceId" => $unzerShipment->getInvoiceId()])
        ];
    }

    protected function getNewTransactionObject(): TransactionModel
    {
        return oxNew(TransactionModel::class);
    }

    /**
     * @param string $paymentid
     * @return array|null
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getTransactionDataByPaymentId(string $paymentid): ?array
    {
        $result = null;

        if ($paymentid) {
            $queryBuilderFactory = $this->getServiceFromContainer(QueryBuilderFactoryInterface::class);
            $queryBuilder = $queryBuilderFactory->create();

            $query = $queryBuilder
                ->select(
                    'oxorderid',
                    'oxuserid'
                )
                ->from('oscunzertransaction')
                ->distinct()
                ->where($queryBuilder->expr()->eq('typeid', ':typeid'));

            $parameters = [
                ':typeid' => $paymentid,
            ];

            $queryResult = $query->setParameters($parameters)->execute();
            if ($queryResult instanceof ResultStatement && $queryResult->columnCount() >= 1) {
                $result = $queryResult->fetchAllAssociative();
            }
        }

        return $result;
    }

    /**
     * @param Cancellation|Charge $unzerObject
     *
     * @return null|string
     */
    protected static function getUzrStatus($unzerObject)
    {
        if ($unzerObject->isSuccess()) {
            return "success";
        }
        if ($unzerObject->isError()) {
            return "error";
        }
        if ($unzerObject->isPending()) {
            return "pending";
        }

        return null;
    }

    /**
     * @param string $orderid
     * @param bool $withoutCancel
     * @return string
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function getPaymentIdByOrderId(string $orderid, bool $withoutCancel = false): string
    {
        $transActionConst = $this->transActionConst;
        if ($withoutCancel) {
            $transActionConst = array_diff($transActionConst, [PaymentState::STATE_NAME_CANCELED]);
        }

        $result = '';

        $queryBuilderFactory = $this->getServiceFromContainer(QueryBuilderFactoryInterface::class);
        $queryBuilder = $queryBuilderFactory->create();

        $query = $queryBuilder
            ->select('typeid')
            ->from('oscunzertransaction')
            ->distinct()
            ->where($queryBuilder->expr()->eq('oxorderid', ':oxorderid'))
            ->andWhere($queryBuilder->expr()->in('oxaction', ':oxaction'))
            ->setMaxResults(1);

        $parameters = [
            ':oxorderid' => $orderid,
            ':oxaction'  => $transActionConst
        ];

        $parameterTypes = [
            ':oxorderid' => \PDO::PARAM_STR,
            ':oxaction'  => Connection::PARAM_STR_ARRAY // Specify array type
        ];

        $queryResult = $query->setParameters($parameters, $parameterTypes)->execute();
        if ($queryResult instanceof ResultStatement && $queryResult->columnCount()) {
            $result = $queryResult->fetchOne();
            $result = is_string($result) ? $result : '';
        }

        return $result;
    }

    /**
     * @param string $orderid
     * @return string
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getTransactionIdByOrderId(string $orderid): string
    {
        $result = '';

        $queryBuilderFactory = $this->getServiceFromContainer(QueryBuilderFactoryInterface::class);
        $queryBuilder = $queryBuilderFactory->create();

        $query = $queryBuilder
            ->select(
                'oxid'
            )
            ->from('oscunzertransaction')
            ->where($queryBuilder->expr()->eq('oxorderid', ':oxorderid'))
            ->andWhere($queryBuilder->expr()->in('oxaction', ':oxaction'))
            ->orderBy('oxtimestamp', 'desc')
            ->setMaxResults(1);

        $parameters = [
            ':oxorderid' => $orderid,
            ':oxaction'  => $this->transActionConst
        ];

        $parameterTypes = [
            ':oxorderid' => \PDO::PARAM_STR,
            ':oxaction'  => Connection::PARAM_STR_ARRAY // Specify array type
        ];

        $queryResult = $query->setParameters($parameters, $parameterTypes)->execute();
        if ($queryResult instanceof ResultStatement && $queryResult->columnCount()) {
            $result = $queryResult->fetchOne();
            $result = is_string($result) ? $result : '';
        }

        return $result;
    }

    /**
     * @param string $orderId
     * @return array
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getCustomerTypeAndCurrencyFromTransactionByOrderId(string $orderId): array
    {
        $transaction = oxNew(TransactionModel::class);
        $transactionId = $this->getTransactionIdByOrderId($orderId);
        $transaction->load($transactionId);

        return [
            'customertype' => $transaction->getFieldData('customertype') ?? '',
            'currency' => $transaction->getFieldData('currency') ?? '',
        ];
    }

    /**
     * @param string $typeid
     * @return bool
     * @throws DatabaseConnectionException
     */
    public function isValidTransactionTypeId(string $typeid): bool
    {
        $result = false;

        $queryBuilderFactory = $this->getServiceFromContainer(QueryBuilderFactoryInterface::class);
        $queryBuilder = $queryBuilderFactory->create();

        $query = $queryBuilder
            ->select(
                'oxid'
            )
            ->from('oscunzertransaction')
            ->where($queryBuilder->expr()->eq('typeid', ':typeid'))
            ->distinct()
            ->setMaxResults(1);

        $parameters = [
            ':typeid' => $typeid
        ];

        $queryResult = $query->setParameters($parameters)->execute();
        if ($queryResult instanceof ResultStatement && $queryResult->columnCount()) {
            $result = true;
        }

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    public function getTransactionIds(?User $user = null): array
    {
        $result = [];

        // check user Model
        if (!$user) {
            return $result;
        }

        // check user Id
        $userId = $user->getId() ?: null;
        if (!$userId) {
            return $result;
        }

        $queryBuilderFactory = $this->getServiceFromContainer(QueryBuilderFactoryInterface::class);
        $queryBuilder = $queryBuilderFactory->create();

        $query = $queryBuilder
            ->select(
                'ot.oxid',
                'ot.oxuserid',
                'ot.paymenttypeid',
                'ot.currency',
                'ot.customertype',
                'o.oxpaymenttype'
            )
            ->from('oscunzertransaction', 'ot')
            ->leftJoin('ot', 'oxorder', 'o', 'ot.oxorderid = o.oxid')
            ->where($queryBuilder->expr()->eq('ot.oxuserid', ':oxuserid'))
            ->andWhere($queryBuilder->expr()->isNotNull('ot.paymenttypeid'))
            ->groupBy('ot.paymenttypeid');

        $parameters = [
            ':oxuserid' => $userId,
        ];

        $queryResult = $query->setParameters($parameters)->execute();
        $rows = [];
        if ($queryResult instanceof ResultStatement && $queryResult->columnCount() >= 1) {
            $rows = $queryResult->fetchAllAssociative();
        }

        return $rows;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getSavedPaymentsForUser(?User $user, array $ids, bool $cache): array
    {
        if ($cache && count($this->paymentTypes) > 0) {
            return $this->paymentTypes;
        }

        $tmpArr = [];
        foreach ($ids as $typeData) {
            $paymentTypes = null;
            $paymentTypeId = $typeData['paymenttypeid'] ?: '';
            if ($paymentTypeId !== '') {
                $paymentTypes = $this->setPaymentTypes(
                    $user,
                    $paymentTypeId,
                    $typeData['currency'] ?: '',
                    $typeData['customertype'] ?: '',
                    $paymentTypeId
                );
            }

            if ($paymentTypes) {
                foreach ($paymentTypes as $key => $paymentType) {
                    $tmpArr[$key][] = $paymentType;
                }
            }
        }

        $result = [];
        foreach ($tmpArr as $key => $paymentType) {
            foreach ($paymentType as $paymentDetails) {
                $keyDetail = array_key_first($paymentDetails);
                $result[$key][$keyDetail] = $paymentDetails[$keyDetail];
            }
        }

        $this->paymentTypes = $result;
        return $this->paymentTypes;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function setPaymentTypes(
        ?User $user,
        string $paymentId,
        string $currency,
        string $customerType,
        string $paymentTypeId
    ): ?array {
        $result = [];

        try {
            $UnzerSdk = $this->getServiceFromContainer(UnzerSDKLoader::class);
            $unzerSDK = $UnzerSdk->getUnzerSDK(
                $paymentId,
                $currency,
                $customerType
            );
            $paymentType = $unzerSDK->fetchPaymentType($paymentTypeId);
        } catch (UnzerException | UnzerApiException $e) {
            $userId = $user ? $user->getId() : 'unknown';
            $logEntry = sprintf(
                'The incorrect data used to initialize the SDK ' .
                'comes from the transactions of the user: "%s"',
                $userId
            );
            $logger = $this->getServiceFromContainer(DebugHandler::class);
            $logger->log($logEntry);
            return null;
        }

        foreach ($this->transPaymentTypeIds as $unzerId => $oxVar) {
            if (strpos($paymentTypeId, $unzerId)) {
                if ($paymentType instanceof UnzerResourceCard && method_exists($paymentType, 'getBrand')) {
                    $sensitiveData = $paymentType->expose();
                    $sensitiveData = is_array($sensitiveData) ? $sensitiveData : get_object_vars($sensitiveData);
                    $result[$oxVar][$paymentTypeId] = array_merge(
                        $sensitiveData,
                        ['brand' => $paymentType->getBrand()]
                    );
                } elseif ($paymentType instanceof UnzerResourcePaypal && !empty($paymentType->getEmail())) {
                    $result[$oxVar][$paymentTypeId] = $paymentType->expose();
                }
            }
        }
        return $result;
    }

    private function getTransactionAmount(
        Payment $unzerPayment,
        ?AbstractTransactionType $transaction = null
    ): ?float {
        return $transaction instanceof Cancellation ?
            $transaction->getAmount() : $unzerPayment->getAmount()->getTotal();
    }

    private function getCustomerTypeByOrder(Order $oOrder): string
    {
        $delCompany = $oOrder->getFieldData('oxdelcompany') ?? '';
        $billCompany = $oOrder->getFieldData('oxbillcompany') ?? '';
        $customerType = 'B2C';
        if (!empty($delCompany) || !empty($billCompany)) {
            $customerType = 'B2B';
        }

        return $customerType;
    }
}
