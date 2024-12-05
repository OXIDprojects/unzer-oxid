<?php

declare(strict_types=1);

class_alias(
    \OxidEsales\Eshop\Application\Controller\Admin\ModuleConfiguration::class,
    \OxidSolutionCatalysts\Unzer\Controller\Admin\ModuleConfiguration_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\Admin\OrderMain::class,
    \OxidSolutionCatalysts\Unzer\Controller\Admin\OrderMain_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\Admin\OrderList::class,
    \OxidSolutionCatalysts\Unzer\Controller\Admin\OrderList_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\OrderController::class,
    \OxidSolutionCatalysts\Unzer\Controller\OrderController_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController::class,
    \OxidSolutionCatalysts\Unzer\Controller\Admin\AdminDetailsController_parent::class
);
class_alias(
    \OxidEsales\Eshop\Application\Controller\PaymentController::class,
    \OxidSolutionCatalysts\Unzer\Controller\PaymentController_parent::class
);

class_alias(
    \OxidEsales\Eshop\Core\Config::class,
    \OxidSolutionCatalysts\Unzer\Core\Config_parent::class
);

class_alias(
    \OxidEsales\Eshop\Core\ShopControl::class,
    \OxidSolutionCatalysts\Unzer\Core\ShopControl_parent::class,
);

class_alias(
    \OxidEsales\Eshop\Core\ViewConfig::class,
    \OxidSolutionCatalysts\Unzer\Core\ViewConfig_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Model\Article::class,
    \OxidSolutionCatalysts\Unzer\Model\Article_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Model\Order::class,
    \OxidSolutionCatalysts\Unzer\Model\Order_parent::class,
);

class_alias(
    \OxidEsales\Eshop\Application\Model\Payment::class,
    \OxidSolutionCatalysts\Unzer\Model\Payment_parent::class
);

class_alias(
    \OxidEsales\Eshop\Application\Model\DiscountList::class,
    \OxidSolutionCatalysts\Unzer\Model\DiscountList_parent::class
);
