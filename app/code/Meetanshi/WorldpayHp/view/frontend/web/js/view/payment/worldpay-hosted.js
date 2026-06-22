define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component,
              rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'worldpay_hp',
                component: 'Meetanshi_WorldpayHp/js/view/payment/method-renderer/worldpay-payments'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
