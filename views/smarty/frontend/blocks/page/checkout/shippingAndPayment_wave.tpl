[{assign var="payment" value=$oView->getPayment()}]
[{if $oViewConf->checkUnzerHealth() && $payment->isUnzerPayment()}]
    [{include file="@unzer/unzer_shippingAndPayment_wave.tpl"}]
[{else}]
    [{$smarty.block.parent}]
[{/if}]