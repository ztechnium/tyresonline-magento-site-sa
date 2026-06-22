<?php
namespace Hdweb\Oilservice\Controller\Index;

class Getautofilterproducts extends \Magento\Framework\App\Action\Action
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
		// $menuId               = $postData['menuid'];
       // $modelId              = $postData['modelid'];
	   
        $carId                = $postData['car_id'];
        $assemblyGroupNodeIds = $postData['cateid'];
		$collection = $this->productCollectionFactory->create();
		$currencysymbol = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
		$currency = $currencysymbol->getStore()->getCurrentCurrencyCode();
		$_attributehelper = $this->_objectManager->create('Hdweb\Tyrefinder\Helper\Productlisting');
		
		
        $function = 'getArticles';
		$params   = array(
			'articleCountry'       => 'AE',
			'lang'                 => 'en',
			'linkageTargetType'    => 'P',
			'assemblyGroupNodeIds' => $assemblyGroupNodeIds,
			'linkageTargetId'      => $carId,
			'perPage'              => 1000,
			'provider'             => $this->_oilserviceHelper::TECDOC_MANDATOR,
		);
		
		$apiResponse   = $this->_oilserviceHelper->getTechdocApiConnection($function, $params);
		$articleArray  = array();
		foreach ($apiResponse->articles as $item) {
			$articleArray[] = $item->articleNumber;
		}
		
		$oemnumber = $this->_oilserviceHelper->getOEMNumbers($articleArray);
		$articleNumbers = array_unique (array_merge ($articleArray, $oemnumber));
		
		$collection = $this->productCollectionFactory->create();
		$collection->addAttributeToSelect('*');
		$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
		$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
		//$collection->addAttributeToFilter('article_number', ['in' => $articleArray]);
		$collection->addAttributeToFilter('article_number', ['in' => $articleNumbers]);
		$collection->setOrder('price', 'DESC');
		$collection->setPageSize(1);
		
		$options = array();
		if(count($collection->getData()) > 0){
			foreach($collection as $product){
				$finalPricewithtax = $_attributehelper->getSet1price($product);
				$options['price_amount'] = $finalPricewithtax;
				$options['product_id'] = $product->getId();
				$options['sku'] = $product->getSku();
				$options['brand_id'] = $product->getMgsBrand();
			}
		}
		$resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($options);
        
    }
}