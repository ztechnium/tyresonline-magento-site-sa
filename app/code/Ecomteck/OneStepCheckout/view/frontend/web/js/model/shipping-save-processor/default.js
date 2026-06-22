/**
 * Ecomteck_StorePickup Magento Extension
 *
 * @category    Ecomteck
 * @package     Ecomteck_StorePickup
 * @author      Ecomteck <ecomteck@gmail.com>
 * @website    http://www.ecomteck.com
 */

define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-converter',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/select-billing-address'
    ],
    function (
        $,
        ko,
        quote,
        resourceUrlManager,
        storage,
        paymentService,
        methodConverter,
        errorProcessor,
        fullScreenLoader,
        selectBillingAddressAction
    ) {
        'use strict';

        return {
            isBillingBeforeShipping: function () {
                return window.checkoutConfig.onestepcheckout.moveBillingAddressBeforeShippingAddressEnabled;
            },
            saveShippingInformation: function () {
                if (!quote.shippingMethod()) {
                    return;
                }
                var payload;

                if ((!quote.billingAddress() || $('#billing-address-same-as-shipping').is(':checked')) && !this.isBillingBeforeShipping()) {
                    selectBillingAddressAction(quote.shippingAddress());
                    $(".checkout-billing-address > .fieldset").hide();
                }
                payload = {
                    addressInformation: {
                        shipping_address: quote.shippingAddress(),
                        billing_address: quote.billingAddress(),
                        shipping_method_code: quote.shippingMethod().method_code,
                        shipping_carrier_code: quote.shippingMethod().carrier_code,
                        extension_attributes:{
                            delivery_date: $('[name="delivery_date"]').val(),
                            delivery_comment: $('[name="delivery_comment"]').val(),
                            pickup_store: $('#pickup-store').val(),
                            pickup_date: $('#pickup-date').val(),
                            pickup_time: $('#pickup-time').val(),
                        }
                    }
                };

                fullScreenLoader.startLoader();

                return storage.post(
                    resourceUrlManager.getUrlForSetShippingInformation(quote),
                    JSON.stringify(payload)
                ).done(
                    function (response) {
                        quote.setTotals(response.totals);
                        paymentService.setPaymentMethods(methodConverter(response.payment_methods));
                        fullScreenLoader.stopLoader();
                    }
                ).fail(
                    function (response) {
                        errorProcessor.process(response);
                        fullScreenLoader.stopLoader();
                    }
                );
            }
        };
    }
);
