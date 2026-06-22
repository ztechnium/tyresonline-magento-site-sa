/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([], function () {
    'use strict';

    /**
     * Hides shipping method title if required by configuration.
     */
    return function (target) {
        if (typeof(window.checkoutConfig.onestepcheckout) == 'undefined') {
            return target.extend({});
        }
        return target.extend({
            getShippingMethodTitle: function () {
                return window.checkoutConfig.onestepcheckout.hideShippingTitle ? '' : this._super();
            }
        });
    };
});