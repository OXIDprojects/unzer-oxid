[{assign var="payment" value=$oView->getPayment()}]
[{if $payment->isUnzerPayment()}]
    [{include file="@osc-unzer/frontend/tpl/order/unzer_shippingAndPayment_flow"}]
[{else}]
    [{$smarty.block.parent}]
[{/if}]