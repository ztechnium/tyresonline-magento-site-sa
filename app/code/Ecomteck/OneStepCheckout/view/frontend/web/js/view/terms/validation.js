/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Ecomteck_OneStepCheckout/js/model/terms/validator'
], function (Component, additionalValidators, termsValidator) {
    'use strict';

    additionalValidators.registerValidator(termsValidator);

    return Component.extend({});
});
