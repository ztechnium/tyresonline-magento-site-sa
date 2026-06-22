/**
 * Copyright © Ecomteck. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-rate-processor/new-address',
    'Magento_Checkout/js/model/shipping-rate-processor/customer-address',
    'Magento_Checkout/js/model/shipping-rate-registry'
], function (quote, defaultProcessor, customerAddressProcessor, rateRegistry) {
    'use strict';

    var processors = [];

    processors.default =  defaultProcessor;
    processors['customer-address'] = customerAddressProcessor;

    return {
        /**
         * @param {String} type
         * @param {*} processor
         */
        reload: function () {
            if (quote.isVirtual()) {
                return;
            }
            var type = quote.shippingAddress().getType();
            if (processors[type]) {
                processors[type].getRates(quote.shippingAddress());
            } else {
                processors.default.getRates(quote.shippingAddress());
            }
        }
    };
});
