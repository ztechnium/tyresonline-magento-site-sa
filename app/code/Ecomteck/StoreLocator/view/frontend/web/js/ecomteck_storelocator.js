define([
    'jquery',
    'Ecomteck_StoreLocator/js/libs/handlebars.min'
], function ($, Handlebars) {
    'use strict';

    window.Handlebars = Handlebars;

    function loadGoogleMaps(apiKey) {
        return $.Deferred(function (deferred) {
            if (window.google && window.google.maps) {
                deferred.resolve();
                return;
            }

            var attempts = 0;
            var timer = setInterval(function () {
                if (window.google && window.google.maps) {
                    clearInterval(timer);
                    deferred.resolve();
                    return;
                }

                if (++attempts < 50) {
                    return;
                }

                clearInterval(timer);

                if (!apiKey) {
                    deferred.reject(new Error('Store locator: missing Google Maps API key'));
                    return;
                }

                $.getScript(
                    'https://maps.googleapis.com/maps/api/js?v=3&sensor=false&key=' +
                        encodeURIComponent(apiKey) +
                        '&libraries=geometry,places'
                ).done(function () {
                    deferred.resolve();
                }).fail(function () {
                    deferred.reject(new Error('Store locator: failed to load Google Maps'));
                });
            }, 200);
        }).promise();
    }

    function initStoreLocator(config) {
        if (typeof require.undef === 'function') {
            require.undef('Ecomteck_StoreLocator/js/plugins/storeLocator/jquery.storelocator');
        }

        require(['Ecomteck_StoreLocator/js/plugins/storeLocator/jquery.storelocator'], function () {
            if (typeof $.fn.storeLocator !== 'function') {
                console.error('Store locator plugin is unavailable');
                return;
            }

            $('#bh-sl-map-container').storeLocator(config);
        });
    }

    return function (config) {
        $(function () {
            loadGoogleMaps(config.apiKey)
                .done(function () {
                    initStoreLocator(config);
                })
                .fail(function (error) {
                    console.error(error.message || error);
                });
        });
    };
});
