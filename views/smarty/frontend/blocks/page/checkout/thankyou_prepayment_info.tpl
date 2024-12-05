
    [{$smarty.block.parent}]
    [{assign var="orderNumber" value=$order->oxorder__oxunzerordernr->value}]
    [{if $oViewConf->getPrePaymentIban($orderNumber)}]
        <strong>[{oxmultilang ident="OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_HEADLINE"}]:</strong><br/>
        [{oxmultilang ident="OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_IBAN"}]: [{$oViewConf->getPrePaymentIban($orderNumber)}]<br/>
    [{/if}]
    [{if $oViewConf->getPrePaymentBic($orderNumber)}]
        [{oxmultilang ident="OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_BIC"}]: [{$oViewConf->getPrePaymentBic($orderNumber)}]<br/>
    [{/if}]
    [{if $oViewConf->getPrePaymentHolder($orderNumber)}]
        [{oxmultilang ident="OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_BANK_HOLDER"}]: [{$oViewConf->getPrePaymentHolder($orderNumber)}]<br/>
    [{/if}]
    [{if $oView->getUnzerPrePaymentDescriptor($orderNumber)}]
        [{oxmultilang ident="OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_DESCRIPTOR"}]: [{$oViewConf->getUnzerPrePaymentDescriptor($orderNumber)}]<br/>
        [{/if}]
    [{if $oViewConf->getPrePaymentIban($orderNumber)}]
        <br/>
    [{/if}]
