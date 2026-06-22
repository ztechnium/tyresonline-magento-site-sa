define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'Ecomteck_OneStepCheckout/js/model/gift-message',
        'Magento_GiftMessage/js/model/gift-options',
        'Magento_GiftMessage/js/action/gift-options',
        'Magento_Ui/js/modal/modal'
    ],
    function ($,ko,Component, GiftMessage, giftOptions, giftOptionsService,modal) {
        'use strict';

        return Component.extend({
            model: {},

            /**
             * Component init
             */
            initialize: function () {
                var self = this,
                    model;
                this.model = {};
                this.elementPopup = {};
                this._super();
            },

            /**
             * @param {String} key
             * @return {*}
             */
            getObservable: function (item,key) {
                if (!this.model[item['item_id']]) {
                    this.itemId = item['item_id'];
                    var model = new GiftMessage(item['item_id']);
                    giftOptions.addOption(model);
                    this.model[item['item_id']] = model;
                }
                return  this.model[item['item_id']].getObservable(key);
                /*
                var value = '';
                if(value == ''){
                    if(window.giftOptionsConfig.giftMessage.itemLevel && window.giftOptionsConfig.giftMessage.itemLevel[item['item_id']] && window.giftOptionsConfig.giftMessage.itemLevel[this.itemId]['message'] && window.giftOptionsConfig.giftMessage.itemLevel[this.itemId]['message'][key]){
                        value =  window.giftOptionsConfig.giftMessage.itemLevel[this.itemId]['message'][key];
                    }
                } else {
                    value = this.model.getObservable(key);
                }


                return value;*/
            },

            hideFormBlock : function (item) {
                if (this.elementPopup[item['item_id']]) {
                    this.elementPopup[item['item_id']].modal('closeModal');
                }
            },

            /**
             * Delete options
             */
            deleteOptions: function () {
                giftOptionsService(this.model, true);
            },

            /**
             * @return {Boolean}
             */
            isActive: function (item) {
                if (!window.checkoutConfig.onestepcheckout.giftMessage.perItem || !window.giftOptionsConfig.isItemLevelGiftOptionsEnabled) {
                    return false;
                }
                if (!item) {
                    return false;
                }
                if (!this.model[item['item_id']]) {
                    this.itemId = item['item_id'];
                    var model = new GiftMessage(item['item_id']);
                    giftOptions.addOption(model);
                    this.model[item['item_id']] = model;
                }
                if (window.giftOptionsConfig.isItemLevelGiftOptionsEnabled) {
                    if (window.giftOptionsConfig.giftMessage.itemLevel && window.giftOptionsConfig.giftMessage.itemLevel[item['item_id']] && window.giftOptionsConfig.giftMessage.itemLevel[item['item_id']]['is_available'] == false) {
                        return false;
                    }
                    return true;
                }
                return false;
            },

            /**
             * Submit options
             */
            submitOptions: function (item) {
                giftOptionsService(this.model[item['item_id']]);
                if (this.elementPopup[item['item_id']]) {
                    this.elementPopup[item['item_id']].modal('closeModal');
                }
            },
            showModalFormBlock: function (item) {
                if (!this.elementPopup[item['item_id']]) {
                    var options = {
                        type: 'popup',
                        responsive: true,
                        innerScroll: true,
                        title: 'Gift Message',
                        buttons: []
                    };
                    this.elementPopup[item['item_id']] = $('#gift-message-popup-modal-'+item['item_id']);
                    modal(options, this.elementPopup[item['item_id']]);
                }
    
                this.elementPopup[item['item_id']].modal('openModal');
            }
        });
    }
);
