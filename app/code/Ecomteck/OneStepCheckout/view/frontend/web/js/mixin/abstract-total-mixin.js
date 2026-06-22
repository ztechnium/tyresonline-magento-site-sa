/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
define(
    [
        'Magento_Checkout/js/model/step-navigator'
    ],
    function (stepNavigator) {
        'use strict';

        /**
         * Allow configuration to force total full mode.
         */
        return function (target) {
            if (typeof(window.checkoutConfig.onestepcheckout) == 'undefined') {
                return target.extend({});
            }
            return target.extend({
                /**
                 * @return {*}
                 */
                isFullMode: function () {
                    if (!this.getTotals()) {
                        return false;
                    }

                    return true;
                }
            });
        };
    }
);