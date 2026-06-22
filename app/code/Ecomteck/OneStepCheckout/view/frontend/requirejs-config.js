/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
var config = {
    map: {
        '*': {
            async: 'Ecomteck_OneStepCheckout/js/async',
            'Magento_Checkout/js/model/shipping-save-processor/default': 'Ecomteck_OneStepCheckout/js/model/shipping-save-processor/default'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/shipping': {
                'Ecomteck_OneStepCheckout/js/mixin/shipping-mixin': true
            },
            'Magento_Checkout/js/view/billing-address': {
                'Ecomteck_OneStepCheckout/js/mixin/billing-address-mixin': true
            },
            'Magento_Checkout/js/view/shipping-address/address-renderer/default': {
                'Ecomteck_OneStepCheckout/js/mixin/shipping-address-address-renderer-mixin': true
            },
            'Magento_Checkout/js/model/step-navigator': {
                'Ecomteck_OneStepCheckout/js/mixin/step-navigator-mixin': true
            },
            'Magento_Checkout/js/model/payment-service': {
                'Ecomteck_OneStepCheckout/js/mixin/payment-service-mixin': true
            },
            'Magento_Checkout/js/model/shipping-rate-registry': {
                'Ecomteck_OneStepCheckout/js/mixin/shipping-rate-registry-mixin': true
            },
            'Magento_Checkout/js/view/progress-bar': {
                'Ecomteck_OneStepCheckout/js/mixin/progress-bar-mixin': true
            },
            'Magento_Checkout/js/view/payment': {
                'Ecomteck_OneStepCheckout/js/mixin/payment-mixin': true
            },
            'Magento_Checkout/js/view/payment/default': {
                'Ecomteck_OneStepCheckout/js/mixin/payment-default-mixin': true
            },
            'Magento_Checkout/js/view/summary/cart-items': {
                'Ecomteck_OneStepCheckout/js/mixin/cart-items-mixin': true
            },
            'Magento_Checkout/js/view/summary/shipping': {
                'Ecomteck_OneStepCheckout/js/mixin/summary-shipping-mixin': true
            },
            'Magento_Checkout/js/view/summary/abstract-total': {
                'Ecomteck_OneStepCheckout/js/mixin/abstract-total-mixin': true
            },
            'Magento_Checkout/js/view/form/element/email': {
                'Ecomteck_OneStepCheckout/js/mixin/email-mixin': true
            },
            'Magento_Checkout/js/model/shipping-rates-validator': {
                'Ecomteck_OneStepCheckout/js/mixin/shipping-rates-validator-mixin': true
            },
            'Magento_Checkout/js/model/shipping-service': {
                'Ecomteck_OneStepCheckout/js/mixin/shipping-service-mixin': true
            },
            'Magento_Checkout/js/action/place-order': {
                'Magento_CheckoutAgreements/js/model/place-order-mixin': false,
                'Ecomteck_OneStepCheckout/js/mixin/place-order-mixin': true
            }
        }
    }
};