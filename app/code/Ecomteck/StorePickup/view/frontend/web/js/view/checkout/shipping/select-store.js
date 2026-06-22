/**
 * Ecomteck_StorePickup Magento Extension
 *
 * @category    Ecomteck
 * @package     Ecomteck_StorePickup
 * @author      Ecomteck <ecomteck@gmail.com>
 * @website    http://www.ecomteck.com
 */

define([
    'uiComponent',
    'ko',
    'jquery',
    'mage/translate',
    'moment',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/quote',
    'Ecomteck_StoreLocator/js/libs/handlebars.min',
    'Ecomteck_StorePickup/js/jquery.timepicker.min'
], function (Component, ko, $, $t, moment, modal, quote, handlebars) {
    'use strict';
    window.Handlebars = handlebars;
    var popUp = null;

    return Component.extend({
        defaults: {
            template: 'Ecomteck_StorePickup/checkout/shipping/select-store'
        },
        isStorePickup: ko.observable(false),
        isSelectStoreVisible: ko.observable(false),
        isMapVisible: ko.observable(false),
        selectedStore: ko.observable(),
        pickupDate : ko.observable(''),

        initialize: function () {
        	var self = this;
        	quote.shippingMethod.subscribe(function () {
                if(quote.shippingMethod()) {
                    if (quote.shippingMethod().carrier_code == 'storepickup') {
                        self.isStorePickup(true);
                        var stores = $.parseJSON(window.checkoutConfig.shipping.select_store.stores);
                        if(stores.totalRecords >= 1) {
                            self.isSelectStoreVisible(true);
                        }
                    } else {
                        self.isStorePickup(false);
                    }
                } else {
                    self.isStorePickup(false);
                }
            	
            });

            this.isMapVisible.subscribe(function (value) {
                if (value) {
                    self.getPopUp().openModal();
                } else {
                    self.getPopUp().closeModal();
                }
            });

            ko.bindingHandlers.storepickupdatepicker = {
                init: function (element, valueAccessor, allBindingsAccessor) {
                    //console.log(allBindingsAccessor()['selectedStore']);
                    var $el = $(element);
                    $el.datetimepicker({
                        'showTimepicker': false,
                        'format': 'yyyy-MM-dd',
                        'onSelect' : function(date){
                            self.pickupDate(date);
                            return true;
                        }
                    });
                    var writable = valueAccessor();
                    if (!ko.isObservable(writable)) {
                        var propWriters = allBindingsAccessor()._ko_property_writers;
                        if (propWriters && propWriters.storepickupdatepicker) {
                            writable = propWriters.storepickupdatepicker;
                        } else {
                            return;
                        }
                    }
                    writable($(element).datetimepicker("getDate"));
                },
                update: function (element, valueAccessor,allBindingsAccessor) {
                    var selectedStore = allBindingsAccessor()['selectedStore'];
                    var widget = $(element).data("datepicker");
                    if (widget) {
                        widget.settings.beforeShowDay = function(date) {
                            var day = date.getDay();
                            var dateFormated = moment(date).format('MM/D/YYYY');
                            var current = moment().format('YYYY-MM-DD');
                            if(moment(moment(date).format('YYYY-MM-DD')).isBefore(moment().format('YYYY-MM-DD'))){
                                //console.log(moment(date).format('YYYY-MM-DD'));
                                return [false];
                            }
                            if(selectedStore && selectedStore.special_opening_hours && selectedStore.special_opening_hours[dateFormated]&& selectedStore.special_opening_hours[dateFormated].length){
                                return [true];
                            }
                            if(selectedStore && selectedStore.opening_hours && selectedStore.opening_hours[day] && selectedStore.opening_hours[day].length){
                                return [true];
                            }
                            
                            return [true];
                        }
                    }
                }
            };

            ko.bindingHandlers.storepickuptimepicker = {
                init: function (element, valueAccessor, allBindingsAccessor) {
                    var $el = $(element);
                    
                    $el.timepicker();
                },
                update: function (element, valueAccessor,allBindingsAccessor) {
                    var selectedStore = allBindingsAccessor()['selectedStore'];
                    var $el = $(element);
                    var date = valueAccessor();
                    var dateFormated = moment(date,'MM/D/YYYY').format('MM/D/YYYY');
                    var day = moment(date,'MM/D/YYYY').day();
                    var enableTimes = {};
                    var disabledTimes = [];
                    if(selectedStore && selectedStore.special_opening_hours && selectedStore.special_opening_hours[dateFormated]){
                        if(selectedStore.special_opening_hours[dateFormated].length){
                            
                            for(var i=0;i<24;i++){
                                for(var j=0;j<selectedStore.special_opening_hours[dateFormated].length;j++){
                                    var timeSlot = selectedStore.special_opening_hours[dateFormated][j];
                                    var startTime = timeSlot[0];
                                    var endTime = timeSlot[1];
                                    var format = 'HH:mm';
                                    var time = moment(i+':00',format);
                                    var timeHaft = moment(i+':30',format);
                                    var beforeTime = moment(startTime, format);
                                    var afterTime = moment(endTime, format);
                                    if(time.isBetween(beforeTime, afterTime)){
                                        enableTimes[time.format('h:mma')] = true;
                                    }
                                    
                                    if(timeHaft.isBetween(beforeTime, afterTime)){
                                        enableTimes[timeHaft.format('h:mma')] = true;
                                    }
                                }
                            }
                            for(var i=0;i<24;i++){
                                var format = 'HH:mm';
                                var time = moment(i+':00',format);
                                var timeHaft = moment(i+':30',format);
                                if(typeof(enableTimes[time.format('h:mma')]) == 'undefined'){
                                    var disableTime = moment(i+':00', format).format('h:mma');
                                    var disableTime2 = moment(i+':29', format).format('h:mma');
                                    disabledTimes.push([disableTime,disableTime2]);
                                }

                                if(typeof(enableTimes[timeHaft.format('h:mma')]) == 'undefined'){
                                    var disableHalfTime = moment(i+':30', format).format('h:mma');
                                    var disableHalfTime2 = moment(i+':59', format).format('h:mma');
                                    disabledTimes.push([disableHalfTime,disableHalfTime2]);
                                }
                            }
                            $el.timepicker('option','disableTimeRanges',disabledTimes);
                            return;
                        }
                    }
                    if(selectedStore.opening_hours[day]){
                        if(selectedStore.opening_hours[day].length){
                            for(var j=0;j<selectedStore.opening_hours[day].length;j++){
                                for(var i=0;i<24;i++){
                                    var timeSlot = selectedStore.opening_hours[day][j];
                                    var startTime = timeSlot[0];
                                    var endTime = timeSlot[1];
                                    var format = 'HH:mm';
                                    var time = moment(i+':00',format);
                                    var timeHaft = moment(i+':30',format);
                                    var beforeTime = moment(startTime, format);
                                    var afterTime = moment(endTime, format);
                                    if(time.isBetween(beforeTime, afterTime)){
                                        enableTimes[time.format('h:mma')] = true;
                                    }
                                    
                                    if(timeHaft.isBetween(beforeTime, afterTime)){
                                        enableTimes[timeHaft.format('h:mma')] = true;
                                    }
                                }
                                
                            }
                            for(var i=0;i<24;i++){
                                var format = 'HH:mm';
                                var time = moment(i+':00',format);
                                var timeHaft = moment(i+':30',format);
                                if(typeof(enableTimes[time.format('h:mma')]) == 'undefined'){
                                    var disableTime = moment(i+':00', format).format('h:mma');
                                    var disableTime2 = moment(i+':29', format).format('h:mma');
                                    disabledTimes.push([disableTime,disableTime2]);
                                }

                                if(typeof(enableTimes[timeHaft.format('h:mma')]) == 'undefined'){
                                    var disableHalfTime = moment(i+':30', format).format('h:mma');
                                    var disableHalfTime2 = moment(i+':59', format).format('h:mma');
                                    disabledTimes.push([disableHalfTime,disableHalfTime2]);
                                }
                            }
                            
                            $el.timepicker('option','disableTimeRanges',disabledTimes);
                        }
                        
                    }
                }
            };

            return this._super();
        },

        showMap: function () {
            this.isMapVisible(true);
        },

        getPopUp: function () {
            var self = this,
                buttons;

            if (!popUp) {
               var config = window.checkoutConfig.shipping.storelocator;
               config.callbackListClick = function(index,marker,store){
                    self.selectedStore(store);
               };
                $.getScript("https://maps.googleapis.com/maps/api/js?v=3&sensor=false&key="+config.apiKey+"&libraries=geometry,places", function () {
					require(['Ecomteck_StoreLocator/js/plugins/storeLocator/jquery.storelocator'],function(){
						$('#bh-sl-map-container').storeLocator(config);
					});
                });
                
                popUp = modal({
                	'responsive': true,
                	'innerScroll': false,
                    'buttons': [
                        {
                            text: $t('Pick Up Here!'),
                            class: 'button action primary',
                            click: function() {
                                if(self.selectedStore() && self.selectedStore().stores_id){
                                    $('#pickup-store').val(self.selectedStore().stores_id);
                                    $('#pickup-store-country').val(self.selectedStore().country_id);
                                    $('#pickup-store-city').val(self.selectedStore().city);
                                    $('#pickup-store-postcode').val(self.selectedStore().postcode);
                                    $('#pickup-store-region').val(self.selectedStore().region);
                                    $('#pickup-store-region-id').val(self.selectedStore().region_id);
                                    $('#pickup-store-address').val(self.selectedStore().address);
                                    $('#selected-store-msg')
                                        .show()
                                        .find('span')
                                        .text( self.selectedStore().name);
                                    self.isMapVisible(false);
                                } else {
                                    alert('Please choose store for pickup');
                                }
                                
                            }
                        },
                        {
                            text: $t('Close'),
                            class: '',
                            click: function() {
                                self.isMapVisible(false);
                            }
                        }
                    ],
                    'modalClass': 'mc_cac_map',
                	closed: function() {
            			self.isMapVisible(false)
            		}
                }, $('#store-locator'));
            }
            return popUp;
        }
    });
});