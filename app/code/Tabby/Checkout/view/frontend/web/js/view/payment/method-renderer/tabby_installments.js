define(
    [
        'jquery',
        'ko',
		'Magento_Checkout/js/model/quote',
        'Tabby_Checkout/js/view/payment/method-renderer/tabby_base',
        'mage/translate'
    ],
    function ($, ko, quote, Component) {
        'use strict';

        return Component.extend({
			
			isdisplaytabinstllment: function(){
				var quotetotal = quote.getTotals()();
				var quoteGrandtotal=quotetotal['base_grand_total'];
				if(quoteGrandtotal >= this.tabbyfourday && quoteGrandtotal <= this.tabbymaxinstallmentprice ){
					return true;
				}else{
					return false;
				}
			 },
			
            isTabbyPlaceOrderActionAllowed: ko.observable(false),
            isRejected: ko.observable(false),
			
			tabbymaxinstallmentprice:window.checkoutConfig.tabbymaxinstallmentprice,
			tabbyfourday:window.checkoutConfig.tabbyfourday,
			
			isdisplaytabinstllment: function(){
				var quotetotal = quote.getTotals()();
				var quoteGrandtotal=quotetotal['base_grand_total'];
				if(quoteGrandtotal >= this.tabbyfourday && quoteGrandtotal <= this.tabbymaxinstallmentprice ){
					return true;
				}else{
					return false;
				}
			 },

            initialize: function () {
                this._super(),
                    this.register(this);
            },

            getCode: function () {
                return 'tabby_installments';
            },

            getTabbyCode: function () {
                return 'installments';
            },

            getMethodDescription: function () {
                return $.mage.__('Use any card.');
            },
            createTabbyCard: function (payment) {
                return new TabbyCard(this.getTabbyCardConfig(payment));
            }
        });
    }
);
