/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([], function () {
    'use strict';

    /**
     * Override progress bar template so we can style the step icons.
     */
    return function (target) {
        if (typeof(window.checkoutConfig.onestepcheckout) == 'undefined') {
            return target.extend({});
        }
        return target.extend({
            defaults: {
                template: 'Ecomteck_OneStepCheckout/progress-bar',
                visible: true
            }
        });
    }
});