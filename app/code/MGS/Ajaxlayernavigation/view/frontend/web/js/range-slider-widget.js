/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */


/*jshint browser:true jquery:true*/
/*global alert*/

define(['jquery', 'Magento_Catalog/js/price-utils', 'mage/template', 'jquery/ui', 'Magento_Ui/js/modal/modal'],
    function ($, priceUtil, mageTemplate) {

    "use strict";
    
    return function(widget){
        $.widget('smileEs.rangeSlider', widget, {
            _create: function () {
                this._super();
                var $handles = this.element.find('.ui-slider-handle');
                var $fromLabel = this.element.find('[data-label="from"]');
                var $toLabel = this.element.find('[data-label="to"]');

                if ($handles.length >= 2) {
                    $handles.eq(0).append($fromLabel);
                    $handles.eq(1).append($toLabel);
                }

                this.sliderBar = this.element.find('[data-role="slider-bar"]');
                const sliderOptions = this.sliderBar.slider('option');
                this.min = sliderOptions.min;
                this.max = sliderOptions.max;
                this._bindInputEvents();
            },
            _onSliderChange: function (ev, ui) {
                this.from = ui.values[0];
                this.to   = ui.values[1];
                this._refreshDisplay();
                this._applyRange();
            },
            _getCurrencyLabel: function () {
                return this.element.data('currency') || 'SAR';
            },
            _refreshDisplay: function () {
                var currency = this._getCurrencyLabel();
                this.element.find('[data-role="from-label"]').text(currency + ' ' + this.from);
                this.element.find('[data-role="to-label"]').text(currency + ' ' + this.to);

                this.element.find('.slider-price-form .price-from').val(this.from);
                this.element.find('.slider-price-form .price-to').val(this.to);
            },
            _updateSliderValues: function() {
                this._refreshDisplay();
                this._applyRange();
            },
            _bindInputEvents: function () {
                const self = this;
                const $fromInput = this.element.find('.slider-price-form .price-from');
                const $toInput = this.element.find('.slider-price-form .price-to');
            
                const sanitizeValue = function (val, min, max) {
                    val = parseFloat(val);
                    if (isNaN(val)) return null;
                    return Math.min(Math.max(val, min), max);
                };
            
                $fromInput.on('change blur', function () {
                    const sanitized = sanitizeValue($(this).val(), self.min, self.to);
                    if (sanitized !== null) {
                        self.from = sanitized;
                        self.sliderBar.slider("values", 0, sanitized);
                        $fromInput.val(sanitized);
                        self._updateSliderValues();
                    }
                });
            
                $toInput.on('change blur', function () {
                    const sanitized = sanitizeValue($(this).val(), self.from, self.max);
                    if (sanitized !== null) {
                        self.to = sanitized;
                        self.sliderBar.slider("values", 1, sanitized);
                        $toInput.val(sanitized);
                        self._updateSliderValues();
                    }
                });
            }            
        })
    }

    return $.smileEs.rangeSlider;
});
