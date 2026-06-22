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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class RedirectToCheckoutObserver implements ObserverInterface
{
    const CONFIG_PATH_REDIRECT_TO_CHECKOUT = 'one_step_checkout/general/redirect_to_checkout';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var \Ecomteck\OneStepCheckout\Helper\Config
     */
    protected $_config;



    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlInterface $url,
        \Ecomteck\OneStepCheckout\Helper\Config $config
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->url = $url;
        $this->_config = $config;
    }

    /**
     * Redirects directly to checkout on adding product, if we're configured to do so.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->_config->isEnabled()) {
            return $this;
        }
        if (!$this->scopeConfig->getValue(self::CONFIG_PATH_REDIRECT_TO_CHECKOUT, ScopeInterface::SCOPE_STORE)) {
            return;
        }

        $request = $observer->getData('request');
        $request->setParam('return_url', $this->url->getUrl('checkout'));
    }
}
