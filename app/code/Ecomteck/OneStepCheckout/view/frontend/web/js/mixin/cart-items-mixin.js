/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([], function () {
    'use strict';

    /**
     * Allow cart items to always be shown through configuration.
     */
    return function (target) {
        if (typeof(window.checkoutConfig.onestepcheckout) == 'undefined') {
            return target.extend({});
        }
        return target.extend({
            isItemsBlockExpanded: function () {
                return window.checkoutConfig.onestepcheckout.alwaysShowCartItems ? true : this._super();
            }
        });
    };
});