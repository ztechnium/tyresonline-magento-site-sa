/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    'Magento_Checkout/js/model/shipping-save-processor/default',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/checkout-data',
    'Ecomteck_OneStepCheckout/js/model/shipping-rate-service',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/quote'
], function (
    shippingSaveProcessor,
    checkoutDataResolver,
    checkoutData,
    shippingRateService,
    getTotalsAction,
    quote
) {
    'use strict';
    return function (target) {
        if (typeof(window.checkoutConfig.onestepcheckout) == 'undefined') {
            return target.extend({});
        }
        return target.extend({
            defaults: {
                template: 'Ecomteck_OneStepCheckout/shipping-address/address-renderer/default'
            },
            selectAddress: function () {
                this._super();
                if (quote.isVirtual()) {
                    getTotalsAction(function () {

                    });
                } else {
                    shippingRateService.reload();
                    getTotalsAction(function () {
                        
                    });
                }
                /*
                shippingSaveProcessor.saveShippingInformation().error(function(b){
                    window.location.reload();
                });*/
                return true;
            },
        });
    }
});