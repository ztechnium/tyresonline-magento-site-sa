<?php

namespace Hdweb\Vehicles\Block;

class Makes extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context
    ) {
        parent::__construct($context);
    }

    public function _prepareLayout()
    {
        $storeCode = $this->_storeManager->getStore()->getCode();
        if($storeCode == 'en'){
            $this->pageConfig->getTitle()->set(__('Buy Car, SUV Tires Online at Best Price in UAE – Tyresonline.ae'));
            $this->pageConfig->setKeywords(__('car tyres, vehicle tires, premium tyres, high-performance tyres, luxury car tyres, tyre selection'));
            $this->pageConfig->setDescription(__('Shop Car Tyres online at best prices and enjoy free delivery and installation from our extensive network of local installers in Dubai, Sharjah & anywhere in UAE.')); 
        }else{
            $this->pageConfig->getTitle()->set(__('Arabic title here'));
            $this->pageConfig->setKeywords(__('arabic keywords here'));
            $this->pageConfig->setDescription(__('Arabic Description here'));
        }       
        return parent::_prepareLayout();
    }
}
