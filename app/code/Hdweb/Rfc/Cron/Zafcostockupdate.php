<?php

namespace Hdweb\Rfc\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;

class Zafcostockupdate
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
		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

		$rfcEnable = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_enable', $storeScope);
		$rfcEnable = 1;
		if($rfcEnable == 1){
			$rfcUrl = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_url', $storeScope);
			$rfcUsername = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_username', $storeScope);
			$rfcPassword = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_password', $storeScope);
			$rfcFunction = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_function', $storeScope);
			$rfcEnableEmail = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_enable_email', $storeScope);
			$rfcEmailids = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_emailids', $storeScope);

			$parts = explode(",", $rfcEmailids);
            $to = implode(', ', $parts);

            $ipaddress = $this->getIpAddress();
			
			
			$connection = $this->_resouceConnection->getConnection();
			$cataloginventoryStockItemTable = $this->_resouceConnection->getTableName('cataloginventory_stock_item');
			
			$csvFile = '/home/cloudpanel/htdocs/new.tyresonline.ae/auto-stocks/zafco/zafco-stock-test.csv';
			//$csvFile = $this->_filesystem->getDirectoryRead(DirectoryList::ROOT)->getAbsolutePath().'auto-stocks/zafco/zafco-stock-test.csv';
			
			$csv = $this->objectManager->get('Magento\Framework\File\Csv');
			$csvDataRecords = $csv->getData($csvFile);
			unset($csvDataRecords[0]); //removes the header from csv file
			if(count($csvDataRecords) > 0){
				//echo '<pre>';print_r($csvDataRecords);die;
				$material_number_array = array();
				foreach($csvDataRecords as $csvData){
					if(empty($csvData[1])){
						continue;
					}
					$material_number = '';
					$dataQty = '';
					if($csvData[4] != ''){
						$material_number = $csvData[1].'-20'.$csvData[4];	
						$productId  = $this->getProductId($material_number);
						$dataQty = $csvData[3];
						if($productId){
							if($dataQty != ''){
								
								$isInStock = 0;
                                if($dataQty > 0){
                                    $isInStock = 1;
                                }
							}	
						}
						$material_number_array[] = array('material_number' => $material_number, 'qty' => $dataQty);
					}
						
				}
				
				$temp = [];
				$i = 0;
				$totalcount = 0;
				$successcount = 0;
				$failedcount = 0;
				$responseRowData = array();
				$responseRowData[] = array('material_number','Qty','Status','Message');
				
				$method = 'Auto';
				$rfc = $this->objectManager->create('Hdweb\Rfc\Model\Rfc');
				$rfc->setData('rfc_name','Zafco Product Stock Update');
				$rfc->setData('rfc_url', $rfcUrl);
				$rfc->setData('rfc_username', $rfcUsername);
				$rfc->setData('rfc_password', $rfcPassword);
				$rfc->setData('rfc_datetime', $this->getTodaysDate());
				$rfc->setData('rfc_enable', $rfcEnable);
				$rfc->setData('rfc_status', 'Running');
				$rfc->setData('rfc_run_method', $method);
				$rfc->setData('rfc_ip_address', $ipaddress);
				$rfc->save();
				$rfcid = $rfc->getRfcId();
				
				foreach($material_number_array as $value) {
					if(!array_key_exists($value['material_number'], $temp)) {
						$temp[$value['material_number']][$value['material_number']] = 0;
					}
					if($value['qty'] != ''){
						$temp[$value['material_number']][$value['material_number']] += $value['qty'];	
					}else{
						$temp[$value['material_number']][$value['material_number']] = $value['qty'];
					}
					
				}
				//array_values($temp);
				foreach($temp as $key => $tempData){
					$materialCode = $key;
					$qty = array_values($tempData)[0];
					$productId  = $this->getProductId($materialCode);
					if($productId){
						$isInStock = 0;
						if($qty > 0){
							$isInStock = 1;
						}
						$updateStocksql = "UPDATE ".$cataloginventoryStockItemTable. " SET qty = ".$qty." , is_in_stock = ".$isInStock." where product_id = ".$productId."";
								
						 $connection->query($updateStocksql);
						 
						 $successcount++;
                         $responseRowData[] = array($materialCode,$qty,'Success','Updated');
					}else{
						$failedcount++;
						$responseRowData[] = array($materialCode,$qty,'Failed','Not Updated');
					}
					$totalcount++;
				}
				
				if(count($responseRowData) > 1){
					$this->_createcsvfile($responseRowData, $rfcid);
				}
				$rfc = $this->objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
                $rfc->setData('rfc_datetime', $this->getTodaysDate());
                $rfc->setData('rfc_status', 'Success');
                $rfc->setData('rfc_total_record', $totalcount);
                $rfc->setData('rfc_total_sucess', $successcount);
                $rfc->setData('rfc_total_fail', $failedcount);
                $rfc->save();
				
				$this->_reIndexingAll();
                $this->flushCache();
			}
			

		} else {
            $this->_logger->info('RFC Settings are disabled.');
            //echo "RFC Settings are disabled."; //die();
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

            $outputFile = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath().'rfcfiles/'."Zafco_Stock_Update_".$this->getTodaysDate().".csv";

            $handle = fopen($outputFile, 'w');
            foreach ($responseRow as $response) {
                fputcsv($handle, $response);
            }

            $rfc = $this->objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
            $rfc->setData('rfc_manual_path', "Zafco_Stock_Update_".$this->getTodaysDate().".csv");
            $rfc->save();
        }
    }

    public function getProductId($material_number){
		$_product = $this->productCollectionFactory->create()
					->addAttributeToSelect('*')
					->addAttributeToFilter('material_number', $material_number);
		$productId = '';
		if($_product->getData()){
			$productData = $_product->getData();
			$productId = $productData[0]['entity_id'];
		}	
		return $productId;		
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