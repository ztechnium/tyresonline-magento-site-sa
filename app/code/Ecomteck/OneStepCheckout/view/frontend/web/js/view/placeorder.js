/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
define(
    [
        'ko',
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Checkout/js/model/error-processor'
    ],
    function (
        ko,
        $,
        Component,
        setShippingInfomationAction,
        quote,
        customer,
        urlBuilder,
        urlFormatter,
        errorProcessor
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Ecomteck_OneStepCheckout/placeorder'
            },

            isEnabled: function () {
                return true;
            },

            getCheckoutLabel: function () {
                return window.checkoutConfig.onestepcheckout.newsletterLabel;
            },

            placeOrder: function () {
                var _this = this;
                
                if (!quote.isVirtual()) {
                    var shippingAddressForm = $('#co-shipping-form').get(0);
                    if (shippingAddressForm) {
                        var shipping = ko.dataFor(shippingAddressForm);
                        var shippingValidateResult = shipping.validateShippingInformation();
                        var billingValidateResult =  shipping.validateBillingInformation();
                        var termValidateResult =  this.validateTerm();
                        var validateCustomOrderAttributesResult = this.validateCustomOrderAttributes();
                        var validateResult = shippingValidateResult && billingValidateResult && validateCustomOrderAttributesResult && termValidateResult;
                        if (validateResult) {
                            setShippingInfomationAction().done(function () {
                                setTimeout(function () {
                                    _this.placeOrderAction();
                                },1000);
                            });
                        }
                    }
                } else {
                    var billingAddressForm = $('#billing-new-address-form').get(0);
                    if (billingAddressForm) {
                        var billing = ko.dataFor(billingAddressForm);
                        var billingValidateResult =  billing.validateBillingInformation();
                        var termValidateResult =  this.validateTerm();
                        var validateCustomOrderAttributesResult = this.validateCustomOrderAttributes();
                        var validateResult = billingValidateResult && validateCustomOrderAttributesResult && termValidateResult;
                        if (validateResult) {
                            _this.placeOrderAction();
                        }
                    }
                }
            },
            placeOrderAction: function () {
                if ($('.payment-method._active button.action').length) {
                    var placeOrderButton = $('.payment-method._active button.action').get(0);
                    $(placeOrderButton).click();
                    return;
                }
            },
            validateTerm: function () {
                var form = '#form-term-condition';
                if ($(form).length == 0) {
                    return true;
                }
                var result = $(form).validation() && $(form).validation('isValid');
                if (!result) {
                    $('#error-term-condition').show();
                } else {
                    $('#error-term-condition').hide();
                }
                return result;
            },
            validateCustomOrderAttributes: function () {
                if (this.source) {
                    if (this.source.order) {
                        this.source.set('params.invalid', false);
                        for (var step in this.source.order) {
                            this.source.trigger('order.'+step+'.data.validate');
                        }
                        var result = !this.source.get('params.invalid');

                        if (result) {
                            var isCustomer = customer.isLoggedIn();
                            var quoteId = quote.getQuoteId();

                            var url;
                            if (isCustomer) {
                                url = urlBuilder.createUrl('/carts/mine/set-order-custom-attributes', {})
                            } else {
                                url = urlBuilder.createUrl('/guest-carts/:cartId/set-order-custom-attributes', {cartId: quoteId});
                            }

                            var orderCustomAttributes = JSON.stringify(this.source.order);
                            
                            var payload = {
                                cartId: quoteId,
                                orderCustomAttributes: {
                                    order_custom_attributes: orderCustomAttributes
                                }
                            };

                            if (!payload.orderCustomAttributes.order_custom_attributes) {
                                return true;
                            }

                            var result = true;

                            $.ajax({
                                url: urlFormatter.build(url),
                                data: JSON.stringify(payload),
                                global: false,
                                contentType: 'application/json',
                                type: 'PUT',
                                async: false
                            }).done(
                                function (response) {
                                    result = true;
                                }
                            ).fail(
                                function (response) {
                                    result = false;
                                    errorProcessor.process(response);
                                }
                            );
                        }
                        return result;
                    }
                }
                return true;
            }
        });
    }
);