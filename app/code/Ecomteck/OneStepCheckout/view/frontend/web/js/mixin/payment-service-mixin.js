/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    'underscore',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/select-payment-method'
], function (
    _,
    wrapper,
    quote,
    selectPaymentMethod
) {
    'use strict';

    return function (target) {
        if (typeof(window.checkoutConfig.onestepcheckout) == 'undefined') {
            return target;
        }
        target.setPaymentMethods = wrapper.wrap(target.setPaymentMethods, function (setPaymentMethods, methods) {
            var result = setPaymentMethods(methods);
            if (!quote.paymentMethod()) {
                var defaultMethodIsAvailable = methods.find(function (item) {
                    return item.method === window.checkoutConfig.onestepcheckout.defaultPaymentMethod;
                });
                // Set default payment method
                if (defaultMethodIsAvailable) {
                    //selectPaymentMethod(defaultMethodIsAvailable);
                }
            }
            return result;
        });
        return target;
    }
});