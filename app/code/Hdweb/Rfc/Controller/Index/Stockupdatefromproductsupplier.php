<?php 

namespace Hdweb\Rfc\Controller\Index;
 
use Magento\Framework\App\Filesystem\DirectoryList;

class Stockupdatefromproductsupplier extends \Magento\Framework\App\Action\Action {

    protected $resultPageFactory;
    protected $_logger;
    protected $scopeConfig;
    protected $_timezoneInterface;
    protected $_resouceConnection;
    protected $_filesystem;
    protected $_storeManager;
    protected $_indexerFactory;
    protected $_indexerCollectionFactory;

    /**
     * Constructor
     * 
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\App\ResourceConnection $resouceConnection,
        \Magento\Framework\Filesystem $_filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \Magento\Indexer\Model\Indexer\CollectionFactory $indexerCollectionFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->_logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->_timezoneInterface = $timezoneInterface;
        $this->_resouceConnection = $resouceConnection;
        $this->_filesystem = $_filesystem;
        $this->_storeManager = $storeManager;
        $this->_indexerFactory = $indexerFactory;
        $this->_indexerCollectionFactory = $indexerCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Execute view action
     * 
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {   
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $rfcEnable = 1; //$this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_enable', $storeScope);

        if($rfcEnable == 1){
            
            $connection = $this->_resouceConnection->getConnection();
			$eavEntityTypeTable = $this->_resouceConnection->getTableName('eav_entity_type');
			$eavAttributeTable = $this->_resouceConnection->getTableName('eav_attribute');
            $cataloginventoryStockItemTable = $this->_resouceConnection->getTableName('cataloginventory_stock_item');
			$catalogPriceDecimalTable = $this->_resouceConnection->getTableName('catalog_product_entity_decimal');	
			$connection = $this->_resouceConnection->getConnection();
			
			$rfcSupplierTable = $this->_resouceConnection->getTableName('rfc_supplierproducts');
			$query = "SELECT web_product_sku, web_product_id, min(item_price) as cost ,sum(item_qty) as total_qty FROM " . $rfcSupplierTable . " WHERE `type` LIKE '%Supplier%' and web_product_sku != '' group by web_product_sku, web_product_id";
			
			//Optained Catalog Entity Id
			$catalogEntitySql = "SELECT entity_type_id FROM " . $eavEntityTypeTable . " WHERE entity_type_code = 'catalog_product' LIMIT 1";
			$resultCatalog    = $connection->fetchCol($catalogEntitySql);
			$catalogEntityId  = $resultCatalog[0];

			//Optained Product From attribute id  ----- PRODUCT YEAR
			$costIdSql    = "SELECT attribute_id FROM " . $eavAttributeTable . " WHERE attribute_code = 'cost' AND entity_type_id = " . $catalogEntityId . " LIMIT 1";
			$costIdResult = $connection->fetchCol($costIdSql);
			$costAttrId   = $costIdResult[0];
			
			$result = $connection->fetchAll($query);
			
			//echo '<pre>';print_r($result);die;
			foreach ($result as $data) {
					$productId = $data['web_product_id']; 
					$totalQty = $data['total_qty']; 
					$cost = $data['cost']; 
					//Update Stock Item Table
					$isInStock = 0;
					
					if($totalQty >= 7) {
						$isInStock = 1;
					}
					
					$updateStocksql = "UPDATE ".$cataloginventoryStockItemTable. " SET qty = ".$totalQty." , is_in_stock = ".$isInStock." where product_id = ".$productId."";
				  
					$updateCostsql = "UPDATE ".$catalogPriceDecimalTable. " SET value = ".$cost." WHERE attribute_id = ".$costAttrId." AND entity_id = ".$productId."";
					
					$connection->query($updateStocksql);
					$connection->query($updateCostsql);
					
					//if($this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_debugmode', $storeScope) == 1){
						$date = $this->getTodaysDate();
						$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/stockupdatefromsupplier.log');
						$logger = new \Zend\Log\Logger();
						$logger->addWriter($writer);
						$logger->info('Stock updated for product id - '.$productId.' Cost -'. $cost.' Qty - '.$totalQty.' Updated on '.$date);
					//}
			}
            $this->_reIndexingAll();      
            
            die('Complete');

        } else {
            echo "RFC Settings are disabled."; die();
        }
    }

    public function getStore()
    {
        return $this->_storeManager->getStore();
    }
	
	public function getTodaysDate(){
        $localeTimezone = $this->_timezoneInterface->getConfigTimezone('store', $this->getStore());
        date_default_timezone_set($localeTimezone);
        return $this->_timezoneInterface->date()->format('Y-m-d H:i:s');
    }
	
    public function _reIndexingAll(){
        $indexerCollection = $this->_indexerCollectionFactory->create();
        /* $ids = $indexerCollection->getAllIds();
        foreach ($ids as $id) {
            if($id == 'cataloginventory_stock'){
                $idx = $this->_indexerFactory->create()->load($id);
                $idx->reindexAll($id); // this reindexes all
            }
        } */
		$indexerIds = array(
			'catalog_product_price',
			'cataloginventory_stock',
		);
		foreach ($indexerIds as $indexerId) {
			$indexer = $this->_indexerFactory->create();
			$indexer->load($indexerId);
			$indexer->reindexAll();
		}
    }
}