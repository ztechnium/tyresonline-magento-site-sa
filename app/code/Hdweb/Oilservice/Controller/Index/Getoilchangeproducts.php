<?php
namespace Hdweb\Oilservice\Controller\Index;

class Getoilchangeproducts extends \Magento\Framework\App\Action\Action
{	
	protected $resultJsonFactory;
	protected $_resultPageFactory;
	protected $productCollectionFactory;
	protected $productFactory;
	protected $_objectManager;
	protected $_oilserviceHelper;
	
    public function __construct(\Magento\Framework\App\Action\Context $context,
    	\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Catalog\Model\ProductFactory $productFactory,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Hdweb\Oilservice\Helper\Data $oilserviceHelper
	) {
    	$this->resultJsonFactory = $resultJsonFactory;
		$this->_resultPageFactory = $resultPageFactory;
		$this->productCollectionFactory = $productCollectionFactory;
		$this->productFactory = $productFactory;
		$this->_objectManager = $objectManager;
		$this->_oilserviceHelper = $oilserviceHelper;
        parent::__construct($context);
    }
    
    public function execute()
    {
    	$postData = $this->getRequest()->getParams();
		$menuId               = $postData['menuid'];
        $modelId              = $postData['modelid'];
        $carId                = $postData['carid'];
        $assemblyGroupNodeIds = $postData['cateid'];

		$collection = $this->productCollectionFactory->create();
		$currencysymbol = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
		$currency = $currencysymbol->getStore()->getCurrentCurrencyCode();
		$options = array();
        if ((isset($menuId) && !empty($menuId)) && (isset($modelId) && !empty($modelId)) && (isset($carId) && !empty($carId)) && (isset($assemblyGroupNodeIds) && !empty($assemblyGroupNodeIds))) {

            $function = 'getArticles';
            $params   = array(
                'articleCountry'         => 'AE',
                'lang'                   => 'en',
                'linkageTargetType'      => 'P',
                'assemblyGroupNodeIds'   => $assemblyGroupNodeIds,
                'linkageTargetId'        => $carId,
                'perPage'                => 1000,
                'includeArticleCriteria' => true,
                'provider'               => $this->_oilserviceHelper::TECDOC_MANDATOR,
            );
            
            $apiResponse   = $this->_oilserviceHelper->getTechdocApiConnection($function, $params);
            $oilGradeArray = array();
            $oilGradeOptionIds = array();
			foreach ($apiResponse->articles as $item) {
			//echo '<pre>';print_r($item->articleCriteria);
			foreach ($item->articleCriteria as $oilData) {
				$criteriaId = $oilData->criteriaId;
				if ($criteriaId != 2467) {
					continue;
				}
				$oilGradeArray[] = $oilData->formattedValue;
				//$oilGradeOptionIds[] = $this->_oilserviceHelper->getAttributeDropdownvalueId($oilData->formattedValue, 'oil_grade');
			}

		}
		if (count($oilGradeArray) > 0) {
			$oilGradeArray = array_unique($oilGradeArray);
			//$oilGradeOptionIds = array_unique($oilGradeOptionIds);
		}
		//echo '<pre>';print_r($oilGradeArray);die;
		$collection->addAttributeToSelect('*');
		$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
		$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
		$collection->addAttributeToFilter('article_number', ['in' => $oilGradeArray]);
		//$collection->addAttributeToFilter('oil_grade', ['in' => $oilGradeOptionIds]);  
        
		if(count($collection->getData()) > 0){
			$defaultoilGrade = '5W-40';
			$defaultBrandId = '1663'; // Stop&go brand ID
			if (in_array('5W-40', $oilGradeArray)){
				$collection->addAttributeToFilter('article_number', ['eq' => $defaultoilGrade]);
				$collection->addAttributeToFilter('mgs_brand', ['eq' => $defaultBrandId]);
			}
			$oilgradeCollection = $this->_objectManager->get('Hdweb\Oilservice\Model\OilgradeFactory')->create()->getCollection()
								  ->addFieldToSelect('oil_litre')
								  ->addFieldToFilter('make_id', ['eq' => $menuId])
								  ->addFieldToFilter('model_id', ['eq' => $modelId])
								  ->addFieldToFilter('engine_id', ['eq' => $carId])
								  ->getFirstItem();
			$oilPerLitre = '';					  
			if(count($oilgradeCollection->getData()) > 0){
				$oilPerLitre = $oilgradeCollection->getOilLitre();
			}
						
			foreach($collection as $product){
				if(!$product->getHasOptions()){
					continue;
				}
				$customOptions = $this->_objectManager->get('Magento\Catalog\Model\Product\Option')->getProductOptionCollection($product);
				
				 foreach ($customOptions as $o) {
					foreach ($o->getValues() as $value) {
						if($value->getTitle() != $oilPerLitre){
							continue;
						}else{
							$sumprice = $value['price'] + $product->getPrice();
							$multiplyPrice = $sumprice * 0.05;
							$customOptionPrice = $sumprice + $multiplyPrice;
							$customOptionPrice = number_format($customOptionPrice, 2);
							//$options['price'] = $currency.' '.$customOptionPrice;
							$options['price_amount'] = $customOptionPrice;
							$options['product_id'] = $product->getId();
							$options['sku'] = $product->getSku();
							$options['oil_litre'] = $oilPerLitre;
							$options['brand_id'] = $product->getMgsBrand();
						}
					}
				 }
			}
			
		} /* else{
			$defaultoilGrade = '5W-40';
			$defaultBrandId = '18305';
			$collection = $this->productCollectionFactory->create();
			$collection->addAttributeToSelect('*');
			$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
			$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
			$collection->addAttributeToFilter('article_number', ['eq' => $defaultoilGrade]);
			$collection->addAttributeToFilter('brand', ['eq' => $defaultBrandId]);
			if(count($collection->getData()) > 0){
			
				$oilgradeCollection = $this->_objectManager->get('Hdweb\Oilservice\Model\OilgradeFactory')->create()->getCollection()
									  ->addFieldToSelect('oil_litre')
									  ->addFieldToFilter('make_id', ['eq' => $menuId])
									  ->addFieldToFilter('model_id', ['eq' => $modelId])
									  ->addFieldToFilter('engine_id', ['eq' => $carId])
									  ->getFirstItem();
				$oilPerLitre = '';					  
				if(count($oilgradeCollection->getData()) > 0){
					$oilPerLitre = $oilgradeCollection->getOilLitre();
				}			
				foreach($collection as $product){
					if(!$product->getHasOptions()){
						continue;
					}
					$customOptions = $this->_objectManager->get('Magento\Catalog\Model\Product\Option')->getProductOptionCollection($product);
					
					 foreach ($customOptions as $o) {
						foreach ($o->getValues() as $value) {
							if($value->getTitle() != $oilPerLitre){
								continue;
							}else{
								$sumprice = $value['price'] + $product->getPrice();
								$multiplyPrice = $sumprice * 0.05;
								$customOptionPrice = $sumprice + $multiplyPrice;
								$customOptionPrice = number_format($customOptionPrice, 2);
								//$options['price'] = $currency.' '.$customOptionPrice;
								$options['price_amount'] = $customOptionPrice;
								$options['product_id'] = $product->getId();
								$options['sku'] = $product->getSku();
								$options['oil_litre'] = $oilPerLitre;
								$options['brand_id'] = $product->getBrand();
							}
						}
					 }
				}
			}
		} */
		}
		$resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($options);
        
    }
}