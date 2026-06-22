<?php

namespace Hdweb\Vehicles\Block;

class Models extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context
    ) {
        parent::__construct($context);
    }

    public function _prepareLayout()
    {
        $makeParagraph['meta_title'] = '';
        $makeParagraph['meta_keywords'] = '';
        $makeParagraph['meta_description'] = '';
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $make = $this->getRequest()->getParam('make');
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $storeId = $storeManager->getStore()->getStoreId();
        $paragraphCollection = $objectManager->create('Hdweb\Vehicles\Model\ResourceModel\Vehicles\Collection');
        $paragraphCollection->addFieldToFilter('store_id', ['eq' => $storeId]);
        $paragraphCollection->addFieldToFilter('make', ['eq' => $make]);
        $paragraphCollection->addFieldToFilter('model', ['null' => true]);
        $makeParagraph = $paragraphCollection->getFirstItem();

        $this->pageConfig->getTitle()->set(__($makeParagraph['meta_title']));
        $this->pageConfig->setKeywords(__($makeParagraph['meta_keywords']));
        $this->pageConfig->setDescription(__($makeParagraph['meta_description']));
        
        return parent::_prepareLayout();
    }
}
