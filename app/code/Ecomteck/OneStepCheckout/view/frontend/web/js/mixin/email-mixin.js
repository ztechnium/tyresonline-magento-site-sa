/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
define(['jquery','Magento_Checkout/js/checkout-data'], function ($,checkoutData) {
    'use strict';

    /**
     * - Set our own email (login) template.
     * - Reduce the check delay down from 2 seconds.
     */
    return function (target) {
        if (typeof(window.checkoutConfig.onestepcheckout) == 'undefined') {
            return target.extend({});
        }
        return target.extend({
            defaults: {
                template: 'Ecomteck_OneStepCheckout/form/element/email',
                email: checkoutData.getInputFieldEmailValue(),
                emailFocused: false,
                isLoading: false,
                isPasswordVisible: false,
                listens: {
                    email: 'emailHasChanged',
                    emailFocused: 'validateEmail'
                }
            },

            initialize: function () {
                this.checkDelay = 500;
                return this._super();
            },
            hasRegisterForm: function () {
                return $('#social-login-popup').length;
            },
            openRegisterForm: function () {
                if (!this.registerform) {
                    this.registerform = $('#social-login-popup').modal({
                        title: $('.create-account-title').text(),
                        responsive: true,
                        autoOpen: true,
                        buttons: [],
                        opened : function () {
                            $(this).removeClass('mfp-hide');
                            $('.social-login.authentication').hide();
                            $('.social-login.create').show();
                        }
                    });
                } else {
                    this.registerform.modal('openModal');
                }
                
            }
        });
    }
});