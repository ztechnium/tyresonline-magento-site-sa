/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
define(
    [
    ],
    function () {
        'use strict';

        /**
         * Disable shiping rate cache
         */
        
        return function (target) {
            if (typeof(window.checkoutConfig.onestepcheckout) == 'undefined') {
                return target;
            }
            target.get = function (key) {
                return false;
            }
            return target;
        };
        
    }
);