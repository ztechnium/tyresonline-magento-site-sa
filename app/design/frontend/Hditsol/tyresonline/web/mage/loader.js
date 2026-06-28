/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/template',
    'jquery-ui-modules/widget',
    'mage/translate'
], function ($, mageTemplate) {
    'use strict';

    $.widget('mage.loader', {
        loaderStarted: 0,
        options: {
            icon: '',
            texts: {
                loaderText: $.mage.__('Please wait...'),
                imgAlt: $.mage.__('Loading...'),
                loaderLongText: $.mage.__('If loader does not close automatically, please Click Here')
            },
            template:
                '<div class="loading-mask" data-role="loader">' +
                    '<div class="loader">' +
                     '<div class="inner">' +
                        '<img alt="<%- data.texts.imgAlt %>" src="<%- data.icon %>"><span class="fsloaderclose close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path d="M312.1 375c9.369 9.369 9.369 24.57 0 33.94s-24.57 9.369-33.94 0L160 289.9l-119 119c-9.369 9.369-24.57 9.369-33.94 0s-9.369-24.57 0-33.94L126.1 256L7.027 136.1c-9.369-9.369-9.369-24.57 0-33.94s24.57-9.369 33.94 0L160 222.1l119-119c9.369-9.369 24.57-9.369 33.94 0s9.369 24.57 0 33.94L193.9 256L312.1 375z"></path></svg></span><span class="fsloaderclose text"><%- data.texts.loaderLongText %></span>' +
                        '<p><%- data.texts.loaderText %></p>' +
                    '</div>' +
                  '</div>' +
                '</div>'

        },

        /**
         * Loader creation
         * @protected
         */
        _create: function () {
            this._bind();

            if ($('body').hasClass('checkout-index-index') || $('body').hasClass('checkout-cart-index')) {
                var self = this;

                setInterval(function () {
                    if ($.active === 0 && self.loaderStarted > 0) {
                        self.loaderStarted = 0;

                        if (self.spinner) {
                            self.spinner.hide();
                        }
                    }
                }, 1000);
            }
        },

        /**
         * Bind on ajax events
         * @protected
         */
        _bind: function () {
            this._on({
                'processStop': 'hide',
                'processStart': 'show',
                'show.loader': 'show',
                'hide.loader': 'hide',
                'contentUpdated.loader': '_contentUpdated'
            });
        },

        /**
         * Verify loader present after content updated
         *
         * This will be cleaned up by the task MAGETWO-11070
         *
         * @param {EventObject} e
         * @private
         */
        _contentUpdated: function () {
            return false;
        },

        /**
         * Show loader
         */
        show: function (e, ctx) {
            this._render();
            this.loaderStarted++;
            this.spinner.show();

            if (ctx) {
                this.spinner
                    .css({
                        width: ctx.outerWidth(),
                        height: ctx.outerHeight(),
                        position: 'absolute'
                    })
                    .position({
                        my: 'top left',
                        at: 'top left',
                        of: ctx
                    });
            }

            return false;
        },

        /**
         * Hide loader
         */
        hide: function () {
            if (this.loaderStarted > 0) {
                this.loaderStarted--;
            }

            if (this.loaderStarted <= 0) {
                this.loaderStarted = 0;

                if (this.spinner) {
                    this.spinner.hide();
                }
            }

            return false;
        },

        /**
         * Render loader
         * @protected
         */
        _render: function () {
            var html;

            if (!this.spinnerTemplate) {
                this.spinnerTemplate = mageTemplate(this.options.template);

                html = $(this.spinnerTemplate({
                    data: this.options
                }));

                html.prependTo(this.element);

                this.spinner = html;
            }
        },

        /**
         * Destroy loader
         */
        _destroy: function () {
            this.spinner.remove();
        }
    });

    /**
     * This widget takes care of registering the needed loader listeners on the body
     */
    $.widget('mage.loaderAjax', {
        options: {
            defaultContainer: '[data-container=body]',
            loadingClass: 'ajax-loading'
        },

        /**
         * @private
         */
        _create: function () {
            this._bind();
            // There should only be one instance of this widget, and it should be attached
            // to the body only. Having it on the page twice will trigger multiple processStarts.
            if (window.console && !this.element.is(this.options.defaultContainer) && $.mage.isDevMode(undefined)) {
                console.warn('This widget is intended to be attached to the body, not below.');
            }
        },

        /**
         * @private
         */
        _bind: function () {
            $(document).on({
                'ajaxSend': this._onAjaxSend.bind(this),
                'ajaxComplete': this._onAjaxComplete.bind(this)
            });
        },

        /**
         * @param {Object} loaderContext
         * @return {*}
         * @private
         */
        _getJqueryObj: function (loaderContext) {
            var ctx;

            // Check to see if context is jQuery object or not.
            if (loaderContext) {
                if (loaderContext.jquery) {
                    ctx = loaderContext;
                } else {
                    ctx = $(loaderContext);
                }
            } else {
                ctx = $('[data-container="body"]');
            }

            return ctx;
        },

        /**
         * @param {jQuery.Event} e
         * @param {Object} jqxhr
         * @param {Object} settings
         * @private
         */
        _onAjaxSend: function (e, jqxhr, settings) {
            var ctx;

            $(this.options.defaultContainer)
                .addClass(this.options.loadingClass)
                .attr({
                    'aria-busy': true
                });

            if (settings && settings.showLoader) {
                ctx = this._getJqueryObj(settings.loaderContext);
                ctx.trigger('processStart');

                // Check to make sure the loader is there on the page if not report it on the console.
                // NOTE that this check should be removed before going live. It is just an aid to help
                // in finding the uses of the loader that maybe broken.
                if (window.console && !ctx.parents('[data-role="loader"]').length) {
                    console.warn('Expected to start loader but did not find one in the dom');
                }
            }
        },

        /**
         * @param {jQuery.Event} e
         * @param {Object} jqxhr
         * @param {Object} settings
         * @private
         */
        _onAjaxComplete: function (e, jqxhr, settings) {
            $(this.options.defaultContainer)
                .removeClass(this.options.loadingClass)
                .attr('aria-busy', false);

            if (settings && settings.showLoader) {
                this._getJqueryObj(settings.loaderContext).trigger('processStop');
            }
        }

    });

    return {
        loader: $.mage.loader,
        loaderAjax: $.mage.loaderAjax
    };
});
