define([
    'jquery',
    'uiComponent',
    'mage/validation',
    'ko',
    'Hdweb_Shippingform/js/action/getvehiclemodel',
    'Hdweb_Shippingform/js/action/getvehicleyear',
    'Hdweb_Shippingform/js/action/getarea'
], function ($, Component, validation, ko, getvehiclemodelAction, getvehicleyearAction, getareaAction) {
    'use strict';


    $(document).on('change', "select[name='make']", function() {
      var selectedmodel = $("select[name='make']").val();
             if (selectedmodel) {
                getvehiclemodelAction(selectedmodel);
            }
    });
    $(document).on('change', "select[name='model']", function() {
      var selectevehicledmodel = $("select[name='make']").val();
             var selectedyear = $("select[name='model']").val();
             var data = {'make': selectevehicledmodel, 'model': selectedyear};
             if (data) {
                getvehicleyearAction(data);
            }
    });

    $(document).on('change', "select[name='city']", function() {
      var selectedCity = $("select[name='city']").val();
             if (selectedCity) {
                getareaAction(selectedCity);
            }
    });

    return Component.extend({
        initialize: function () {
            this._super();
            // component initialization logic
            return this;
        }

    });
});