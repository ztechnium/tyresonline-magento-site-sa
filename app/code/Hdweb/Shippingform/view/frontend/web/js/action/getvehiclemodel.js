/*global define,alert*/
define(
        [
            'ko',
            'jquery',
            'mage/storage',
            'mage/translate',
            'Magento_Checkout/js/model/step-navigator',
        ],
        function (
                ko,
                $,
                storage,
                $t,
                stepNavigator
                ) {
            'use strict';
            return function (area, responce) {
                $('body').trigger('processStart');
                return storage.post(
                        'shippingform/ajax/getvehiclemodel',
                        JSON.stringify(area),
                        false
                        ).done(
                        function (response) {
                            if (response) {
                                $.each(response, function (i, v) {         
                                        $("select[name='model']").html(v.vehiclemodel);
                                        $("select[name='year']").html(v.vehicleyear);
                                });
                            }
                            $('body').trigger('processStop');
                        }

                ).fail(
                        function (response) {
                        }
                );
            };
        }
);