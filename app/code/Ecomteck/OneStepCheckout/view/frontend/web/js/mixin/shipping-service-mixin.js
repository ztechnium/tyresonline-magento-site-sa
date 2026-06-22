/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    'ko',
    'jquery',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/shipping-save-processor/default'
], function (ko,$,checkoutDataResolver,getPaymentInformation,getTotalsAction,shippingSaveProcessor) {
    'use strict';
    return function (target) {
        if (typeof(window.checkoutConfig.onestepcheckout) == 'undefined') {
            return target
        }

        var shippingRates = ko.observableArray([]);

        return {
            isLoading: ko.observable(false),

            /**
             * Set shipping rates
             *
             * @param {*} ratesData
             */
            setShippingRates: function (ratesData) {
                shippingRates(ratesData);
                shippingRates.valueHasMutated();
                checkoutDataResolver.resolveShippingRates(ratesData);
                var deferred = $.Deferred();
                shippingSaveProcessor.saveShippingInformation();
                getTotalsAction([]);
                //getPaymentInformation(deferred);
            },

            /**
             * Get shipping rates
             *
             * @returns {*}
             */
            getShippingRates: function () {
                return shippingRates;
            }
        };
    };
});
