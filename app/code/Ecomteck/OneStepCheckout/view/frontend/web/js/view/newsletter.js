/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
define(
    [
        'uiComponent'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Ecomteck_OneStepCheckout/newsletter'
            },

            initialize: function () {
                this._super();
            },

            isEnabled: function () {
                return window.checkoutConfig.onestepcheckout.newsletterEnabled;
            },

            isCheckedByDefault: function () {
                return window.checkoutConfig.onestepcheckout.newsletterChecked;
            },

            getCheckoutLabel: function () {
                return window.checkoutConfig.onestepcheckout.newsletterLabel;
            }
        });
    }
);