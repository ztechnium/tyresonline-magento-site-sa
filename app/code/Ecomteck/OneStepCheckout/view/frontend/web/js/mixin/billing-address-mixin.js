/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    'ko',
    'mage/translate',
    'jquery'
], function (
    ko,
    $t,
    $
) {
    'use strict';

    return function (target) {
        if (typeof(window.checkoutConfig.onestepcheckout) == 'undefined') {
            return target.extend({});
        }
        return target.extend({
            /**
             * Override billing address template
             */
            defaults: {
                template: 'Ecomteck_OneStepCheckout/billing-address'
            },
        });
    }
});