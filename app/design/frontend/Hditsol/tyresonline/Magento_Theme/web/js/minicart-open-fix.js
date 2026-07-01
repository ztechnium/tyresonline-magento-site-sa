require([
    'jquery',
    'domReady!',
    'Magento_Customer/js/customer-data',
    'mage/translate'
], function ($, domReady, customerData, $t) {
    'use strict';

    var minicartSelector = '[data-block="minicart"]';

    function wrapperHasContent() {
        var wrapper = document.getElementById('minicart-content-wrapper');

        if (!wrapper) {
            return false;
        }

        return !!wrapper.querySelector('.block-content, .minicart-items, .subtitle');
    }

    function ensureFallback() {
        var $wrapper = $('#minicart-content-wrapper');

        if (!$wrapper.length || wrapperHasContent()) {
            $wrapper.find('.minicart-static-fallback').remove();
            return;
        }

        if ($wrapper.find('.minicart-static-fallback').length) {
            return;
        }

        var cart = customerData.get('cart')();
        var message = cart && cart.summary_count ?
            $t('Loading cart...') :
            $t('You have no items in your shopping cart.');

        $wrapper.prepend(
            '<div class="minicart-static-fallback">' +
                '<strong class="subtitle empty">' + message + '</strong>' +
            '</div>'
        );
    }

    function refreshMinicartContent() {
        if (wrapperHasContent()) {
            return;
        }

        customerData.invalidate(['cart']);
        customerData.reload(['cart'], true);
        ensureFallback();
    }

    $(minicartSelector).on('dropdowndialogopen', function () {
        ensureFallback();
        refreshMinicartContent();
    });

    $(document).on('contentUpdated', minicartSelector, function () {
        $('#minicart-content-wrapper .minicart-static-fallback').remove();
    });

    customerData.get('cart').subscribe(function () {
        if ($('.minicart-wrapper').hasClass('active')) {
            ensureFallback();
        }
    });

    ensureFallback();
});
