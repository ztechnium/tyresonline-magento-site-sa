<?php

namespace Hdweb\Rfc\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;

class Stockupdate
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

            try {
            $dbuser = $rfcUsername;
            $dbpass = $rfcPassword;
            $dbhost = $rfcUrl;
            $dbname= $rfcFunction;
            $conn = new \PDO("dblib:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
            }catch (\PDOException $e) {
            echo "Error : " . $e->getMessage() . "<br/>";
            die();
            }

            $expectedCount = 5000;
			$query1 = "SELECT * FROM [gcccoastmsdb].[dbo].[WITMAST]";
            $stmt1 = $conn->prepare($query1);
            $stmt1->execute();
            $response = $stmt1->fetchAll(\PDO::FETCH_ASSOC);
			$actualCount = count($response);
            // echo "<pre>";
            // print_r($result);die;
            
            if($this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_debugmode', $storeScope) == 1){
            	$date = $this->getTodaysDate();
            	$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/RFC-'.$rfcFunction.'.log');
				$logger = new \Zend\Log\Logger();
				$logger->addWriter($writer);
				$logger->info($date);
				$logger->info($rfcUrl);
				$logger->info($result);
				$logger->info('-------------------------------------------------------------------------');
            }

			$connection = $this->_resouceConnection->getConnection();
            $eavEntityTypeTable = $this->_resouceConnection->getTableName('eav_entity_type');
            $eavAttributeTable = $this->_resouceConnection->getTableName('eav_attribute');
            $eavAttributeOptionTable = $this->_resouceConnection->getTableName('eav_attribute_option');
            $eavAttributeOptionValueTable = $this->_resouceConnection->getTableName('eav_attribute_option_value');
            $catalogProductEntityVarcharTable = $this->_resouceConnection->getTableName('catalog_product_entity_varchar');
            $catalogProductEntityIntTable = $this->_resouceConnection->getTableName('catalog_product_entity_int');
            $cataloginventoryStockItemTable = $this->_resouceConnection->getTableName('cataloginventory_stock_item');
			$catalogDatetimeTable = $this->_resouceConnection->getTableName('catalog_product_entity_datetime');
			$gccCostAttrId = $this->scopeConfig->getValue('productsearch/general/gcc_cost_attr_id', $storeScope);
			$gccQtyAttrId = $this->scopeConfig->getValue('productsearch/general/gcc_qty_attr_id', $storeScope);
			$specialToDateAttrId = 78;

            $method = 'Auto';

			$totalcount = 0;
            $successcount = 0;
            $failedcount = 0;
            $rfc = $this->objectManager->create('Hdweb\Rfc\Model\Rfc');
            $rfc->setData('rfc_name','Product Stock Update');
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

            if($actualCount < $expectedCount){             
            	$rfc = $this->objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
                $rfc->setData('rfc_datetime', $this->getTodaysDate());
                $rfc->setData('rfc_status', 'Failed');
                $rfc->setData('rfc_total_record', $totalcount);
                $rfc->setData('rfc_total_sucess', $successcount);
                $rfc->setData('rfc_total_fail', $failedcount);
                $rfc->save();
                //Response Row Data for csv file if response is 0
                $responseRowData[] = array('Requested URL','Response Data', 'Status');
                $responseRowData[] = array($rfcUrl,$rfcFunction,'Failed');
                if(count($responseRowData) > 1){
                    $this->_createcsvfile($responseRowData, $rfcid);
                }
                
                if($rfcEnableEmail == 1){
                    $subject = "RFC Stock Update Issue - ".$rfcFunction;
                    $message = "RFC Stock Update Issue. Unable to get data from server.";
                    $retval = mail($to,$subject,$message);
                    
                    if( $retval == true ) {
                        $this->_logger->info('Unable to get server response. Mail sent successfully.');
                        //echo "Unable to get server response. Mail sent successfully.";
                    } else {
                        $this->_logger->info('Unable to get server response. Mail could not be sent.');
                        //echo "Unable to get server response. Mail could not be sent.";
                    }
                }
                // If its get an Response - SELECT * FROM [gcccoastmsdb].[dbo].[WITMAST] WHERE [STOCK] > 0
            }else{
				$query = "SELECT * FROM [gcccoastmsdb].[dbo].[WITMAST] WHERE [ITMODEL] > 2018";
				$stmt = $conn->prepare($query);
				$stmt->execute();
				$response = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                if(is_array($response)){
                    $responseRowData = array();
                    // $responseRowData[] = array('Sku','Item Description','Year','Old Qty','Qty','Vendor Qty','Old Price','New Price','Vendor Price','Status','Message');
                    $responseRowData[] = array('Sku','Item Description','Year','Old Qty','Qty','Vendor Qty','Status','Message');
                    //Optained Catalog Entity Id
                    $catalogEntitySql = "SELECT entity_type_id FROM ".$eavEntityTypeTable." WHERE entity_type_code = 'catalog_product' LIMIT 1";
                    $resultCatalog = $connection->fetchCol($catalogEntitySql);
                    $catalogEntityId = $resultCatalog[0];

                    //Optained Product From attribute id  ----- PRODUCT YEAR
                    $yearIdSql = "SELECT attribute_id FROM ".$eavAttributeTable." WHERE attribute_code = 'dot' AND entity_type_id = ".$catalogEntityId." LIMIT 1";
                    $yearIdResult = $connection->fetchCol($yearIdSql);
                    $yearId = $yearIdResult[0];
                  
                    //Optained Product From Options option id
                    $yearIdOptionSql = "SELECT option_id FROM ".$eavAttributeOptionTable." WHERE attribute_id = ".$yearId;
                    $yearIdOptionSqlResult = $connection->fetchCol($yearIdOptionSql);                    
                    //End of Optained Product From attribute id  ----- PRODUCT FROM

                    //Obtained Old Qty
                    $oldQtyArr = $this->updateStockQtyBefore($response,$yearIdOptionSqlResult,$eavAttributeOptionValueTable,$connection,$cataloginventoryStockItemTable,$storeScope);
					$objectManager =   \Magento\Framework\App\ObjectManager::getInstance();
					$_productloader = $objectManager->create('Magento\Catalog\Model\ProductFactory');
					//$oldPrice = '';
					
					$productFactory = $this->objectManager->get('Magento\Catalog\Model\ResourceModel\ProductFactory');
					$poductReource = $productFactory->create();
					$attributeCode = 'dot';
					$dotOptionValue = '';
					$attribute = $poductReource->getAttribute($attributeCode);
					
                    foreach ($response as $data) {
                        $dataItcode = trim($data['ITCODE']);
                        $dataDescription = trim($data['ITDESC']);
                        $dataYear = trim($data['ITMODEL']);
                        $dataQty = trim($data['STOCK']);
                        $fittPrice = trim($data['FITTPRICE']);
                        $gccCOst = trim($data['SPRICE']);
                        $gccQty = trim($data['STOCK']);
                         

						if ($attribute->usesSource()) {
							$dotOptionValue = $attribute->getSource()->getOptionId($dataYear);
						}	
                        $_product = $this->productCollectionFactory->create()
                                    ->addAttributeToSelect('*')
                                   // ->addAttributeToFilter('gc_item_code', $dataItcode);
                                     ->addAttributeToFilter('gcc_code', $dataItcode)
                                    // ->addAttributeToFilter('productfrom', 12121)
									 ->addAttributeToFilter('dot', $dotOptionValue)
									 ->addAttributeToFilter('auto_stock', 1);
                        
                        $product = $_product->getData();
                        
                        if(!empty($product) || $product != null){
                            $productId = $product[0]['entity_id'];                            
                            if($productId != null && $productId != ""){
								$productObject = $_productloader->create()->load($productId);
								$specialPrice = $productObject->getSpecialPrice();
								$specialPriceFromDate = $productObject->getSpecialFromDate(); //2020-10-22 00:00:00
								$specialPriceToDate = $productObject->getSpecialToDate();
								
                                //Update Stock Item Table
                                $isInStock = 0;
                                if($dataQty > 0){
                                    $isInStock = 1;
                                }
                                else
                                {
                                    $dataQty = 0;
                                }
								if($specialPrice != '' && $specialPrice > 0 && $specialPriceFromDate != '' && $specialPriceToDate != ''){
									$currentDate =  strtotime(date('Y-m-d H:i:s'));
									$newDate = strtotime($specialPriceToDate);
									$nextDate = strtotime("+7 day", $currentDate);
									$pastDate = strtotime("-7 day", $currentDate);
									if($dataQty > 3) {
										$newSpecialPriceTo = date('Y-m-d H:i:s', $nextDate);
									}
									else
									{
										$newSpecialPriceTo = date('Y-m-d H:i:s', $pastDate);
									}	
									
									$updateSpecialToDatesql = "UPDATE ".$catalogDatetimeTable. " SET value = '" . $newSpecialPriceTo . "' WHERE attribute_id = ".$specialToDateAttrId." AND entity_id = ".$productId."";
									$connection->query($updateSpecialToDatesql);
								}
                                $updateStocksql = "UPDATE ".$cataloginventoryStockItemTable. " SET qty = ".$dataQty." , is_in_stock = ".$isInStock." where product_id = ".$productId."";
								
								$updateGccCostsql = "UPDATE ".$catalogProductEntityVarcharTable. " SET value = ".$gccCOst." WHERE attribute_id = ".$gccCostAttrId." AND entity_id = ".$productId."";
								
								$updateGccQtysql = "UPDATE ".$catalogProductEntityVarcharTable. " SET value = ".$gccQty." WHERE attribute_id = ".$gccQtyAttrId." AND entity_id = ".$productId."";
								
                                $connection->query($updateStocksql);
                                $connection->query($updateGccCostsql);
                                $connection->query($updateGccQtysql);
                                $this->_logger->info("Stock Update for Product Sku ".$productId."-".$dataItcode."-".$dataYear);
                                //echo "Stock Update for Product Sku ".$productId."-".$dataSku."-".$dataYear." </br>";
                                $successcount++;
                                $responseRowData[] = array($dataItcode,$dataDescription,$dataYear,$oldQtyArr[$productId],$dataQty,$dataQty,'Success','Updated');
                            }
                        }else{
                            $this->_logger->info("Failed Stock Update for Product Sku ".$dataItcode."-".$dataYear);
                            //echo "Failed Stock Update for Product Sku ".$dataSku."-".$dataYear." </br>";
                            $failedcount++;
                            $responseRowData[] = array($dataItcode,$dataDescription,$dataYear,'N/A','N/A',$dataQty,'Failed','Product Not Found');
                        }
                        $totalcount++;
                    }

                    if(count($responseRowData) > 1){
                        $this->_createcsvfile($responseRowData, $rfcid);
                    }

                    $this->_reIndexingAll();
                    $this->flushCache();

                }else{
                    if($rfcEnableEmail == 1){
                        $subject = "RFC connection issue - ".$rfcFunction;
                        $message = "Could not connect to server.</b>";
                        $retval = mail($to,$subject,$message);
                    }
                    if( $retval == true ) {
                        $this->_logger->info('Could not connect to server. Mail sent successfully.');
                        //echo "Could not connect to server. Mail sent successfully.";
                    }else {
                        $this->_logger->info('Could not connect to server. Mail could not be sent.');
                        //echo "Could not connect to server. Mail could not be sent.";
                    }
                }

                $rfc = $this->objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
                $rfc->setData('rfc_datetime', $this->getTodaysDate());
                $rfc->setData('rfc_status', 'Success');
                $rfc->setData('rfc_total_record', $totalcount);
                $rfc->setData('rfc_total_sucess', $successcount);
                $rfc->setData('rfc_total_fail', $failedcount);
                $rfc->save();

                if($rfcEnableEmail == 1){
                    $subject = "RFC run successfully - ".$rfcFunction;
                    $message = "Stock Update RFC executed successfully.";
                    $retval = mail($to,$subject,$message);
                    
                    if( $retval == true ) {
                        $this->_logger->info('Inventory updated. Mail sent successfully!');
                        //echo "Inventory updated. Mail sent successfully!";
                    } else {
                        $this->_logger->info('Inventory updated. Mail could not be sent!');
                        //echo "Inventory updated. Mail could not be sent!";
                    }
                }
            }            
            
            //die('Complete');

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
			
			$productFactory = $this->objectManager->get('Magento\Catalog\Model\ResourceModel\ProductFactory');
			$poductReource = $productFactory->create();
			$attributeCode = 'dot';
			$dotOptionValue = '';
			$attribute = $poductReource->getAttribute($attributeCode);
			
            foreach ($response as $data) {
                $dataItcode = trim($data['ITCODE']);
                $dataYear = trim($data['ITMODEL']);
                $dataQty = trim($data['STOCK']);
				
				if ($attribute->usesSource()) {
					$dotOptionValue = $attribute->getSource()->getOptionId($dataYear);
				}	
                $_product = $this->productCollectionFactory->create()
                            ->addAttributeToSelect('*')
                            // ->addWebsiteFilter(2)
                            ->addAttributeToFilter('gcc_code', $dataItcode)
							//->addAttributeToFilter('productfrom', 12121)
							->addAttributeToFilter('dot', $dotOptionValue)
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