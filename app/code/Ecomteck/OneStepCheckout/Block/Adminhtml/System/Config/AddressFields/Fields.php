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

class Fields extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $addressFieldsHelper = $objectManager->create(\Ecomteck\OneStepCheckout\Helper\AddressFields::class);
        $fields = $addressFieldsHelper->getAddressFields();
        if (!$this->getOptions()) {
            $this->addOption(
                '',
                __('Please Select Field')
            );
            foreach ($fields as $field => $fieldName) {
                $this->addOption($field, addslashes($fieldName));
            }
        }
        return parent::_toHtml();
    }
}
