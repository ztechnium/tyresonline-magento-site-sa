/**
 * Copyright © 2017 Ecomteck. All rights reserved.
 * See LICENSE.txt for license details.
 */
 define([
    'mage/utils/wrapper',
    'jquery'
 ], function (wrapper, $) {
    'use strict';

    let mixin = {
        /**
         * Sets window location hash.
         *
         * @param {String} hash
         */
         setHash: function (hash) {
            if (typeof(hash) !== "undefined" && typeof(hash) !== "object" && typeof(hash) !== "function") {
                window.location.hash = String(hash).replace('#', '');
            }
        },
        navigateTo: function (code, scrollToElementId) {
            var sortedItems = steps().sort(this.sortItems),
                bodyElem = $('body');

            scrollToElementId = scrollToElementId || null;

            if (!this.isProcessed(code)) {
                return;
            }
            sortedItems.forEach(function (element) {
                if (element.code == code) { //eslint-disable-line eqeqeq
                    element.isVisible(true);
                    bodyElem.animate({
                        scrollTop: $('#' + code).offset().top
                    }, 0, function () {
                        //window.location = window.checkoutConfig.checkoutUrl + '#' + code;
                    });

                    if (scrollToElementId && $('#' + scrollToElementId).length) {
                        bodyElem.animate({
                            scrollTop: $('#' + scrollToElementId).offset().top
                        }, 0);
                    }
                } else {
                    element.isVisible(false);
                }

            });
        }
    };

    return function (target) {
        return wrapper.extend(target, mixin);
    };
});