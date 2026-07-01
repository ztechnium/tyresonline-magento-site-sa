require([
    'jquery',
    'domReady!',
    'Magento_Customer/js/customer-data',
    'mage/translate'
], function ($, domReady, customerData, $t) {
    'use strict';

    var minicartSelector = '[data-block="minicart"]';
    var reloadPending = false;

    function hasRenderedMinicart() {
        var wrapper = document.getElementById('minicart-content-wrapper');

        if (!wrapper) {
            return false;
        }

        return !!wrapper.querySelector('.block-content, .minicart-items');
    }

    function removeFallback() {
        $('#minicart-content-wrapper .minicart-static-fallback').remove();
    }

    function showEmptyCartFallback() {
        var $wrapper = $('#minicart-content-wrapper');

        if (!$wrapper.length || hasRenderedMinicart()) {
            removeFallback();
            return;
        }

        if ($wrapper.find('.minicart-static-fallback').length) {
            return;
        }

        $wrapper.prepend(
            '<div class="minicart-static-fallback">' +
                '<strong class="subtitle empty">' +
                    $t('You have no items in your shopping cart.') +
                '</strong>' +
            '</div>'
        );
    }

    function refreshMinicartContent() {
        if (hasRenderedMinicart() || reloadPending) {
            return;
        }

        reloadPending = true;
        customerData.reload(['cart'], false).done(function () {
            $(minicartSelector).trigger('contentUpdated');
        }).always(function () {
            reloadPending = false;
            if (hasRenderedMinicart()) {
                removeFallback();
            }
        });
    }

    function handleMinicartOpen() {
        removeFallback();

        if (hasRenderedMinicart()) {
            return;
        }

        var cart = customerData.get('cart')();

        if (!cart || !cart.summary_count) {
            showEmptyCartFallback();
            return;
        }

        refreshMinicartContent();
    }

    $(minicartSelector).on('dropdowndialogopen', handleMinicartOpen);

    $(document).on('click', minicartSelector + ' .action.showcart', function () {
        window.setTimeout(function () {
            if ($(minicartSelector).hasClass('active')) {
                handleMinicartOpen();
            }
        }, 0);
    });

    $(document).on('contentUpdated', minicartSelector, function () {
        removeFallback();
    });

    customerData.get('cart').subscribe(function () {
        if (hasRenderedMinicart()) {
            removeFallback();
            return;
        }

        if ($(minicartSelector).hasClass('active')) {
            var cart = customerData.get('cart')();

            if (!cart || !cart.summary_count) {
                showEmptyCartFallback();
            }
        }
    });
});
