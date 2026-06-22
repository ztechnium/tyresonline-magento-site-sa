<?php

namespace Hdweb\Rfc\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;

class Staffproductupdate
{
	protected $_logger;
	protected $scopeConfig;
	protected $_timezoneInterface;
	protected $objectManager;
	protected $_resouceConnection;
	protected $rfcCollection;
    protected $productCollectionFactory;
    protected $_filesystem;
    protected $_storeManager;
    protected $_indexerFactory;
    protected $_indexerCollectionFactory;

	public function __construct(
		\Psr\Log\LoggerInterface $logger,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
		\Magento\Framework\App\ResourceConnection $resouceConnection,
		\Hdweb\Rfc\Model\ResourceModel\Rfc\Collection $rfcCollection,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Filesystem $_filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \Magento\Indexer\Model\Indexer\CollectionFactory $indexerCollectionFactory
	){
		$this->_logger = $logger;
		$this->objectManager = $objectManager;
		$this->scopeConfig = $scopeConfig;
		$this->_timezoneInterface = $timezoneInterface;
		$this->_resouceConnection = $resouceConnection;
		$this->rfcCollection = $rfcCollection;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_filesystem = $_filesystem;
        $this->_storeManager = $storeManager;
        $this->_indexerFactory = $indexerFactory;
        $this->_indexerCollectionFactory = $indexerCollectionFactory;
	}

	public function execute(){
		
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

	public function getIpAddress(){       
        $remoteAddress = $this->objectManager->create('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');
        return $remoteAddress->getRemoteAddress();
    }

    public function _createcsvfile($responseRow, $rfcid){
        if(count($responseRow) > 0){

            $csvMediapath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath().'rfcfiles/';
            if(!is_dir($csvMediapath)){
                $csvMediapath = mkdir($csvMediapath);
            }

            $outputFile = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath().'rfcfiles/'."Stock_Update_".$this->getTodaysDate().".csv";

            $handle = fopen($outputFile, 'w');
            foreach ($responseRow as $response) {
                fputcsv($handle, $response);
            }

            $rfc = $this->objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
            $rfc->setData('rfc_manual_path', "Stock_Update_".$this->getTodaysDate().".csv");
            $rfc->save();
        }
    }

    public function updateStockQtyBefore($response,$yearIdOptionSqlResult,$eavAttributeOptionValueTable,$connection,$cataloginventoryStockItemTable,$storeScope){
        if (count($response) > 0) {
            $oldQtyArray = array();
            foreach ($response as $data) {
                $dataItcode = trim($data['ITCODE']);
                $dataYear = trim($data['ITMODEL']);
                $dataQty = trim($data['STOCK']);
                $_product = $this->productCollectionFactory->create()
                            ->addAttributeToSelect('*')
                            // ->addWebsiteFilter(2)
                            ->addAttributeToFilter('gcc_code', $dataItcode)
							->addAttributeToFilter('productfrom', 12121)
							->addAttributeToFilter('auto_stock', 1);
                
                $product = $_product->getData();
                
                if(!empty($product) || $product != null){
                    $productId = $product[0]['entity_id'];                            
                    if($productId != null && $productId != ""){
                        $getOldStocksql = "SELECT qty FROM ".$cataloginventoryStockItemTable." WHERE product_id = ".$productId." LIMIT 1";
                        $oldQtyResult = $connection->fetchCol($getOldStocksql);
                        $oldQty = $oldQtyResult[0];
                        $oldQtyArray[$productId] = $oldQty;
                    }
                }
            }
        }

        if($this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_clearstock', $storeScope) == 1){
            $updateStocksql = "UPDATE ".$cataloginventoryStockItemTable. " SET qty = 0, is_in_stock = 0";
            $connection->query($updateStocksql);
        }
        return $oldQtyArray;
    }

    public function _reIndexingAll(){
        $indexerCollection = $this->_indexerCollectionFactory->create();
        $ids = $indexerCollection->getAllIds();
        foreach ($ids as $id) {
            if($id == 'cataloginventory_stock'){
                $idx = $this->_indexerFactory->create()->load($id);
                $idx->reindexAll($id); // this reindexes all
            }
        }
    }
	
	public function flushCache(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$_cacheTypeList = $objectManager->create('Magento\Framework\App\Cache\TypeListInterface');
		$_cacheFrontendPool = $objectManager->create('Magento\Framework\App\Cache\Frontend\Pool');
		$types = array('config','layout','block_html','collections','reflection','db_ddl','eav','config_integration','config_integration_api','full_page','translate','config_webservice');
		foreach ($types as $type) {
			$_cacheTypeList->cleanType($type);
		}
		foreach ($_cacheFrontendPool as $cacheFrontend) {
			$cacheFrontend->getBackend()->clean();
		}
	}
}