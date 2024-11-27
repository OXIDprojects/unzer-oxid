<?php

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Service;

use OxidEsales\Eshop\Application\Model\Order;

interface TmpOrderServiceInterface
{
    public function getOrderBySessionOrderId(string $sessionOrderId): ?Order;
    public function getPaymentType(string $sessionOrderId, Order $order): string;
    public function getOrderCurrency(string $sessionOrderId, Order $order, string $paymentType): string;
    public function getCustomerType(?string $currency, string $paymentType): string;
    public function isPaylaterInvoice(string $paymentType): bool;
}
