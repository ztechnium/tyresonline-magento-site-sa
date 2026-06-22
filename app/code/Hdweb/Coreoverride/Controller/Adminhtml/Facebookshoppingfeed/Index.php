<?php

namespace Hdweb\Coreoverride\Controller\Adminhtml\Facebookshoppingfeed;

use Magento\Framework\App\Filesystem\DirectoryList;

class Index extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
	protected $_storeManager;
    protected $_configWriter;
    protected $_scopeConfig;
    protected $_cacheTypeList;
    protected $_cacheFrontendPool;
	protected $productCollectionFactory;
	protected $_filesystem;
	protected $productRepository;
	
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
		\Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Framework\Filesystem $_filesystem,
		\Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
		$this->_configWriter = $configWriter;
		$this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
		$this->_cacheTypeList = $cacheTypeList;
		$this->_cacheFrontendPool = $cacheFrontendPool;
		$this->productCollectionFactory  = $productCollectionFactory;
        $this->_filesystem               = $_filesystem;
        $this->productRepository = $productRepository;
    }

    public function execute()
    {
		$websiteId = 0;
		$resultRedirect = $this->resultRedirectFactory->create();
		$currencysymbol = $this->_storeManager;
		$currency = $currencysymbol->getStore()->getCurrentCurrencyCode();
		$categoryIds = $this->_scopeConfig->getValue('productsearch/general/google_shopping_feed_cat_ids');
		if($categoryIds != ''){
			$googleFeedCategories = explode(",", $categoryIds);
			$productCollection = $this->productCollectionFactory;
			$collection = $productCollection->create();
			$collection->setFlag('has_stock_status_filter', true);
			$collection->addCategoriesFilter(['in' => $googleFeedCategories]);
			$collection = $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
						->joinField('qty',
								'cataloginventory_stock_item',
								'qty',
								'product_id=entity_id',
								'{{table}}.stock_id=1',
								'left'
							)->joinTable('cataloginventory_stock_item', 'product_id=entity_id', array('stock_status' => 'is_in_stock'))
							->addAttributeToSelect('stock_status')
							->addFieldToFilter('stock_status', ['eq' => 1])
						->load();
			//echo '<pre>';print_r($collection->getData());die;				
			$responseRowData   = array();
			$responseRowData[] = array('id', 'title', 'description', 'availability', 'condition', 'price',  'link', 'image_link', 'brand');
			foreach($collection as $productData){
				$productId = $productData->getId();
				$product = $this->getProductData($productId);
				$sku = $product->getSku();
				$title = ucwords(strtolower($product->getName()));
				$description = ucwords(strtolower($product->getDescription()));
				$basePrice = $product->getPrice();
				$price  = $basePrice * (5/100);
				$finalPrice = $basePrice + $price;
				$pricewithTax = $currency.number_format($finalPrice, 2);
				$condition = 'new';
				$productLink = $product->getProductUrl();
				$availability = 'in stock';
				$status = 1;
				//product image
				$store = $this->_storeManager->getStore();
				$imageLink = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
				$brand = $product->getResource()->getAttribute("mgs_brand")->getFrontend()->getValue($product);
				$identifier_exists = 'no';
				$responseRowData[] = array($sku, $title, $description, $availability, $condition, $pricewithTax, $productLink, $imageLink, $brand);
			}
			 if (count($responseRowData) > 1) {
				$this->_createcsvfile($responseRowData);
			}
			$this->messageManager->addSuccess(__('Facebook shopping feed generated successfully'));
		}else{
			$this->messageManager->addError(__('Facebook shopping feed category not configured'));
		}	
		
		$RefererUrl=$this->_redirect->getRefererUrl();
		return $resultRedirect->setPath($RefererUrl);
    }
    
    protected function _isAllowed()
    {
        return true;
    }
	
	public function getProductData($productId)
	{
		$productRepository = $this->productRepository;
		$productData = $productRepository->getById($productId); 
		return $productData;	
	}
	
	public function _createcsvfile($responseRow)
    {
        if (count($responseRow) > 0) {

            $csvMediapath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'facebook_shopping/';
            if (!is_dir($csvMediapath)) {
                $csvMediapath = mkdir($csvMediapath, 0777, true);
				
            }

            $outputFile = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'facebook_shopping/' . "Tyresonline_Facebook_Shopping.csv";
            $handle = fopen($outputFile, 'w');
            foreach ($responseRow as $response) {
                fputcsv($handle, $response);
            }
			
			$file_name = "Tyresonline_Facebook_Shopping.csv";
			$this->downloadOutputCSV($responseRow, $file_name);

        }
    }
	
	public function downloadOutputCSV($responseRow, $file_name) {
       # output headers so that the file is downloaded rather than displayed
        header("Content-Type: text/csv");
		header("Content-Transfer-Encoding: UTF-8");
        header("Content-Disposition: attachment; filename=$file_name");
        # Disable caching - HTTP 1.1
        header("Cache-Control: no-cache, no-store, must-revalidate");
        # Disable caching - HTTP 1.0
        header("Pragma: no-cache");
        # Disable caching - Proxies
        header("Expires: 0");
    
        # Start the ouput
        $output = fopen("php://output", "w");
        
         # Then loop through the rows
        foreach ($responseRow as $row) {
            # Add the rows to the body
            fputcsv($output, $row); // here you can change delimiter/enclosure
        }
        # Close the stream off
        fclose($output);
		exit;
    }
}