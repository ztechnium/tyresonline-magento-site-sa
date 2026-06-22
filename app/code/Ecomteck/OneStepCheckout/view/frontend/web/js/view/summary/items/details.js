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
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Checkout/js/model/shipping-save-processor/default',
        'Ecomteck_OneStepCheckout/js/model/shipping-rate-service',
        'Magento_Checkout/js/action/get-totals',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/model/messageList',
        'mage/translate'
    ],
    function (
        Component,
        $,
        checkoutDataResolver,
        shippingSaveProcessor,
        shippingRateService,
        getTotalsAction,
        quote,
        globalMessageList,
        $t
    ) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Ecomteck_OneStepCheckout/summary/items/details'
            },
            getValue: function (quoteItem) {
                return quoteItem.name;
            },
            setItemId: function (itemId) {
                this.item_id = itemId;
            },
            getItemId: function () {
                return this.item_id;
            },
            isEnabledChangeQty: function () {
                return window.checkoutConfig.onestepcheckout.allowUpdateQty;
            },
            incrementQty: function (e) {
                this.qty++;
                $('#input-qty-'+this.item_id).val(this.qty).change();
            },
            decrementQty: function (e) {
                if (this.qty == 1) {
                    return;
                }
                this.qty--;
                $('#input-qty-'+this.item_id).val(this.qty).change();
            },
            updateItemQty : function (e) {
                if (this.xhr) {
                    this.xhr.abort();
                }
                this.xhr = $.post(window.checkoutConfig.onestepcheckout.updateItemQtyUrl,{
                    item_id:this.item_id,
                    item_qty:this.qty,
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
                }).fail(function (response) {
                    globalMessageList.addErrorMessage({message:$t('Can not remove item.')});
                });
            },
            removeItem : function (e) {
                if (this.xhr) {
                    this.xhr.abort();
                }
                this.xhr = $.post(window.checkoutConfig.onestepcheckout.removeItemUrl,{
                    item_id:this.item_id,
                    item_qty:this.qty,
                    form_key:window.checkoutConfig.formKey
                },function (response) {
                    if (!response.success) {
                        if (response.error_message) {
                            globalMessageList.addErrorMessage({message:response.error_message});
                        }
                    }
                    if (quote.isVirtual()) {
                        getTotalsAction(function () {

                        });
                    } else {
                        shippingRateService.reload();
                        getTotalsAction(function () {
                            
                        });
                    }
                }).fail(function (response) {
                    globalMessageList.addErrorMessage({message:$t('Can not remove item.')});
                });
            }
        });
    }
);
