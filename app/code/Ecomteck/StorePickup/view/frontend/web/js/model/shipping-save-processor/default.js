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
            saveShippingInformation: function () {
                var payload;

                if (!quote.billingAddress() || $('#billing-address-same-as-shipping').is(':checked')) {
                    selectBillingAddressAction(quote.shippingAddress());
                }
                var shipping_address = quote.shippingAddress();
                var country_id = $('#pickup-store-country').val();
                var city = $('#pickup-store-city').val();
                var postcode = $('#pickup-store-postcode').val();
                var region = $('#pickup-store-region').val();
                var region_id = $('#pickup-store-region-id').val();
                var address = $('#pickup-store-address').val();
                if(country_id){
                    shipping_address.countryId = country_id;
                }
                if(city){
                    shipping_address.city = city;
                }
                if(postcode){
                    shipping_address.postcode = postcode;
                }
                if(region){
                    shipping_address.region = region;
                }
                if(region_id){
                    shipping_address.regionCode = regionCode;
                }
                if(address){
                    shipping_address.street = new Array();
                    shipping_address.street.push(address);
                }
                var carrier_code = "";
                var method_code = "";
                if(quote.shippingMethod()){
                    method_code = quote.shippingMethod().method_code;
                    carrier_code = quote.shippingMethod().carrier_code;
                }
                payload = {
                    addressInformation: {
                        shipping_address: shipping_address,
                        billing_address: quote.billingAddress(),
                        shipping_method_code: method_code,
                        shipping_carrier_code: carrier_code,
                        extension_attributes:{
                            delivery_date: $('[name="delivery_date"]').val(),
                            delivery_comment: $('[name="delivery_comment"]').val(),
                            pickup_store: $('#pickup-store').val(),
                            pickup_date: $('#pickup-date').val(),
                            pickup_time: $('#pickup-time').val(),
                            plate: $('[name="plate"]').val(),
                            make: $('[name="make"]').val(),
                            model: $('[name="model"]').val(),
                            year: $('[name="year"]').val(),
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
                        //paymentService.setPaymentMethods(methodConverter(response.payment_methods));
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
