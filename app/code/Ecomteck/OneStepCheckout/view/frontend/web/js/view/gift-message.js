define(
    [
        'Magento_GiftMessage/js/view/gift-message',
        'Ecomteck_OneStepCheckout/js/model/gift-message',
        'Magento_GiftMessage/js/model/gift-options',
        'Magento_GiftMessage/js/action/gift-options'
    ],
    function (Component, GiftMessage, giftOptions, giftOptionsService) {
        'use strict';
        return Component.extend({
            /**
             * Component init
             */
            initialize: function () {
                var self = this,
                    model;
                this._super();
                this.itemId = this.itemId || 'orderLevel';
                model = new GiftMessage(this.itemId);
                giftOptions.addOption(model);
                this.model = model;

                this.model.getObservable('isClear').subscribe(function (value) {
                    if (value == true) {
                        self.formBlockVisibility(false);
                        self.model.getObservable('alreadyAdded')(true);
                    }
                });
            },
            isActive: function () {
                return this._super() && !window.checkoutConfig.onestepcheckout.display.disableGiftOptions;
            },
            getInitialCollapseState: function () {
                return window.checkoutConfig.onestepcheckout.display.giftOptionsCollapseState;
            },
            isInitialStateOpened: function () {
                return this.getInitialCollapseState() === 1
            }
        });
    }
);
