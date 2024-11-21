<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Service;

use Exception;
use JsonException;
use OxidEsales\Eshop\Application\Model\User;
use OxidSolutionCatalysts\Unzer\Exception\UnzerException;
use OxidEsales\Eshop\Application\Model\Order;
use OxidSolutionCatalysts\Unzer\Traits\Request;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsDate;
use OxidSolutionCatalysts\Unzer\Model\Transaction as TransactionModel;
use UnzerSDK\Constants\PaymentState;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Card as UnzerResourceCard;
use UnzerSDK\Resources\PaymentTypes\PaylaterInstallment;
use UnzerSDK\Resources\PaymentTypes\PaylaterInvoice;
use UnzerSDK\Resources\PaymentTypes\Paypal as UnzerResourcePaypal;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit as UnzerResourceSepa;
use UnzerSDK\Resources\TransactionTypes\AbstractTransactionType;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Shipment;

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
    use Request;

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

    /**
     * @param string $orderid
     * @param string $userId
     * @param Payment|null $unzerPayment
     * @param Shipment|null $unzerShipment
     * @return bool
     * @throws Exception
    */
    public function writeTransactionToDB(
        string $orderid,
        string $userId,
        ?Payment $unzerPayment,
        ?Shipment $unzerShipment = null,
        ?AbstractTransactionType $transaction = null
    ): bool {

        $oOrder = oxNew(Order::class);
        $oOrder->load($orderid);

        $params = $this->getBasicSaveParameters($orderid, $userId);

        if ($unzerPayment) {
            $this->extendSaveParameters(
                $params,
                $unzerPayment,
                $unzerShipment,
                $transaction
            );

            // for PaylaterInvoice, store the customer type
            if (
                $unzerPayment->getPaymentType() instanceof PaylaterInvoice ||
                $unzerPayment->getPaymentType() instanceof PaylaterInstallment
            ) {
                $delCompany = $oOrder->getFieldData('oxdelcompany') ?? '';
                $billCompany = $oOrder->getFieldData('oxbillcompany') ?? '';
                $params['customertype'] = 'B2C';
                if (!empty($delCompany) || !empty($billCompany)) {
                    $params['customertype'] = 'B2B';
                }
            }
        }

        if ($this->saveTransaction($params)) {
            $this->deleteInitOrder($params);

            if ($oOrder->isLoaded()) {
                // Fallback: set ShortID as OXTRANSID
                $shortId = $params['shortid'] ?? '';
                $oOrder->setUnzerTransId($shortId);
            }

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
     * @param Cancellation|null $unzerCancel
     * @return bool
     * @throws Exception
     */
    public function writeCancellationToDB(
        string $orderid,
        string $userId,
        ?Cancellation $unzerCancel
    ): bool {
        $unzerCancelReason = '';
        if ($unzerCancel !== null) {
            $unzerCancelReason = $unzerCancel->getReasonCode() ?? '';
        }

        $customerData = $this->getCustomerTypeAndCurrencyByOrderId($orderid);

        $params = [
            'oxorderid' => $orderid,
            'oxshopid' => $this->context->getCurrentShopId(),
            'oxuserid' => $userId,
            'oxactiondate' => date('Y-m-d H:i:s', $this->utilsDate->getTime()),
            'cancelreason' => $unzerCancelReason,
            'customertype' => $customerData['customertype'],
        ];

        if ($unzerCancel) {
            $params = array_merge($params, $this->getUnzerCancelData($unzerCancel));
        }

        return $this->saveTransaction($params);
    }

    /**
     * @param string $orderid
     * @param string $userId
     * @param Charge|null $unzerCharge
     * @throws Exception
     * @return bool
     */
    public function writeChargeToDB(string $orderid, string $userId, ?Charge $unzerCharge): bool
    {
        $params = [
            'oxorderid' => $orderid,
            'oxshopid' => $this->context->getCurrentShopId(),
            'oxuserid' => $userId,
            'oxactiondate' => date('Y-m-d H:i:s', $this->utilsDate->getTime()),
        ];

        if ($unzerCharge instanceof Charge) {
            $params = array_merge($params, $this->getUnzerChargeData($unzerCharge));
        }

        return $this->saveTransaction($params);
    }

    /**
     * @param array $params
     * @return string
     * @throws JsonException
     */
    protected function prepareTransactionOxid(array $params): string
    {
        unset($params['oxactiondate'], $params['serialized_basket'], $params['customertype']);

        /** @var string $jsonEncode */
        $jsonEncode = json_encode($params, JSON_THROW_ON_ERROR);
        return md5($jsonEncode);
    }

    /**
     * @throws JsonException
     */
    protected function saveTransaction(array $params): bool
    {
        $result = false;
        $transaction = $this->getNewTransactionObject();

        $params['metadata'] = $params['metadata'] ?? json_encode('', JSON_THROW_ON_ERROR);

        $oxid = $this->prepareTransactionOxid($params);

//        if (!$transaction->load($oxid) && $this->canSaveTransaction($transaction, $params)) {
        if (!$transaction->load($oxid)) {
            $transaction->assign($params);
            $transaction->setId($oxid);
            $transaction->save();
            $result = true;
        }

        return $result;
    }

    /**
     * @param array $params
     * @return string
     * @throws JsonException
     */
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

        $paymentType = $unzerPayment->getPaymentType();
        $savePayment = $this->getServiceFromContainer(RequestService::class)
            ->isSavePaymentSelectedByUserInRequest();

        if (
            $savePayment
            && (
                $paymentType instanceof UnzerResourcePaypal
                || $paymentType instanceof UnzerResourceCard
                || $paymentType instanceof UnzerResourceSepa
            )
        ) {
            $typeId = $paymentType->getId();
            $params['paymenttypeid'] = $typeId;
        }

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
        $customerId = '';
        $typeId = '';
        /** @var Payment $payment */
        $payment = $unzerCharge->getPayment();
        if ($payment) {
            /** @var Customer $customer */
            $customer = $payment->getCustomer();
            if ($customer) {
                $customerId = $customer->getId();
            }
            $paymentType = $payment->getPaymentType();
            $typeId = $paymentType ? $paymentType->getId() : '';
        }

        return [
            'amount'        => $unzerCharge->getAmount(),
            'currency'      => $unzerCharge->getCurrency(),
            'typeid'        => $unzerCharge->getId(),
            'paymenttypeid' => $typeId,
            'oxaction'      => 'charged',
            'customerid'    => $customerId,
            'traceid'       => $unzerCharge->getTraceId(),
            'shortid'       => $unzerCharge->getShortId(),
            'status'        => self::getUzrStatus($unzerCharge),
        ];
    }

    protected function getUnzerCancelData(Cancellation $unzerCancel): array
    {
        $currency = '';
        $customerId = '';
        $metadata = null;
        $payment = $unzerCancel->getPayment();
        if (is_object($payment)) {
            $currency = $payment->getCurrency();
            $customer = $payment->getCustomer();
            if (is_object($customer)) {
                $customerId = $customer->getId();
            }
            $metadata = $payment->getMetadata();
        }

        $metadataJson = '';
        if ($metadata instanceof Metadata) {
            $metadataJson = $metadata->jsonSerialize();
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
            'metadata'   => $metadataJson,
        ];
    }

    protected function getUnzerShipmentData(Shipment $unzerShipment, Payment $unzerPayment): array
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
        $params = [
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

        $unzerCustomer = $unzerPayment->getCustomer();
        if ($unzerCustomer instanceof Customer) {
            $params['customerid'] = $unzerCustomer->getId();
        }

        return $params;
    }

    /**
     * @return TransactionModel
     */

    protected function getNewTransactionObject(): TransactionModel
    {
        return oxNew(TransactionModel::class);
    }

    /**
     * @return array|false
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getTransactionDataByPaymentId(string $paymentid)
    {
        if ($paymentid) {
            return DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getAll(
                "SELECT DISTINCT OXORDERID, OXUSERID FROM oscunzertransaction WHERE TYPEID=?",
                [$paymentid]
            );
        }

        return false;
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
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function getPaymentIdByOrderId(string $orderid, bool $withoutCancel = false): string
    {
        $result = '';
        if ($orderid) {
            $result = DatabaseProvider::getDb()->getOne(
                "SELECT DISTINCT TYPEID FROM oscunzertransaction
                WHERE OXORDERID=? AND OXACTION IN (" . $this->prepareTransActionConstForSql($withoutCancel) . ")",
                [$orderid]
            );
            $result = is_string($result) ? $result : '';
        }

        return $result;
    }

    /**
     * @param string $orderid
     * @return string
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getTransactionIdByOrderId(string $orderid): string
    {
        $result = '';

        if ($orderid) {
            $result = DatabaseProvider::getDb()->getOne(
                "SELECT OXID FROM oscunzertransaction
                WHERE OXORDERID=? AND OXACTION IN (" . $this->prepareTransActionConstForSql() . ")
                ORDER BY OXTIMESTAMP DESC LIMIT 1",
                [$orderid]
            );
            $result = is_string($result) ? $result : '';
        }

        return $result;
    }

    /**
     * @param string $orderid
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function getCustomerTypeAndCurrencyByOrderId($orderid): array
    {
        $transaction = oxNew(TransactionModel::class);
        $transactionId = $this->getTransactionIdByOrderId($orderid);
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
    public function isValidTransactionTypeId($typeid): bool
    {
        if (
            DatabaseProvider::getDb()->getOne(
                "SELECT DISTINCT TYPEID FROM oscunzertransaction WHERE TYPEID=? ",
                [$typeid]
            )
        ) {
            return true;
        }
        return false;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
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

        $oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        if ($oDB) {
            $result = $oDB->getAll(
                "SELECT transaction.OXID, transaction.PAYMENTTYPEID,
                    transaction.CURRENCY, transaction.CUSTOMERTYPE,
                    oxorder.OXPAYMENTTYPE, transaction.OXACTIONDATE
                FROM oscunzertransaction AS transaction
                LEFT JOIN oxorder ON transaction.oxorderid = oxorder.OXID
                INNER JOIN (
                    SELECT PAYMENTTYPEID, MAX(OXACTIONDATE) AS LatestActionDate
                    FROM oscunzertransaction
                    WHERE OXUSERID = :oxuserid AND PAYMENTTYPEID IS NOT NULL
                    GROUP BY PAYMENTTYPEID
                ) AS latest_transaction 
                    ON transaction.PAYMENTTYPEID = latest_transaction.PAYMENTTYPEID 
                        AND transaction.OXACTIONDATE = latest_transaction.LatestActionDate
                WHERE transaction.OXUSERID = :oxuserid;",
                [':oxuserid' => $userId]
            );
        }
        return $result;
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
            $paymentTypeId = $typeData['PAYMENTTYPEID'] ?: '';
            if ($paymentTypeId) {
                $paymentTypes = $this->setPaymentTypes(
                    $user,
                    $typeData['PAYMENTTYPEID'] ?: '',
                    $typeData['CURRENCY'] ?: '',
                    $typeData['CUSTOMERTYPE'] ?: '',
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
                $result[$oxVar][$paymentTypeId] = $paymentType->expose();
            }
        }
        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function prepareTransActionConstForSql(bool $withoutCancel = false): string
    {
        $transActionConst = $this->transActionConst;
        if ($withoutCancel) {
            $transActionConst = array_diff($transActionConst, [PaymentState::STATE_NAME_CANCELED]);
        }
        return implode(',', DatabaseProvider::getDb()->quoteArray($transActionConst));
    }

    private function getTransactionAmount(
        Payment $unzerPayment,
        ?AbstractTransactionType $transaction = null
    ): ?float {
        return $transaction instanceof Cancellation ?
            $transaction->getAmount() : $unzerPayment->getAmount()->getTotal();
    }

    private function getBasicSaveParameters(string $orderId, string $userId): array
    {
        $customerData = $this->getCustomerTypeAndCurrencyByOrderId($orderId);
        return [
            'oxorderid' => $orderId,
            'oxshopid' => $this->context->getCurrentShopId(),
            'oxuserid' => $userId,
            'oxactiondate' => date('Y-m-d H:i:s', $this->utilsDate->getTime()),
            'customertype' => $customerData['customertype'],
        ];
    }

    private function extendSaveParameters(
        array &$parameters,
        Payment $unzerPayment,
        ?Shipment $unzerShipment = null,
        ?AbstractTransactionType $transaction = null
    ): void {
        $unzerPaymentData = !is_null($unzerShipment) ?
            $this->getUnzerShipmentData($unzerShipment, $unzerPayment) :
            $this->getUnzerPaymentData($unzerPayment, $transaction);
        $parameters = array_merge($parameters, $unzerPaymentData);

        $parameters = array_merge(
            $parameters,
            $this->getServiceFromContainer(SavedPaymentSaveService::class)
                ->getTransactionParameters($unzerPayment)
        );
    }
}
