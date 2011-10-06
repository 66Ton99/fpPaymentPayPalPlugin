# fpPaymentPayPal

It depends on fpPaymentPlugin

You have to enable "fpPaymentPayPal"

_settings.yml_

    all:
      .settings:
        enabled_modules:
          - fpPaymentPayPal
    

You have to create fp_payment_paypal.yml file in to yours config folder and configure it.

_fp_payment_paypal.yml_

    all:
      form_hidden_fields:
        business: 'Your PayPal email'
    