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
namespace Ecomteck\OneStepCheckout\Block\Adminhtml\System\Config\AddressFields;

class Checkbox extends \Magento\Framework\View\Element\AbstractBlock
{
    protected function _toHtml()
    {
        $elId = $this->getInputId();
        $elName = $this->getInputName();
        $colName = $this->getColumnName();
        $column = $this->getColumn();

        return '<input type="checkbox" value="1" id="' . $elId . '"' .
            ' name="' . $elName . '" <%- ' . $colName . ' %> ' .
            ($column['size'] ? 'size="' . $column['size'] . '"' : '') .
            ' class="' .
            (isset($column['class']) ? $column['class'] : 'input-text') . '"' .
            (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') . '/>';
    }
}
