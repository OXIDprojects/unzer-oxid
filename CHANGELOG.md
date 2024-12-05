# Change Log for Unzer Checkout for OXID

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.2.1] - unreleased

### FIXED
- [0007730](https://bugs.oxid-esales.com/view.php?id=7730): even if the credit card payment was cancelled (when entering the secure code), an order confirmation is sent
- Order of credit card data input fields optimized
- Add Descriptor for PrePayment on ThankYou-Page
- remove Option "Capture Later"-Option for ApplePay
- Save ApplePay-Certificates for Test- and Live-Mode. Fix an possible Maintenance when something was wrong before
- Fixed a problem when multiple modules were in use. If Unzer was not last in some extended classes, this could lead to a maintenance.

## [1.2.0] - 2024-09-26

### NEW
- New payment method Unzer installment (Paylater)
- If a customer interrupt the order in the checkout for any reason, the order is still saved using a temporary order and Unzer's webhook + scheduled cleanup  temporary order
- add confirm dialog when collecting money in admin and disable the collect button until site is reloaded
- provide Cardholder field for Credit cards

### FIXED
- refactor of the correct use of the appropriate credentials depending on the payment method used
- [0007553](https://bugs.oxid-esales.com/view.php?id=7553) revert this task because, it is possible to have different billing and delivery addresses for invoice purchases (Paylater)
- [0007586](https://bugs.oxid-esales.com/view.php?id=7586) - Fix: Unable to finish the checkout process using the Apple Pay as the payment method
- [0007638](https://bugs.oxid-esales.com/view.php?id=7638) - Fix: Sometimes duplicate order-positions in Backend, and dublicate ordermails ...
- Discounts with time restrictions may not be invalidated directly in the checkout...
- Save Payment Data for registered Users
- [0007723](https://bugs.oxid-esales.com/view.php?id=7723): In net mode, rounding errors are possible. If the total number of items is greater than the OXID total, a voucher will be added for the amount of the rounding error. Conversely, a dummy item will be added.
- If other modules are installed that also extend the order model and contain non-serializable elements, maintenance may occur. Solution: Use a serializer that only contains data and no other elements.

## [1.1.3] - 2023-11-14

- [0007526](https://bugs.oxid-esales.com/view.php?id=7526) Order would be saved only, if everything is correct. In all other cases redirect to checkout
- [0007509](https://bugs.oxid-esales.com/view.php?id=7509) Order would be saved only, if everything is correct. In all other cases redirect to checkout
- [0007524](https://bugs.oxid-esales.com/view.php?id=7524) catch Error if Unzer-API not working and redirect to Checkout
- [0007527](https://bugs.oxid-esales.com/view.php?id=7527) prevent clicking the buy-now-button several times
- [0007544](https://bugs.oxid-esales.com/view.php?id=7544) Add Error handling When unsupported Credit Card is used (e.g. Amex)
- [0007553](https://bugs.oxid-esales.com/view.php?id=7553) The billing and delivery address must be identical for invoice purchases (Paylater)
- [0007546](https://bugs.oxid-esales.com/view.php?id=7546): We provide an additional Order Number to Unzer for identify the Order in OXID-Backend and Unzer-Insights
- Prepayment - Adjust payment date when the payment has been completed
- change information for Unzer-Metadata
- Unzer Invoice (Paylater): Display bank details for invoice

## [1.1.2] - 2023-08-18

### FIXED
- compatibility-issue against other modules that also extend the moduleconfiguration
- [0007503](https://bugs.oxid-esales.com/view.php?id=7503) When ordering via the Unzer module, the OXID standard field OXORDER__OXTRANSID remains empty
- Adjust payment date when the payment has been completed

## [1.1.1] - 2023-06-19

### FIXED
- apple pay session init only when eligible

## [1.1.0] - 2023-06-02

### NEW
- New Payment PayLater
### FIXED
- Webhooks cleanup, registration is now based on key (context)
- Fixed ApplePay admin settings not saving the merchant certificate properly.
- New country restrictions based on the Unzer documentation
  - ALIPAY: DE, AT, BE, IT, ES, NL
  - Unzer Invoice (Paylater): DE, AT, CH, NL
  - Prepayment: all Countries
  - SEPA Direct Debit: DE, AT
  - Sofort: DE, AT, BE, IT, ES, NL
  - WeChat: AT, BE, DK, FI, FR, DE, ES, GB, GR, HU, IE, IS, IT, LI, LU, MT, NL, NO, PT, SE
- New currency restrictions based on the Unzer documentation
  - ALIPAY: AUD, CAD, CHF, CNY, EUR, GBP, HKD, NZD, SGD, USD
  - ApplePay: AUD, CHF, CZK, DKK, EUR, GBP, NOK, PLN, SEK, USD, HUF, RON, BGN, HRK, ISK
  - Bancontact: EUR
  - EPS: EUR
  - Giropay: EUR
  - IDEAL: EUR
  - Przelewy24: PLZ
  - Sofort: EUR
  - SEPA Direct Debit: EUR
  - Unzer Invoice (Paylater): EUR, CHF
  - Prepayment: EUR
  - WeChat Pay: CHF, CNY, EUR, GBP, USD
- Cleanup payment methods in database configuration
- Correct Customer-Details for Unzer (e.g. OXID-customerId)
- [0007453](https://bugs.oxid-esales.com/view.php?id=7453) Unzer prohibits changes in settings of other modules
- [0007454](https://bugs.oxid-esales.com/view.php?id=7454) Unzer and Paypal cannot be activated at the same time
- [0007436](https://bugs.oxid-esales.com/view.php?id=7436) add option to reverese a prepayment-transaction from the backend
- [0007430](https://bugs.oxid-esales.com/view.php?id=7430) Update Basket version to V2
- [0007429](https://bugs.oxid-esales.com/view.php?id=7429) Customer details - adjust addresses
- [0007432](https://bugs.oxid-esales.com/view.php?id=7432) Basket Details - Discount is missed in the Basket
- [0007447](https://bugs.oxid-esales.com/view.php?id=7447) Markup due to negative discount per shopping cart leads to maintainance mode
- [0007439](https://bugs.oxid-esales.com/view.php?id=7439) Chargeback transactions do not appear in the backend
- [0007442](https://bugs.oxid-esales.com/view.php?id=7442) Reversal after partial reversal

### CHANGES
Unzer has **deprecated** following payment methods, which have been removed from the definitions:
- Installment / Ratenzahlung
- Unzer Direct Debit Secured/ SEPA Lastschrift (abgesichert durch Unzer)
- Bank transfer


## [1.0.1] - 2022-12-03

### FIXED
- Optimized webhook saving in the config
- Update github-actions

## [1.0.0] - 2022-07-28

- initial release for OXID>=v6.3 and as part of EE-Compilation v.6.5.0
