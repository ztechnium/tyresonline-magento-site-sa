/*jshint browser:true*/
/*global define*/
define(
    [
        'ko',
        'underscore',
        'jquery',
        'Magento_Ui/js/form/form',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/create-billing-address',
        'Magento_Checkout/js/action/select-billing-address',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/action/set-billing-address',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'Magento_Checkout/js/model/billing-address-postcode-validator'
    ],
    function (
        ko,
        _,
        $,
        Component,
        customer,
        addressList,
        quote,
        createBillingAddress,
        selectBillingAddress,
        checkoutData,
        checkoutDataResolver,
        customerData,
        setBillingAddressAction,
        globalMessageList,
        $t,
        billingAddressPostcodeValidator
    ) {
        'use strict';
            var lastSelectedBillingAddress = null,
            newAddressOption = {
                    /**
                     * Get new address label
                     * @returns {String}
                     */
                getAddressInline: function () {
                    return $t('New Address');
                },
                customerAddressId: null
            },
            countryData = customerData.get('directory-data'),
            addressOptions = addressList().filter(function (address) {
                return address.getType() == 'customer-address';
            }),
            addressDefaultIndex = addressOptions.findIndex(function (address) {
                return address.isDefaultBilling();
            });;

            addressOptions.push(newAddressOption);

            return Component.extend({
                defaults: {
                    template: 'Ecomteck_OneStepCheckout/billing-address-mixin'
                },
                currentBillingAddress: quote.billingAddress,
                addressOptions: addressOptions,
                customerHasAddresses: addressOptions.length > 1,

                /**
                 * Init component
                 */
                initialize: function () {
                    this._super();
                    quote.paymentMethod.subscribe(function () {
                        checkoutDataResolver.resolveBillingAddress();
                    }, this);
                    billingAddressPostcodeValidator.initFields(this.get('name') + '.form-fields');
                    if(quote.billingAddress){
                        lastSelectedBillingAddress = quote.billingAddress
                    }
                },

                /**
                 * @return {exports.initObservable}
                 */
                initObservable: function () {
                    this._super()
                        .observe({
                            selectedAddress: null,
                            isNewAddressSelected: null,
                            isAddressFormVisible: quote.isVirtual() || window.checkoutConfig.onestepcheckout.moveBillingAddressBeforeShippingAddressEnabled || window.checkoutConfig.onestepcheckout.uncheckBillingAddressIsSameShippingAddress,
                            isAddressSameAsShipping: !window.checkoutConfig.onestepcheckout.uncheckBillingAddressIsSameShippingAddress,
                            saveInAddressBook: 1,
                            isAddressFormListVisible:false
                        })
                        .observe({
                            isNewAddressSelected: !customer.isLoggedIn() || !addressOptions.length,
                            selectedAddress: this.addressOptions[addressDefaultIndex]
                        });
        
                    return this;
                },

                canUseShippingAddress: ko.computed(function () {
                    return !quote.isVirtual() && quote.shippingAddress() && quote.shippingAddress().canUseForBilling() && !window.checkoutConfig.onestepcheckout.moveBillingAddressBeforeShippingAddressEnabled;
                }),

                quoteIsVirtual: ko.observable(quote.isVirtual()),

                /**
                 * @param {Object} address
                 * @return {*}
                 */
                addressOptionsText: function (address) {
                    return address.getAddressInline();
                },

                isBillingBeforeShipping: function () {
                    return window.checkoutConfig.onestepcheckout.moveBillingAddressBeforeShippingAddressEnabled;
                },

                /**
                 * @return {Boolean}
                 */
                useShippingAddress: function () {
                    if (this.isAddressSameAsShipping() && !this.isBillingBeforeShipping()) {
                        this.isAddressFormVisible(false);
                        this.isAddressFormListVisible(false);
                        //selectBillingAddress(quote.shippingAddress());
                        $(".checkout-billing-address > .fieldset").hide();
                    } else {
                        lastSelectedBillingAddress = quote.billingAddress();
                        $(".checkout-billing-address > .fieldset").show();
                        $(".checkout-billing-address .billing-address-form").show();
                        if (addressOptions.length == 1) {
                            this.isAddressFormVisible(true);
                        } else {
                            this.isAddressFormListVisible(true);
                        }
                    }
                    //checkoutData.setSelectedBillingAddress(null);
                    return true;
                },

                isRequired: function (isRequired) {
                    if (isRequired) {
                        this.isAddressSameAsShipping(false);
                        this.isAddressFormVisible(true);
                    }
                    
                },
                /**
                 * @param {Object} address
                 */
                onAddressChange: function (address) {
                    if (address) {
                        this.isAddressFormVisible(false);
                    } else {
                        this.isAddressFormVisible(true);
                    }
                    this.isNewAddressSelected(address === newAddressOption);
                },

                /**
                 * @param {int} countryId
                 * @return {*}
                 */
                getCountryName: function (countryId) {
                    return countryData()[countryId] != undefined ? countryData()[countryId].name : '';
                },

                /**
                 * Get code
                 * @param {Object} parent
                 * @returns {String}
                 */
                getCode: function (parent) {
                    return _.isFunction(parent.getCode) ? parent.getCode() : 'shared';
                },

                validateBillingInformation: function () {
                    var validateResult=true;

                    var selectedAddress = $('[name="billing_address_id"]').val();
                    if (selectedAddress) {
                        var res = addressList.some(function (addressFromList) {
                            if (selectedAddress == addressFromList.customerAddressId) {
                                selectBillingAddress(addressFromList);
                                return true;
                            }
                            return false;
                        });

                        validateResult = validateResult && res;
                    } else {
                        this.source.set('params.invalid', false);
                        this.source.trigger('billingAddress.data.validate');

                        if (this.source.get('params.invalid')) {
                            validateResult = validateResult && false;
                        }

                        var addressData = this.source.get('billingAddress'),
                            newBillingAddress;

                        if ($('#billing-save-in-address-book').is(":checked")) {
                            addressData.save_in_address_book = 1;
                        }

                        newBillingAddress = createBillingAddress(addressData);
                        selectBillingAddress(newBillingAddress);

                        validateResult = validateResult && true;
                    }
                    
                    setBillingAddressAction();
                    return validateResult;
                }
        });
    }
);