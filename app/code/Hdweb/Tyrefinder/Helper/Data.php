<?php
namespace Hdweb\Tyrefinder\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    // const WHEEL_SEARCH_APIKEY = 'a2ee2d35f0c597c8e7debb9e80df708c'; //Wheel Size API KEY
		const WHEEL_SEARCH_APIKEY = 'e9f1c4181623dc389b0fafbde0928c0b';
    protected $_storeManager;
    protected $_scopeConfig;
    protected $_objectManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_storeManager = $storeManager;
        $this->_scopeConfig  = $scopeConfig;
		$this->_objectManager = $objectManager;
        parent::__construct($context);
    }

    public function getBaseUrl()
    {
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        return $baseUrl;
    }

    public function getStorepickupUrl()
    {
        $baseUrl = $this->getBaseUrl() . 'storepickup?ref=cart';
        return $baseUrl;
    }

    public function redirectCartToStorepickup()
    {
        $cartToStorepickup = $this->scopeConfig->getValue('carttostorepickup/general/enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $cartToStorepickup;
    }
	
	public function getSearchValue()
    {
        $params = $this->_request->getParams();
        $searchTyre = "";

        if(isset($params['width']) && $params['width'] != ''){
		  if(isset($params['width']) && $params['width'] != '' && isset($params['height']) && $params['height'] != '' && isset($params['rim']) && $params['rim'] != ''){
			$searchTyre = $params['width'].'/'.$params['height'].' R'.$params['rim'];
		  }
		  if(isset($params['width_rear']) && $params['width_rear'] != '' && isset($params['height_rear']) && $params['height_rear'] != '' && isset($params['rim_rear']) && $params['rim_rear'] != ''){
			$searchTyre .= ' - '.$params['width_rear'].'/'.$params['height_rear'].' R'.$params['rim_rear'];
		  }
		}

        return $searchTyre;
    }
	
	public function getRearcollection()
    {
		$productCollectionFactory = $this->_objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
		$productStatus = $this->_objectManager->get('Magento\Catalog\Model\Product\Attribute\Source\Status');
		$productVisibility = $this->_objectManager->get('Magento\Catalog\Model\Product\Visibility');
		$request = $this->_objectManager->get('Magento\Framework\App\Request\Http');
        $width_rear                  = $request->getParam('width_rear');
        $height_rear                 = $request->getParam('height_rear');
        $rim_rear                    = $request->getParam('rim_rear');
        $rear_tyre_search_collection = $productCollectionFactory->create()
									->addAttributeToSelect('*')
									->addAttributeToFilter('status', ['in' => $productStatus->getVisibleStatusIds()])
									->setVisibility($productVisibility->getVisibleInSiteIds())
									->addFieldToFilter('width_value', $width_rear)
									->addFieldToFilter('height_value', $height_rear)
									->addFieldToFilter('rim_value', $rim_rear);

        return $rear_tyre_search_collection;
    }
	
	public function isBundle()
    {
		$request = $this->_objectManager->get('Magento\Framework\App\Request\Http');
		$width_rear                  = $request->getParam('width_rear');
        $height_rear                 = $request->getParam('height_rear');
        $rim_rear                    = $request->getParam('rim_rear');
        $isBundle    = 0;
        if (isset($width_rear) && isset($height_rear) && isset($rim_rear) && !empty($width_rear) && !empty($height_rear) && !empty($rim_rear)) {
            $isBundle = 1;
        }

        return $isBundle;
    }
	
	public function getFilterProductSkus()
    {
		$frontcollection = array();
		$collection = array();
		$sku = array();
		$frontSku = array();		
		$rearSku = array();
		$bundleSku = array();
		
		if ((count($this->getRearcollection()) > 0) && ($this->isBundle())) {
			$productCollectionFactory = $this->_objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
			$productStatus = $this->_objectManager->get('Magento\Catalog\Model\Product\Attribute\Source\Status');
			$productVisibility = $this->_objectManager->get('Magento\Catalog\Model\Product\Visibility');
			$request = $this->_objectManager->get('Magento\Framework\App\Request\Http');
			$width_rear                  = $request->getParam('width_rear');
			$height_rear                 = $request->getParam('height_rear');
			$rim_rear                    = $request->getParam('rim_rear');
			
			$width	= $request->getParam('width');
			$height	= $request->getParam('height');
			$rim	= $request->getParam('rim');

			$frontcollection = $productCollectionFactory->create()
					->addAttributeToSelect('*')
					->addAttributeToFilter('status', ['in' => $productStatus->getVisibleStatusIds()])
					->setVisibility($productVisibility->getVisibleInSiteIds())
					->addFieldToFilter('width_value', $width)
					->addFieldToFilter('height_value', $height)
					->addFieldToFilter('rim_value', $rim);
					
						
			foreach ($frontcollection as $fronProduct) {
				$FrontbrandId   = $fronProduct->getMgsBrand();
				$FrontpatternId = $fronProduct->getPattern();
				$FrontyearId    = $fronProduct->getYear();
				$FrontrunflatId = $fronProduct->getRunflat();

				$rearcollection = $this->getRearcollection();
				foreach ($rearcollection as $rearProduct) {
					if (($FrontbrandId != $rearProduct->getMgsBrand()) || ($fronProduct->getId() == $rearProduct->getId()) || ($FrontpatternId != $rearProduct->getPattern()) || ($FrontyearId != $rearProduct->getYear()) || ($FrontrunflatId != $rearProduct->getRunflat()) || ($fronProduct->getIsSalable() != $rearProduct->getIsSalable())) {
						continue;
					}
					$frontSku[] = $fronProduct->getSku();
					$rearSku[] = $rearProduct->getSku();
				}
			}
		}
		if(count($frontSku) > 0 && count($rearSku) > 0){
			$bundleSku = array_merge($frontSku,$rearSku);
		}
		return $bundleSku;
		
	}
	public function getRelatedProductCollection($_product)
    {
		$relatedProductCollection = array();
		if($_product->getId()){
			$productCollectionFactory = $this->_objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
			$productStatus = $this->_objectManager->get('Magento\Catalog\Model\Product\Attribute\Source\Status');
			$productVisibility = $this->_objectManager->get('Magento\Catalog\Model\Product\Visibility');
			$width = $_product->getWidth();
			$height = $_product->getHeight();
			$rim = $_product->getRim();
			$relatedProductCollection = $productCollectionFactory->create()
					->addAttributeToSelect('*')
					->addAttributeToFilter('status', ['in' => $productStatus->getVisibleStatusIds()])
					->addFieldToFilter('entity_id', ['neq' => $_product->getId()])
					->setVisibility($productVisibility->getVisibleInSiteIds())
					->addFieldToFilter('width', $width)
					->addFieldToFilter('height', $height)
					->addFieldToFilter('rim', $rim)
				//	->setOrder('price', 'asc')
					->setOrder('recommended_product', 'asc')
					->setPageSize(8);
		}
		return $relatedProductCollection;
	}
}
