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
namespace Ecomteck\OneStepCheckout\Block\Adminhtml\System\Config;

class AddressFields extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    protected $_checkboxRenderer;
    /**
     * @var Fields
     */
    protected $_addressFieldRenderer;

    protected $_iconFieldRenderer;

    /**
     * Retrieve group column renderer
     *
     * @return Customergroup
     */
    protected function _getAddressFieldRenderer()
    {
        if (!$this->_addressFieldRenderer) {
            $this->_addressFieldRenderer = $this->getLayout()->createBlock(
                \Ecomteck\OneStepCheckout\Block\Adminhtml\System\Config\AddressFields\Fields::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->_addressFieldRenderer->setClass('fields_select');
        }
        return $this->_addressFieldRenderer;
    }

    /**
     * Retrieve group column renderer
     *
     * @return Customergroup
     */
    protected function _getCheckBoxFieldRenderer()
    {
        if (!$this->_checkboxRenderer) {
            $this->_checkboxRenderer = $this->getLayout()->createBlock(
                \Ecomteck\OneStepCheckout\Block\Adminhtml\System\Config\AddressFields\Checkbox::class,
                '',
                ['data' => ['is_render_to_js_template' => false]]
            );
            $this->_checkboxRenderer->setClass('enable_checkbox');
        }
        return $this->_checkboxRenderer;
    }

    /**
     * Retrieve group column renderer
     *
     * @return Customergroup
     */
    protected function _getIconsFieldRenderer()
    {
        if (!$this->_iconFieldRenderer) {
            $this->_iconFieldRenderer = $this->getLayout()->createBlock(
                \Ecomteck\OneStepCheckout\Block\Adminhtml\System\Config\AddressFields\Icons::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->_iconFieldRenderer->setClass('icons_select');
        }
        return $this->_iconFieldRenderer;
    }

    protected function _prepareToRender()
    {
        $this->addColumn('field', ['label' => __('Field'),  'renderer' => $this->_getAddressFieldRenderer()]);
        $this->addColumn('order', ['label' => __('Order'),  'renderer' => false]);
        $this->addColumn('width', ['label' => __('Width'),  'renderer' => false]);
        $this->addColumn('icon', ['label' => __('Icon'),  'renderer' => false]);
        $this->addColumn('validation', ['label' => __('Validation'),  'renderer' => false]);
        
        //$this->addColumn('icon', ['label' => __('Icon'),  'renderer' => $this->_getIconsFieldRenderer()]);
        //$this->addColumn('enable', ['label' => __('Enable'), 'renderer' => $this->_getCheckBoxFieldRenderer()]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Field');
    }

    /**
     * Prepare existing row data object
     *
     * @param \Magento\Framework\DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = [];
        $optionExtraAttr['option_' . $this->_getAddressFieldRenderer()->calcOptionHash($row->getData('field'))] =
            'selected="selected"';
        $optionExtraAttr['option_' . $this->_getIconsFieldRenderer()->calcOptionHash($row->getData('icon'))] =
        'selected="selected"';

        $row->setData(
            'option_extra_attrs',
            $optionExtraAttr
        );
    }
}
