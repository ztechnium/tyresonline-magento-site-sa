/** Apply coupon codes response processor for cart page */

define([
    'underscore',
    'Magento_Checkout/js/model/quote',
    'Amasty_Coupons/js/model/abstract-apply-response-processor',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/totals',
    'Amasty_Coupons/js/action/recollect-shipping-rates-resolver',
    'Magento_Checkout/js/model/cart/cache',
    'Magento_Checkout/js/model/cart/totals-processor/default'
], function (
    _,
    quote,
    abstractProcessor,
    customerData,
    totals,
    recollectShippingRates,
    cartCache,
    totalsDefaultProvider
) {
    'use strict';

    /**
     * Estimate service's method from Magento 2.4.0
     * @returns {void}
     */
    var estimateTotalsShipping = function () {
        totalsDefaultProvider.estimateTotals(quote.shippingAddress());
    };

    return abstractProcessor.extend({

        /**
         * @param {couponApplyListResult} response
         * @returns {void}
         */
        onSuccess: function (response) {
            if (response.is_quote_items_changed) {
                window.location.reload();

                return;
            }

            this._super();

            var estimateTotalsSubscriber = this.getEstimateTotalsSubscriber();

            cartCache.clear('rates');
            customerData.invalidate([ 'cart-data' ]);
            customerData.reload([ 'cart' ]);

            // Magento 2.4.2 Compatibility: Estimate totals after reload cart section
            if (!estimateTotalsSubscriber) {
                estimateTotalsShipping();
            }

            recollectShippingRates();
        },

        /**
         * Magento 2.4.2 Compatibility: Find cart section's subscriber with estimate totals
         * @returns {undefined|Object}
         */
        getEstimateTotalsSubscriber: function () {
            var cartSectionSubscribers = customerData.get('cart')._subscriptions.change;

            return _.find(cartSectionSubscribers, function (subscriber) {
                var subscriberCallback = subscriber.callback || subscriber._callback,
                    subscriberCallbackWithoutSpaces = subscriberCallback.toString().replace(/\s/g, ''),
                    estimateTotalsShippingWithoutSpaces = estimateTotalsShipping.toString().replace(/\s/g, '');

                return subscriberCallbackWithoutSpaces === estimateTotalsShippingWithoutSpaces;
            });
        }
    });
});
