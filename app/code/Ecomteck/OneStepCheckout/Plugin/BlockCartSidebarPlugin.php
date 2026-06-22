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
namespace Ecomteck\OneStepCheckout\Plugin;

class BlockCartSidebarPlugin
{
    /**
     * Url builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * One step checkout helper
     *
     * @var \Ecomteck\OneStepCheckout\Helper\Config
     */
    protected $_config;
    

    /**
     * Initialize dependencies.
     *
     * @param \Ecomteck\OneStepCheckout\Helper\Config $config
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        \Ecomteck\OneStepCheckout\Helper\Config $config,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->_config = $config;
        $this->url = $url;
    }

    /**
     * Modify checkout url
     *
     * @param \Magento\Checkout\Block\Cart\Sidebar $subject
     * @param string $checkoutUrl
     * @return string
     */
    public function afterGetCheckoutUrl(
        \Magento\Checkout\Block\Cart\Sidebar $subject,
        $checkoutUrl
    ) {
        if (!$this->_config->isEnabled()) {
            return $checkoutUrl;
        }
        if ($this->_config->getRouterName() != 'checkout') {
            return $this->url->getDirectUrl($this->_config->getCheckoutUrl());
        }
        
        return $checkoutUrl;
    }
}
