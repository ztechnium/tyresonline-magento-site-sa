define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Hdweb_Coreoverride/js/model/isVehicle'
    ],
    function (Component, additionalValidators, vehicleValidation) {
        'use strict';
        additionalValidators.registerValidator(vehicleValidation);
        return Component.extend({});
    }
);