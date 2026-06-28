define(['jquery', 'lazysizes'], function ($) {
    'use strict';

    document.addEventListener('lazybeforeunveil', function (e) {
        var bg = e.target.getAttribute('data-bg');
        if (bg) {
            e.target.style.backgroundImage = 'url(' + bg + ')';
        }
    });

    if (window.lazySizes) {
        window.lazySizes.init();
    }

    return function () {};
});
