<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Model;

use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\Eshop\Core\Registry;

class TransactionList extends ListModel
{
    /**
    * List Object class name
    *
    * @var string
    */
    protected $_sObjectsInListName = 'OxidSolutionCatalysts\Unzer\Model\Transaction'; // phpcs:ignore

    public function getTransactionList(string $orderId): void
    {
        $oListObject = $this->getBaseObject();
        $sFieldList = $oListObject->getSelectFields();

        $shopId = Registry::getConfig()->getShopId();

        $params = [':shopid' => $shopId, ':orderid' => $orderId];

        $query = "select $sFieldList from " . $oListObject->getViewName() . "
            where oxshopid = :shopid and oxorderid = :orderid order by {$oListObject->getViewName()}.OXTIMESTAMP asc";

        $this->selectString($query, $params);
    }

    public function getTransactionListByTraceId(string $traceId): void
    {
        $oListObject = $this->getBaseObject();
        $sFieldList = $oListObject->getSelectFields();

        $shopId = Registry::getConfig()->getShopId();

        $params = [':shopid' => $shopId, ':traceid' => $traceId];

        $query = "select $sFieldList from " . $oListObject->getViewName() . "
            where oxshopid = :shopid 
                and traceid = :traceid 
                and oxaction = 'canceled'
            order by {$oListObject->getViewName()}.OXTIMESTAMP asc";

        $this->selectString($query, $params);
    }
}
