[{include file="modules/osc/unzer/unzer_assets.tpl"}]
[{if $savedPaymentTypes}]
    <div class="savedpayment">
        [{foreach from=$savedPaymentTypes item="setting" key="type"}]
            [{if $type != 'paypal' && $type != 'sepa'}]
                [{assign var="savedCardsCount" value=$setting|@count}]
                <form id="payment-saved-cards" class="unzerUI form" novalidate>
                    <input type="hidden" name="savedCardsCount" value="[{$savedCardsCount}]">
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">[{oxmultilang ident="OSCUNZER_CARD_NUMBER"}]</th>
                            <th scope="col">[{oxmultilang ident="OSCUNZER_EXPIRY_DATE"}]</th>
                            <th scope="col">[{oxmultilang ident="OSCUNZER_BRAND"}]</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                            [{assign var="counter" value=0}]
                            [{foreach from=$setting item="paymentType" }]
                                <tr>
                                    <th scope="row">[{$paymentType.number}]</th>
                                    <td>[{$paymentType.expiryDate}]</td>
                                    <td>[{$type}]</td>

                                    <td>
                                        <input type="radio" class="paymenttypeid" name="paymenttypeid" value="[{$paymentType.id}]" style="-webkit-appearance: radio">
                                    </td>
                                </tr>
                            [{/foreach}]
                        </tbody>
                    </table>
                </form>
            [{/if}]
        [{/foreach}]
    </div>
[{/if}]

[{if $savedPaymentTypes != false }]
<br>
    <label id="addNewCardCheckboxLabel">
        <input type="checkbox" name="newccard" id="newccard" value="show"  style="-webkit-appearance: checkbox">[{oxmultilang ident="OSCUNZER_NEW_CARD"}]
    </label>
[{/if}]
<div id="newcc" style="display:none;">
    <form id="payment-form-card" class="unzerUI form" novalidate>
        <div class="field">
            <div id="card-element-id-cardholder" class="unzerInput">
                <!-- Card number UI Element will be inserted here. -->
            </div>
        </div>
        <div class="field">
            <div id="card-element-id-number" class="unzerInput">
                <!-- Card number UI Element will be inserted here. -->
            </div>
        </div>
        <div class="two fields">
            <div class="field ten wide">
                <div id="card-element-id-expiry" class="unzerInput">
                    <!-- Card expiry date UI Element will be inserted here. -->
                </div>
            </div>
            <div class="field six wide">
                <div id="card-element-id-cvc" class="unzerInput">
                    <!-- Card CVC UI Element will be inserted here. -->
                </div>
            </div>
        </div>
        [{if $oView->getPaymentSaveSetting()}]
            <div id="payment-sepa-confirm">
                <div class="oscunzersavepayment" id="oscunzersavepayment_unzer">
                    <input id="oscunzersavepayment" type="checkbox" name="oscunzersavepayment" value="0" style="-webkit-appearance: checkbox">
                    <label for="oscunzersavepayment">
                        [{oxmultilang ident="OSCUNZER_SAVE_PAYMENT"}]
                    </label>
                </div>
            </div>
        [{/if}]
    </form>
    <style>
        .unzerUI.form .error.message {
            line-height: 2rem;
        }
    </style>
</div>

[{if false}]
    <script>
[{/if}]
[{capture assign="unzerCardJS"}]
    let newCardCheckbox = $('input[name="newccard"]');
    let savedCardsTableEl = $('#payment-saved-cards');
    let paymentFormCardEl = $('#payment-form-card');
    let cardsCount = 0;
    let orderConfirmAgbBottom = $('#orderConfirmAgbBottom');

    newCardCheckbox.on('change', function() {
        if ($(this).prop('checked')) {
            orderConfirmAgbBottom.addClass('new-card-selected');
        } else {
            orderConfirmAgbBottom.removeClass('new-card-selected');
        }
    });

    orderConfirmAgbBottom.submit(function(event) {
        if ($(this).hasClass('new-card-selected') && !$(this).hasClass("submitable")) {
            event.preventDefault();
            paymentFormCardEl.submit();
        } else if (!$(this).hasClass("submitable")) {
            event.preventDefault();
            savedCardsTableEl.submit();
        }
        orderConfirmAgbBottom.removeClass('new-card-selected');
    });

    let unzerInstance = new unzer('[{$unzerpub}]', {locale: "[{$unzerLocale}]",  holderEnabled: true });
    if (savedCardsTableEl.length) {
        cardsCount = parseInt($('input[name=savedCardsCount]').attr('value'), 10);
    }
    if (newCardCheckbox.length === 0 || cardsCount === 0) {
        orderConfirmAgbBottom.addClass('new-card-selected');
        $('#newcc').show();
        if (cardsCount === 0) {
            $('#addNewCardCheckboxLabel').hide();
        }
        Card = unzerInstance.Card();
        addPaymentElements(Card);
    } else {
        newCardCheckbox.on('change', function() {
            Card = unzerInstance.Card();
            if ($(this).prop('checked')) {
                addPaymentElements(Card);
            } else {
                removeCardElements();
                if (Card && Card.destroy) {
                    Card.destroy();
                }
            }
        });
    }

    paymentFormCardEl.submit(function( event ) {
        event.preventDefault();
        if (Card) {
            Card.createResource()
                .then(function(result) {
                    let hiddenInput = $(document.createElement('input'))
                        .attr('type', 'hidden')
                        .attr('name', 'paymentData')
                        .val(JSON.stringify(result));
                    let hiddenInput2 = $(document.createElement('input'))
                        .attr('type', 'hidden')
                        .attr('name', 'oscunzersavepayment')
                        .val($('#oscunzersavepayment').is(':checked') ? '1' : '0');
                    orderConfirmAgbBottom.find(".hidden")
                        .append(hiddenInput2)
                        .append(hiddenInput);
                    orderConfirmAgbBottom.addClass("submitable")
                        .submit();
                })
                .catch(function(error) {
                    $('html, body').animate({
                        scrollTop: $("#orderPayment").offset().top - 150
                    }, 350);
                    orderConfirmAgbBottom.addClass('new-card-selected');
                })
        }
        orderConfirmAgbBottom.addClass('new-card-selected');
    });
    savedCardsTableEl.submit(function( event ) {
        event.preventDefault();
        let selectedPaymentTypeId = $('input[name=paymenttypeid]:checked').val();
        let paymentData = {
            id: selectedPaymentTypeId
        };
        let paymentDataString = JSON.stringify(paymentData);

        let hiddenInput3 = $(document.createElement('input'))
            .attr('type', 'hidden')
            .attr('name', 'paymentData')
            .val(paymentDataString);
        let hiddenInput4 = $(document.createElement('input'))
                        .attr('type', 'hidden')
                        .attr('name', 'is_saved_payment_in_action')
                        .val(1);
        orderConfirmAgbBottom.find(".hidden").append(hiddenInput3)
            .append(hiddenInput4);
        orderConfirmAgbBottom.addClass("submitable")
            .submit();
    });

    newCardCheckbox.on('change', function() {
        if ($(this).prop('checked')) {
            $('.savedpayment').fadeOut();
            $('input[name="paymenttypeid"]').prop('checked', false);
            $('#newcc').fadeIn();
        } else {
            $('.savedpayment').fadeIn();
            $('#newcc').fadeOut();
        }
    });
    function removeCardElements() {
        $('#card-element-id-number').empty();
        $('#card-element-id-cardholder').empty();
        $('#card-element-id-expiry').empty();
        $('#card-element-id-cvc').empty();
    }
    function addPaymentElements(Card) {
        Card.create('number', {
            containerId: 'card-element-id-number',
            onlyIframe: false
        });
        Card.create('holder', {
            containerId: 'card-element-id-cardholder',
            onlyIframe: false
        });
        Card.create('expiry', {
            containerId: 'card-element-id-expiry',
            onlyIframe: false
        });
        Card.create('cvc', {
            containerId: 'card-element-id-cvc',
            onlyIframe: false
        });

    }
[{/capture}]
[{capture assign="unzerCardPreventEmpty"}]
    const submitButton = document.querySelector("#orderConfirmAgbBottom button.submitButton");
    const radioButtons = document.querySelectorAll('input[type="radio"].paymenttypeid');
    const overrideCheckbox1 = document.getElementById("oscunzersavepayment");
    const overrideCheckbox2 = document.getElementById("newccard");

    function updateSubmitButtonState() {
        const isRadioSelected = Array.from(radioButtons).some(radio => radio.checked);
        const isAnyCheckboxChecked = (overrideCheckbox1 && overrideCheckbox1.checked) ||
                                     (overrideCheckbox2 && overrideCheckbox2.checked);

        const cardholderInput = document.querySelector('#card-element-id-cardholder iframe');
        const cardnumberInput = document.querySelector('#card-element-id-number iframe');
        const cvcInput = document.querySelector('#card-element-id-cvc iframe');
        const expiryInput = document.querySelector('#card-element-id-expiry iframe');

        const areCardFieldsPresent = cardholderInput && cardnumberInput && cvcInput && expiryInput;

        const shouldEnableSubmit = areCardFieldsPresent || isRadioSelected || isAnyCheckboxChecked;

        submitButton.disabled = !shouldEnableSubmit;
    }

    submitButton.disabled = true;

    radioButtons.forEach(radio => {
        radio.addEventListener('change', updateSubmitButtonState);
    });

    if (overrideCheckbox1) {
        overrideCheckbox1.addEventListener('change', updateSubmitButtonState);
    }

    if (overrideCheckbox2) {
        overrideCheckbox2.addEventListener('change', updateSubmitButtonState);
    }

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'childList') {
                updateSubmitButtonState();
            }
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });

    updateSubmitButtonState();

    setInterval(updateSubmitButtonState, 1000);
[{/capture}]
[{if false}]
    </script>
[{/if}]
[{oxscript add=$unzerCardJS}]
[{oxscript add=$unzerCardPreventEmpty}]
