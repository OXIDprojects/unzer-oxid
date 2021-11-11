<div class="row">
    <div class="col-12 col-md-6" id="orderShipping">
        <form action="[{$oViewConf->getSslSelfLink()}]" method="post">
            <div class="hidden">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="payment">
                <input type="hidden" name="fnc" value="">
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        [{oxmultilang ident="SHIPPING_CARRIER"}]
                        <button type="submit" class="btn btn-sm btn-warning float-right submitButton largeButton edit-button" title="[{oxmultilang ident="EDIT"}]">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </h3>
                </div>
                <div class="card-body">
                    [{assign var="oShipSet" value=$oView->getShipSet()}]
                    [{$oShipSet->oxdeliveryset__oxtitle->value}]
                </div>
            </div>
        </form>
    </div>
    <div class="col-12 col-md-6" id="orderPayment">

        <div class="hidden">
            [{$oViewConf->getHiddenSid()}]
            <input type="hidden" name="cl" value="payment">
            <input type="hidden" name="fnc" value="">
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    [{oxmultilang ident="PAYMENT_METHOD"}]
                    <button type="submit" class="btn btn-sm btn-warning float-right submitButton largeButton edit-button" title="[{oxmultilang ident="EDIT"}]">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                </h3>
            </div>
            <div class="card-body">
                [{assign var="payment" value=$oView->getPayment()}]
                [{assign var="sPaymentID" value=$payment->getId()}]

                [{if $sPaymentID == "oscunzer_banktransfer"}]
                    [{include file="modules/osc/unzer/unzer_banktransfer.tpl" paymentid=$sPaymentID}]
                [{elseif $sPaymentID == "oscunzer_card"}]
                    [{include file="modules/osc/unzer/unzer_card.tpl" paymentid=$sPaymentID}]
                [{elseif $sPaymentID == "oscunzer_eps"}]
                    [{include file="modules/osc/unzer/unzer_eps_charge.tpl paymentid=$sPaymentID"}]
                [{elseif $sPaymentID == "oscunzer_giropay"}]
                    [{include file="modules/osc/unzer/unzer_giro.tpl" paymentid=$sPaymentID}]
                [{elseif $sPaymentID == "oscunzer_ideal"}]
                    [{include file="modules/osc/unzer/unzer_ideal.tpl" paymentid=$sPaymentID}]
                [{elseif $sPaymentID == "oscunzer_installment"}]
                    [{include file="modules/osc/unzer/installment.tpl" paymentid=$sPaymentID}]
                [{elseif $sPaymentID == "oscunzer_invoice-secured"}]
                    [{include file="modules/osc/unzer/unzer_invoice_securred.tpl" paymentid=$sPaymentID}]
                [{elseif $sPaymentID == "oscunzer_paypal"}]
                    [{include file="modules/osc/unzer/unzer_paypal.tpl" paymentid=$sPaymentID}]
                [{elseif $sPaymentID == "oscunzer_post-finance"}]
                    [{include file="modules/osc/unzer/unzer_invoice.tpl" paymentid=$sPaymentID}]
                [{elseif $sPaymentID == "oscunzer_prepayment"}]
                    [{include file="modules/osc/unzer/unzer_prepayment.tpl" paymentid=$sPaymentID}]
                [{elseif $sPaymentID == "oscunzer_sepa"}]
                    [{include file="modules/osc/unzer/unzer_sepa.tpl" paymentid=$sPaymentID}]
                [{elseif $sPaymentID == "oscunzer_sepa-secured"}]
                    [{include file="modules/osc/unzer/unzer_sepa.tpl" paymentid=$sPaymentID}]
                [{elseif $sPaymentID == "oscunzer_sofort"}]
                    [{include file="modules/osc/unzer/unzer_sofort.tpl" paymentid=$sPaymentID}]
                [{else}]
                    [{assign var="payment" value=$oView->getPayment()}]
                    [{$payment->oxpayments__oxdesc->value}]
                [{/if}]
            </div>
        </div>

    </div>
</div>




