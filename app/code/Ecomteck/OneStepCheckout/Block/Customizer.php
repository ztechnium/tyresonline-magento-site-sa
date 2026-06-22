<?php
/**
 * Ecomteck
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Ecomteck.com license that is
 * available through the world-wide-web at this URL:
 * https://ecomteck.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Ecomteck
 * @package     Ecomteck_OneStepCheckout
 * @copyright   Copyright (c) 2018 Ecomteck (https://ecomteck.com/)
 * @license     https://ecomteck.com/LICENSE.txt
 */
namespace Ecomteck\OneStepCheckout\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Ecomteck\OneStepCheckout\Helper\AddressFields as AddressFieldsHelper;

class Customizer extends Template
{
    const CONFIG_PATH_COLORS = 'one_step_checkout/colors/%s';
    const CONFIG_PATH_ICONS = 'one_step_checkout/icons/%s';
    const CONFIG_PATH_LAYOUT = 'one_step_checkout/display/layout';
    const CONFIG_PATH_CUSTOMIZE_CSS = 'one_step_checkout/customize/css';

    protected $addressFieldsHelper;

    /**
     * @param Context $context
     * @param Redirect $redirect
     * @param array $data
     */
    public function __construct(Context $context, AddressFieldsHelper $addressFieldsHelper, array $data = [])
    {
        parent::__construct($context, $data);
        $this->addressFieldsHelper = $addressFieldsHelper;
    }

    /**
     * @return string
     */
    public function getMainColor()
    {
        return $this->_scopeConfig->getValue(
            sprintf(self::CONFIG_PATH_COLORS, 'main'),
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getIconColor()
    {
        $color =  $this->_scopeConfig->getValue(
            sprintf(self::CONFIG_PATH_COLORS, 'icon'),
            ScopeInterface::SCOPE_STORE
        );

        $steps = [
            'account_information',
            'shipping_address',
            'billing_address',
            'shipping_methods',
            'payment_methods',
            'order_summary'
        ];
        $styles = [];
        if ($color) {
            foreach ($steps as $step) {
                $styles[] = '
                            .'.$step.'_title:before {
                                color: '.$color.';
                            }
                        ';
            }
            $styles[] = '
                .fieldset.address .field .control::before{
                    color: '.$color.'
                }
            ';
        }
        
        
        return implode("\n", $styles);
    }

    /**
     * @return string
     */
    public function getButtonColor()
    {
        $color =  $this->_scopeConfig->getValue(
            sprintf(self::CONFIG_PATH_COLORS, 'button'),
            ScopeInterface::SCOPE_STORE
        );
        $styles = [];
        if ($color) {
            $styles[] = '
                #discount-form .action, 
                .place-order .button,
                input[type="checkbox"]:checked, 
                input[type="radio"]:checked,
                #giftcard-form .actions-toolbar .primary .action,
                .customerbalance .actions-toolbar .primary .action
                {
                    background-color: '.$color.';
                    border-color: '.$color.';
                    color:#fff;
                }

            ';
        }
        
        
        return implode("\n", $styles);
    }

    public function getAddressFieldsWidth()
    {
        $fields = $this->addressFieldsHelper->getAddressFields();
        $styles = [];
        foreach ($fields as $field => $name) {
            $fieldConfig = $this->addressFieldsHelper->getConfigValue($field);
            if (!empty($fieldConfig['width'])) {
                
                if ($field == 'street') {
                    $styles[] = '.fieldset.address .street{
                        flex: '.$fieldConfig['width'].'!important;
                    }';
                } else {
                    $styles[] = '[name$=".'.$field.'"] {
                        flex: '.$fieldConfig['width'].'!important;
                    }';
                }
            }
            
        }
        return implode("\n", $styles);
    }

    public function getAddressFieldsIcon()
    {
        $fields = $this->addressFieldsHelper->getAddressFields();
        $styles = [];
        foreach ($fields as $field => $name) {
            $fieldConfig = $this->addressFieldsHelper->getConfigValue($field);
            if (!empty($fieldConfig['icon'])) {

                if ($field == 'street') {
                    $styles[] = '.fieldset.address .street > .control .control {
                        display:flex;
                        flex-wrap: wrap;
                    }';
                    $styles[] = '.fieldset.address .street > .control .control input{
                        flex:70%;
                    }';
                    $styles[] = '.fieldset.address .street > .control .control::before {
                        flex:10%;
                        font-weight: 900;
                        font-family: "Font Awesome 5 Free";
                        content: "\\'.$fieldConfig['icon'].'";
                        height: 31px;
                        line-height: 31px;
                        font-size:31px;
                        width:32px;
                        text-align:left;
                    }';
                } else {
                    $styles[] = '[name$=".'.$field.'"] > .control {
                        display:flex;
                        flex-wrap: wrap;
                    }';
                    $styles[] = '[name$=".'.$field.'"] > .control input, [name$=".'.$field.'"] > .control select {
                        flex:70%;
                    }';
                    $styles[] = '[name$=".'.$field.'"] > .control::before {
                        flex:10%;
                        font-weight: 900;
                        font-family: "Font Awesome 5 Free";
                        content: "\\'.$fieldConfig['icon'].'";
                        height: 31px;
                        line-height: 31px;
                        font-size:31px;
                        width:32px;
                        text-align:left;
                    }';
                }
            }
        }
        if (!empty($styles)) {
            return implode("\n", $styles);
        }
        return '';
    }

    public function getStepIcons()
    {
        $steps = [
            'account_information',
            'shipping_address',
            'billing_address',
            'shipping_methods',
            'payment_methods',
            'order_summary'
        ];
        $styles = [];
        $color = $this->getMainColor() ? $this->getMainColor() : '';
        foreach ($steps as $step) {
            $icon = $this->_scopeConfig->getValue(
                sprintf(self::CONFIG_PATH_ICONS, $step),
                ScopeInterface::SCOPE_STORE
            );
            if ($icon) {
                if (is_numeric($icon)) {
                    $styles[] = '
                        .'.$step.'_title {
                            width:32px;
                            height:32px;
                            border-radius:50%;
                            background-color:'.$color.';
                            color:#ffffff;
                            text-align:center;
                        }
                        .'.$step.'_title:before {
                            content: "'.$icon.'";
                        }
                    ';
                } else {
                    $styles[] = '
                        .'.$step.'_title:before {
                            content: "\\'.$icon.'";
                        }
                    ';
                }
                
            }
        }
        if (!empty($styles)) {
            return implode("\n", $styles);
        }
        return '';
    }

    public function getGiftMessageColor()
    {
        $color =  $this->_scopeConfig->getValue(
            sprintf(self::CONFIG_PATH_COLORS, 'gift_message'),
            ScopeInterface::SCOPE_STORE
        );
        $styles = [];
        if ($color) {
            $styles[] = '
            .gift-message-icon {
                    color: '.$color.';
                }

            ';
        }
        
        
        return implode("\n", $styles);
    }

    public function getOrderSummaryBackgroundColor()
    {
        $color =  $this->_scopeConfig->getValue(
            sprintf(self::CONFIG_PATH_COLORS, 'order_summary_background'),
            ScopeInterface::SCOPE_STORE
        );
        $styles = [];
        if ($color) {
            $styles[] = '
                #opc-sidebar {
                    background-color: '.$color.';
                }
            ';
        }
        
        
        return implode("\n", $styles);
    }

    public function getPaymentMethodsBackgroundColor()
    {
        $color =  $this->_scopeConfig->getValue(
            sprintf(self::CONFIG_PATH_COLORS, 'payment_methods_background'),
            ScopeInterface::SCOPE_STORE
        );
        $styles = [];
        if ($color) {
            $styles[] = '
                #payment {
                    background-color: '.$color.';
                }
            ';
        }
        
        
        return implode("\n", $styles);
    }

    public function getShippingMethodsBackgroundColor()
    {
        $color =  $this->_scopeConfig->getValue(
            sprintf(self::CONFIG_PATH_COLORS, 'shipping_methods_background'),
            ScopeInterface::SCOPE_STORE
        );
        $styles = [];
        if ($color) {
            $styles[] = '
                #opc-shipping_method {
                    background-color: '.$color.';
                }
            ';
        }
        
        
        return implode("\n", $styles);
    }

    public function getAccountAddressBackgroundColor()
    {
        $color =  $this->_scopeConfig->getValue(
            sprintf(self::CONFIG_PATH_COLORS, 'account_address_background'),
            ScopeInterface::SCOPE_STORE
        );
        $styles = [];
        if ($color) {
            $styles[] = '
                #shipping {
                    background-color: '.$color.';
                }
            ';
        }
        
        
        return implode("\n", $styles);
    }

    public function getPageLayout()
    {
        $layout =  $this->_scopeConfig->getValue(
            self::CONFIG_PATH_LAYOUT,
            ScopeInterface::SCOPE_STORE
        );
        
        $styles = [];
        if ($layout == '2columns-style1') {
            $styles[] = '
            #welcome, #payment, #shipping, #opc-shipping_method,li.step {
                    width:100%!important;
                }
                .opc-wrapper .form-login, .opc-wrapper .form-shipping-address {
                    max-width:100%;
                }
            ';
        }

        if ($layout == '2columns-style2') {
            $styles[] = '
                #welcome, #shipping {
                    width:100%!important;
                }
                #payment,#opc-shipping_method {
                    width:50%!important;
                }
                .opc-wrapper .form-login, .opc-wrapper .form-shipping-address {
                    max-width:100%;
                }
            ';
        }
        
        
        return implode("\n", $styles);
    }

    public function getCustomizeCss()
    {
        $css =  $this->_scopeConfig->getValue(
            self::CONFIG_PATH_CUSTOMIZE_CSS,
            ScopeInterface::SCOPE_STORE
        );
        return $css;
    }
}
