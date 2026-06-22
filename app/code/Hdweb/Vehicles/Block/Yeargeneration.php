<?php

namespace Hdweb\Vehicles\Block;

class Yeargeneration extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context
    ) {
        parent::__construct($context);
    }

    public function _prepareLayout()
    {
        $modelParagraph['meta_title'] = '';
        $modelParagraph['meta_keywords'] = '';
        $modelParagraph['meta_description'] = '';
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $params = $this->getRequest()->getParams();
        $make = $params['make'];
        $model = $params['model'];
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $storeId = $storeManager->getStore()->getStoreId();
        $paragraphCollection = $objectManager->create('Hdweb\Vehicles\Model\ResourceModel\Vehicles\Collection');
        $paragraphCollection->addFieldToFilter('store_id', ['eq' => $storeId]);
        $paragraphCollection->addFieldToFilter('make', ['eq' => $make]);
        $paragraphCollection->addFieldToFilter('model', ['eq' => $model]);
        $modelParagraph = $paragraphCollection->getFirstItem();

        $this->pageConfig->getTitle()->set(__($modelParagraph['meta_title']));
        $this->pageConfig->setKeywords(__($modelParagraph['meta_keywords']));
        $this->pageConfig->setDescription(__($modelParagraph['meta_description']));
        
        return parent::_prepareLayout();
    }
}
