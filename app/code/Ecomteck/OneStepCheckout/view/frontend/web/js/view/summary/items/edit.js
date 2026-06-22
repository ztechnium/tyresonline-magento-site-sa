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
        'Magento_Checkout/js/model/quote'
    ],
    function (
        Component,
        $,
        modal,
        shippingRateService,
        getTotalsAction,
        storage,
        urlBuilder,
        quote
    ) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Ecomteck_OneStepCheckout/summary/items/details/edit',
                formTemplate: 'Ecomteck_OneStepCheckout/summary/items/details/edit/form',
            },
            /**
             * Component init
             */
            initialize: function () {
                var self = this;
                this.elementPopup = {};
                this._super();
            },
            /**
             * @return {Boolean}
             */
            isActive: function (quoteItem) {
                return window.checkoutConfig.onestepcheckout.allowEditProductOption;
            },
            getEditUrl: function (quoteItem) {
                return urlBuilder.build('/onestepcheckout/cart/configure/id/'+quoteItem['item_id']+'/');
            },
            getImagesUrl: function () {
                return urlBuilder.build('onestepcheckout/cart/images/');
            },
            showModalFormBlock: function (quoteItem) {
                var self = this;
                if (!this.elementPopup[quoteItem['item_id']]) {
                    var options = {
                        type: 'slide',
                        responsive: true,
                        innerScroll: true,
                        buttons: [],

                        opened:function () {
                            $('#edit-item-popup-modal-'+quoteItem['item_id']).find('iframe').css('height',$(window).height()-100);
                            
                        }
                    };
                    this.elementPopup[quoteItem['item_id']] = $('<iframe src="'+this.getEditUrl(quoteItem)+'" style="width:100%;height:500px;border:none"></iframe>');
                    this.elementPopup[quoteItem['item_id']].css('height',$(window).height()-200);
                    modal(options, this.elementPopup[quoteItem['item_id']]);
                }
                var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
                var eventer = window[eventMethod];
                var messageEvent = eventMethod == "attachEvent" ? "onmessage" : "message";

                // Listen to message from child window
                eventer(messageEvent,function (e) {
                    if (e.data == 'close-edit-item-popup') {
                        var serviceUrl = self.getImagesUrl();
                        storage.get(
                            serviceUrl
                        ).done(function (response) {
                            
                            if (response.success && response.imageData) {
                                window.checkoutConfig.imageData = response.imageData;
                            }

                            shippingRateService.reload();
                            getTotalsAction(function () {
                                
                            });
                        });
                        if (self.elementPopup[quoteItem['item_id']]) {
                            self.elementPopup[quoteItem['item_id']].modal('closeModal');
                        }
                        setTimeout(function () {
                            if (self.elementPopup[quoteItem['item_id']]) {
                                self.elementPopup[quoteItem['item_id']].empty();
                                self.elementPopup[quoteItem['item_id']] = null;
                            }
                        },3000);
                    }
                },false);
                this.elementPopup[quoteItem['item_id']].modal('openModal');
            }
        });
    }
);
