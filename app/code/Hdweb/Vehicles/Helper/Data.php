<?php

namespace Hdweb\Vehicles\Helper;

use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    // const WHEEL_SEARCH_APIKEY = 'a2ee2d35f0c597c8e7debb9e80df708c';
    const WHEEL_SEARCH_APIKEY = 'e9f1c4181623dc389b0fafbde0928c0b';
    protected $_storeManager;
    protected $_scopeConfig;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_storeManager = $storeManager;
        $this->_scopeConfig  = $scopeConfig;
        parent::__construct($context);
    }

    public function getVehiclesSearchbyFormUrl()
    {
        $vehiclesSearchbyFormUrl = $this->_storeManager->getStore()->getBaseUrl() . 'all-tyres/car-tyres.html?';
        return $vehiclesSearchbyFormUrl;
    }
}
