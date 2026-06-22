<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hdweb\Tyrefinder\Block;

use Magento\Store\Model\ScopeInterface;

class Size extends \Magento\Framework\View\Element\Template
{
    const CARTYRE_CATEGORY_ID  = 'hdweb/general/car_tyre_category_id';
    const BASE_URL  = 'web/secure/base_url';
    

    protected $storeManager;
    protected $_categoryFactory;
    public $scopeConfig;
    public $productFactory;
    public $productCollectionFactory;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        array $data = []
    ) {
        $this->storeManager              = $context->getStoreManager();
        $this->_categoryFactory          = $categoryFactory;
        $this->productCollectionFactory  = $productCollectionFactory;
        $this->scopeConfig               = $scopeConfig;
        $this->productFactory            = $productFactory;
        parent::__construct($context, $data);
    }
    public function gehomeTytWidthOptions()
    {
    
        $current_category_id = "";
        $attributesValue     = array();
        $car_tyre_category_id                       = $this->scopeConfig->getValue(self::CARTYRE_CATEGORY_ID, ScopeInterface::SCOPE_STORE);
        $category            = $this->_categoryFactory->create()->load($car_tyre_category_id);
        $collection          = $this->productCollectionFactory->create()
            ->addAttributeToSelect('width')
            ->addCategoryFilter($category);

        $collection->setOrder('width', 'ASC');
        $collection->getSelect()->group('width');

        $attr = $this->productFactory->create()->getResource()->getAttribute('width');

        foreach ($collection as $productData) {
            if ($attr->usesSource()) {
                $optionText = $attr->getSource()->getOptionText($productData['width']);
            }

            $selected          = false;
            $item              = array('value' => $productData['width'], 'label' => $optionText, 'selected' => $selected);
            $attributesValue[] = $item;
        }

        return $attributesValue;
    }

    public function getActionUrl($actionName)
    {
        return $this->getUrl('tyrefinder/ajax/' . $actionName);
    }

}
