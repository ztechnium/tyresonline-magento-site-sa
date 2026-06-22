/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
define(
    [
        'Magento_SalesRule/js/view/payment/discount'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Ecomteck_OneStepCheckout/discount'
            },

            isEnabled: function () {
                return !window.checkoutConfig.onestepcheckout.display.disableCouponCode;
            },
            getInitialCollapseState: function () {
                return window.checkoutConfig.onestepcheckout.display.couponCodeCollapseState;
            },
            isInitialStateOpened: function () {
                return this.getInitialCollapseState() === 1
            }
        });
    }
);