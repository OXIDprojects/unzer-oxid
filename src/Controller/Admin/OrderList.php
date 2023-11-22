<?php

namespace OxidSolutionCatalysts\Unzer\Controller\Admin;

use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\StandardException;
use OxidEsales\Eshop\Core\Registry;
use OxidSolutionCatalysts\Unzer\Model\Payment;
use OxidSolutionCatalysts\Unzer\Service\Payment as PaymentService;
use OxidSolutionCatalysts\Unzer\Model\Order;
use OxidSolutionCatalysts\Unzer\Traits\ServiceContainer;
use UnzerSDK\Exceptions\UnzerApiException;

class OrderList extends OrderList_parent
{
    /**
     * @param $queries
     * @param $queryForAppending
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function prepareWhereQuery($queries, $queryForAppending)
    {
        $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $config = \OxidEsales\Eshop\Core\Registry::getConfig();
        $folders = $config->getConfigParam('aOrderfolder');
        $folder = Registry::getRequest()->getRequestEscapedParameter('folder');

        $query = $this->prepareOrderListQuery($queries, $queryForAppending);
        if ($folder && $folder != '-1') {
            $query .= " and ( oxorder.oxfolder = " . $database->quote($folder) . " )";
        } elseif (!$folder && is_array($folders)) {
            $folderNames = array_keys($folders);
            $query .= " and ( oxorder.oxfolder = " . $database->quote($folderNames[0]) . " )";
        }

        $query .= " and oxorder.oxshopid = '" . \OxidEsales\Eshop\Core\Registry::getConfig()->getShopId() . "'";

        return $query;
    }

    /**
     * @param array $whereQuery
     * @param string $filterQuery
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return string
     * @throws DatabaseConnectionException
     */
    protected function prepareOrderListQuery(array $whereQuery, string $filterQuery): string
    {
        if (is_array($whereQuery) && count($whereQuery)) {
            $myUtilsString = \OxidEsales\Eshop\Core\Registry::getUtilsString();
            foreach ($whereQuery as $identifierName => $fieldValue) {
                //passing oxunzerordernr because it will be combined with oxordernr
                if ("oxorder.oxunzerordernr" === $identifierName) {
                    continue;
                }
                $fieldValue = trim($fieldValue);
                //check if this is search string (contains % sign at beginning and end of string)
                $isSearchValue = $this->isSearchValue($fieldValue);
                //removing % symbols
                $fieldValue = $this->processFilter($fieldValue);
                if (strlen($fieldValue)) {
                    $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
                    $values = explode(' ', $fieldValue);
                    //for each search field using AND action
                    $queryBoolAction = ' and (';

                    //oxordernr is combined with oxunzerordernr
                    if ("oxorder.oxordernr" === $identifierName) {
                        $oxOrderNr = $database->quoteIdentifier("oxorder.oxordernr");
                        $oxUnzerOrderNr = $database->quoteIdentifier("oxorder.oxunzerordernr");
                        $orderNrQuery = [];
                        foreach ($values as $value) {
                            $orderNrQuery[] = "({$oxOrderNr} like '{$value}'"
                                . " or {$oxUnzerOrderNr} like '{$value}')";
                        }
                        $filterQuery .= "and (" . implode(" or ", $orderNrQuery) . ")";

                        continue;
                    }

                    foreach ($values as $value) {
                        // trying to search spec chars in search value
                        // if found, add cleaned search value to search sql
                        $uml = $myUtilsString->prepareStrForSearch($value);
                        if ($uml) {
                            $queryBoolAction .= '(';
                        }
                        $quotedIdentifierName = $database->quoteIdentifier($identifierName);
                        $filterQuery .= " {$queryBoolAction} {$quotedIdentifierName} ";
                        //for search in same field for different values using AND
                        $queryBoolAction = ' and ';
                        $filterQuery .= $this->buildFilter($value, $isSearchValue);
                        if ($uml) {
                            $filterQuery .= " or {$quotedIdentifierName} ";

                            $filterQuery .= $this->buildFilter($uml, $isSearchValue);
                            $filterQuery .= ')'; // end of OR section
                        }
                    }
                        // end for AND action
                        $filterQuery .= ' ) ';
                }
            }
        }

        return $filterQuery;
    }

    /**
     * Returns list filter array
     *
     * @return array
     */
    public function getListFilter()
    {
        if ($this->_aListFilter === null) {
            $request = \OxidEsales\Eshop\Core\Registry::getRequest();
            $filter = $request->getRequestParameter("where");
            $request->checkParamSpecialChars($filter);

            if (!empty($filter['oxorder']['oxordernr'])) {
                $filter['oxorder']['oxunzerordernr'] = $filter['oxorder']['oxordernr'];
            }

            $this->_aListFilter = $filter;
        }

        return $this->_aListFilter;
    }
}
