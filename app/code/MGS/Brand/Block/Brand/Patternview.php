<?php

namespace MGS\Brand\Block\Brand;

//use MGS\Brand\Block\Brand;


/**
 * Class View
 * @package Mageplaza\Shopbybrand\Block
 */
class Patternview extends \Magento\Framework\View\Element\Template
{
	protected $objectManager;
	protected $helper;
	protected $_coreRegistry;

	public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \MGS\Brand\Helper\Data $helper,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->objectManager = $objectmanager;
        $this->helper = $helper;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context,$data);
    }
    /**
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	protected function _prepareLayout()
	{
		parent::_prepareLayout();

		$pattern = $this->getPattern();
		$title = $pattern->getMetaTitle() ?: $pattern->getValue();
		if ($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs')) {
			$breadcrumbsBlock->addCrumb('view', ['label' => $title]);
		}

		$description = $pattern->getMetaDescription();
		if ($description) {
			$this->pageConfig->setDescription($description);
		}
		$keywords = $pattern->getMetaKeywords();
		if ($keywords) {
			$this->pageConfig->setKeywords($keywords);
		}

		$pageMainTitle = $this->getLayout()->getBlock('page.main.title');
		if ($pageMainTitle) {
			$pageMainTitle->setPageTitle($title);
		}

		return $this;
	}
	 
	public function getPattern()
	{
		return $this->_coreRegistry->registry('current_pattern');
	}
	
	public function getBrandImage()
	{
		//$brand = $this->getBrand();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$pattern       = $this->getPattern();
		$brandId 	   = $pattern->getBrandId();
		$brandName 	   = $pattern->getBrand();
		$store = null;
		//$brand = $objectManager->get('MGS\Brand\Model\Brand')->loadByOption($brandId);
		//$brand = $objectManager->get('MGS\Brand\Model\Brand')->load($brandId);
		$brandObj = $objectManager->get('MGS\Brand\Model\Brand');
		$brand = $brandObj->getCollection()
                                ->addFieldToFilter('name', $brandName)
                                ->addFieldToFilter('status', 1)
                                ->getFirstItem();
		$brandImgUrl = $brand->getImageUrl();
		return $brandImgUrl;
		//return $this->helper()->getBrandImageUrl($brand);
	}
	
	public function getBrandLink()
	{

		//$brand = $this->getBrand();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$pattern       = $this->getPattern();
		//$brandId 	   = $pattern->getBrandId();
		$brandName 	   = $pattern->getBrand();
		$store = null;
		$brandObj = $objectManager->get('MGS\Brand\Model\Brand');
		$brand = $brandObj->getCollection()
                                //->addFieldToFilter('brand_id', $brandId)
                                ->addFieldToFilter('name', $brandName)
                                ->addFieldToFilter('status', 1)
                                ->getFirstItem();
		//$brand = $objectManager->get('MGS\Brand\Model\Brand')->loadByOption($brandId);
		//$brand = $objectManager->get('MGS\Brand\Model\Brand')->load($brandId);
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$brandUrl = $storeManager->getStore()->getBaseUrl().'all-tyre-brands/'.$brand->getUrlKey();
		return $brandUrl;
	}
	
	/**
	 * @return string
	 */
	public function getPatternShortDescription()
	{
		//return $this->helper()->getPatternShortDescription($this->getPattern());
		$pattern = $this->getPattern();
		return $pattern->getShortDescription() ?: '';
	}
	
	/**
	 * @return string
	 */
	public function getPatternDescription()
	{
		//return $this->helper()->getPatternDescription($this->getPattern());
		$pattern = $this->getPattern();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$description = $pattern->getDescription() ?: '';
		$filterContent = $objectManager->get('Magento\Cms\Model\Template\FilterProvider')->getPageFilter()->filter($description);
		return $filterContent;

	}
	
	/**
	 * @return string
	 */
	public function getPatternPerformanceDescription()
	{
		//return $this->helper()->getPatternPerformanceDescription($this->getPattern());
		$pattern = $this->getPattern();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$performance_description = $pattern->getPerformanceDescription() ?: '';
		$filterContent = $objectManager->get('Magento\Cms\Model\Template\FilterProvider')->getPageFilter()->filter($performance_description);
		return $filterContent;
	}
	
	public function getPatternProducts()
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$pattern      = $this->getPattern();
		$brandId = $pattern->getBrandId();
		$patternId = $pattern->getPatternId();
		$productFactory = $objectManager->get('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
		$collection = $productFactory->create();
		$collection->addAttributeToSelect('entity_id');
		$collection->addAttributeToSelect('rim');
		$collection->addAttributeToSelect('rim_value');
		$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
		$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
		$collection->addAttributeToFilter('mgs_brand', $brandId);
		$collection->addAttributeToFilter('pattern', $patternId);
		//$collection->getSelect()->group('diameter');
		
		return $collection;
	}
	
	public function group_by($key, $data) {
		$result = array();

		foreach($data as $val) {
			if(array_key_exists($key, $val)){
				$result[$val[$key]][] = $val;
			}else{
				$result[""][] = $val;
			}
		}

		return $result;
	}
	

}
