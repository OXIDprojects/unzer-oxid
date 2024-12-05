<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Service;

use OxidEsales\Eshop\Application\Model\Order;
use OxidSolutionCatalysts\Unzer\Model\TmpOrder;
use OxidSolutionCatalysts\Unzer\Traits\Request;

class TmpOrderService implements TmpOrderServiceInterface
{
    use Request;

    private TmpOrder $tmpOrder;
    private ?Order $order;

    public function __construct()
    {
        $this->tmpOrder = oxNew(TmpOrder::class);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getOrderBySessionOrderId(string $sessionOrderId): Order
    {
        $order = oxNew(Order::class);
        if ($order->load($sessionOrderId)) {
            $this->order = $order;
            return $order;
        }

        $order = $this->tmpOrder->getTmpOrderByOxOrderId($sessionOrderId);
        if ($order !== null) {
            $this->order = $order;
            return $order;
        }

        return oxNew(Order::class);
    }

    public function getPaymentType(string $sessionOrderId, Order $order): string
    {
        $paymentType = $order->getFieldData('oxpaymenttype');

        if (empty($paymentType)) {
            $paymentType = $order->oxorder__oxpaymenttype->value ?? '';
        }

        if (is_string($paymentType) && !empty($paymentType)) {
            return $paymentType;
        }

        return  '';
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getOrderCurrency(string $sessionOrderId, Order $order, string $paymentType): string
    {
        /** @var string $currency */
        $currency = $order->getFieldData('oxcurrency') ?? '';

        if ($this->isPaylaterInvoice($paymentType)) {
            $order = $this->tmpOrder->getTmpOrderByOxOrderId($sessionOrderId);
            if ($order !== null) {
                /** @var \stdClass {name: string} $orderCurrency */
                $orderCurencyStdCls = $order->getOrderCurrency();
                $currency = $orderCurencyStdCls->name;
            }
        }

        return $currency;
    }

    public function getCustomerType(?string $currency, string $paymentType): string
    {
        $customerType = 'B2C';

        if (($currency !== null) && $this->isPaylaterInvoice($paymentType)) {
            $customerInRequest = $this->getUnzerStringRequestParameter('unzer_customer_type');
            if ($customerInRequest !== 'B2C' && !empty($customerInRequest)) {
                return 'B2B';
            }

            if (!empty($this->order->oxorder__oxbillcompany)) {
                $billingCompany = $this->order->oxorder__oxbillcompany->value;
            }

            if (!empty($this->order->oxorder__oxdelcompany)) {
                $shippingCompany = $this->order->oxorder__oxdelcompany->value;
            }

            if (!empty($billingCompany) || !empty($shippingCompany)) {
                $customerType = 'B2B';
            }
        }

        return $customerType;
    }

    public function isPaylaterInvoice(string $paymentType): bool
    {
        return in_array($paymentType, [
            \OxidSolutionCatalysts\Unzer\Core\UnzerDefinitions::INVOICE_UNZER_PAYMENT_ID,
            \OxidSolutionCatalysts\Unzer\Core\UnzerDefinitions::INSTALLMENT_UNZER_PAYLATER_PAYMENT_ID,
            \OxidSolutionCatalysts\Unzer\Core\UnzerDefinitions::INSTALLMENT_UNZER_PAYMENT_ID,
        ], true);
    }
}
