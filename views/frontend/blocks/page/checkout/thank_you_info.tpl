[{$smarty.block.parent}]
[{if $oView->getUnzerPrePaymentIban()}]
    <strong>[{oxmultilang ident="OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_HEADLINE"}]:</strong><br/>
    [{oxmultilang ident="OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_IBAN"}]: [{$oView->getUnzerPrePaymentIban()}]<br/>
[{/if}]
[{if $oView->getUnzerPrePaymentBic()}]
    [{oxmultilang ident="OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_BIC"}]: [{$oView->getUnzerPrePaymentBic()}]<br/>
[{/if}]
[{if $oView->getUnzerPrePaymentHolder()}]
    [{oxmultilang ident="OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_BANK_HOLDER"}]: [{$oView->getUnzerPrePaymentHolder()}]<br/>
[{/if}]
[{if $oView->getUnzerPrePaymentDescriptor()}]
    [{oxmultilang ident="OSCUNZER_PREPAYMENT_BANK_ACCOUNT_INFO_BANK_DESCRIPTOR"}]: [{$oView->getUnzerPrePaymentDescriptor()}]<br/>
[{/if}]
[{if $oView->getUnzerPrePaymentIban()}]
    <br/>
[{/if}]
