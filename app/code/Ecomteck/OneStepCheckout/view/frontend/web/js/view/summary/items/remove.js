/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'uiComponent',
        'jquery',
        'Magento_Ui/js/modal/modal',
        'Ecomteck_OneStepCheckout/js/model/shipping-rate-service',
        'Magento_Checkout/js/action/get-totals',
        'mage/storage',
        'mage/url',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/model/messageList',
        'mage/translate'
    ],
    function (
        Component,
        $,
        modal,
        shippingRateService,
        getTotalsAction,
        storage,
        urlBuilder,
        quote,
        globalMessageList,
        $t
    ) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Ecomteck_OneStepCheckout/summary/items/details/remove',
            },
            /**
             * Component init
             */
            initialize: function () {
                this._super();
            },
            /**
             * @return {Boolean}
             */
            isActive: function (quoteItem) {
                return window.checkoutConfig.onestepcheckout.allowRemoveProduct;
            },
            getRemoveUrl: function (quoteItem) {
                return window.checkoutConfig.onestepcheckout.removeItemUrl;
            },
            removeItem : function (quoteItem) {
                if (this.xhr) {
                    this.xhr.abort();
                }
                this.xhr = $.post(this.getRemoveUrl(),{
                    item_id:quoteItem['item_id'],
                    form_key:window.checkoutConfig.formKey
                },function (response) {
                    if (!response.success) {
                        if (response.error_message) {
                            globalMessageList.addErrorMessage({message:response.error_message});
                        }
                    }
                    if (response.redirect) {
                        window.location.reload();
                    } else {
                        if (quote.isVirtual()) {
                            getTotalsAction(function () {
    
                            });
                        } else {
                            shippingRateService.reload();
                            getTotalsAction(function () {
                                
                            });
                        }
                    }
                }).fail(function () {
                    globalMessageList.addErrorMessage({message:$t('Can not remove item.')});
                });
            }
        });
    }
);
