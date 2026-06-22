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
namespace Ecomteck\OneStepCheckout\Observer;

class AddOneStepCheckoutLayoutHandleObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Ecomteck\OneStepCheckout\Helper\Config
     */
    protected $_config;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Ecomteck\OneStepCheckout\Helper\Config $config
    ) {
        $this->_registry = $registry;
        $this->_config = $config;
    }

    /**
     * add a custom handle to categories of page type 'PAGE'
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_config->isEnabled()) {
            return $this;
        }
        $action = $observer->getData('full_action_name');
        if ($action == 'checkout_index_index' || $action == 'onestepcheckout_index_index') {
            $layout = $observer->getData('layout');
            $layout->getUpdate()->addHandle('onestepcheckout');
            return $this;
        }

        return $this;
    }
}
