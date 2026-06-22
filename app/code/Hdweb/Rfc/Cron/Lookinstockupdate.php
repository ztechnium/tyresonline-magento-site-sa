<?php
namespace Hdweb\Rfc\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;

class Lookinstockupdate
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

		//$rfcEnable = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_enable', $storeScope);
		$rfcEnable = 1;
		if($rfcEnable == 1){
			
			$rfcUrl = 'stopandgo-db.cyvymeknumbz.us-east-1.rds.amazonaws.com'; //$this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_url', $storeScope);
            $rfcUsername = 'devpatelab'; //$this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_username', $storeScope);
            $rfcPassword = 'FpxTyACCX1QDIOiK7FsF'; //$this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_password', $storeScope);
            $rfcFunction = 'LOOKIN'; //$this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_function', $storeScope);
            $rfcEnableEmail = ''; //$this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_enable_email', $storeScope);
            $rfcEmailids = ''; //$this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_emailids', $storeScope);

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

            $query = "SELECT * FROM [LOOKIN].[dbo].[WITMAST]";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            //$result = $stmt->fetchAll();
            $response = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            // echo "<pre>";
            // print_r($result);
            
            // $ch = curl_init($rfcUrl);
            // curl_setopt($ch, CURLOPT_POST, 1);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, array('username'=>$rfcUsername, 'password'=>$rfcPassword, 'function'=>$rfcFunction));
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // $result = curl_exec($ch);
            // $response = json_decode($result, true);
            // curl_close($ch);
            
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

            $manually = 0;
			if ($manually == 1) {
				$method = 'Manually';
			}else{
				$method = 'Auto';
			}

			$totalcount = 0;
            $successcount = 0;
            $failedcount = 0;
            $rfc = $this->objectManager->create('Hdweb\Rfc\Model\Rfc');
            $rfc->setData('rfc_name','Lookin Product Stock Update');
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

            if (count($response) == 0) {            
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
                // If its get an Response
            }else{
                if(is_array($response)){
                    $responseRowData = array();
                    $responseRowData[] = array('Item Code','Sku','Item Description','Year','Old Qty','Qty','Vendor Qty','Status','Message');
                    //Optained Catalog Entity Id
                    $catalogEntitySql = "SELECT entity_type_id FROM ".$eavEntityTypeTable." WHERE entity_type_code = 'catalog_product' LIMIT 1";
                    $resultCatalog = $connection->fetchCol($catalogEntitySql);
                    $catalogEntityId = $resultCatalog[0];

                    //Optained Product From attribute id  ----- PRODUCT FROM
                    // $productfromIdSql = "SELECT attribute_id FROM ".$eavAttributeTable." WHERE attribute_code = 'productfrom' AND entity_type_id = ".$catalogEntityId." LIMIT 1";
                    // $productFromIdResult = $connection->fetchCol($productfromIdSql);
                    // $productFromId = $productFromIdResult[0];
                  
                    //Optained Product From Options option id
                    /*$productFromOptionSql = "SELECT option_id FROM ".$eavAttributeOptionTable." WHERE attribute_id = ".$productFromId;
                    $productFromResult = $connection->fetchCol($productFromOptionSql);
                    
                    foreach ($productFromResult as $productFromOption) {
                        $productFromOptionValueSql = "SELECT option_id FROM ".$eavAttributeOptionValueTable." WHERE option_id = ".$productFromOption." AND value = 'ZAFCO' LIMIT 1";

                        $productFromOptionValueSqlResult = $connection->fetchCol($productFromOptionValueSql);
                        if(!empty($productFromOptionValueSqlResult)){
                            $productFromOptionValueId = $productFromOptionValueSqlResult[0];
                            continue;
                        }
                    }*/
                    //End of Optained Product From attribute id  ----- PRODUCT FROM

                    //Optained Product From attribute id  ----- PRODUCT YEAR
                    $yearIdSql = "SELECT attribute_id FROM ".$eavAttributeTable." WHERE attribute_code = 'dot' AND entity_type_id = ".$catalogEntityId." LIMIT 1";
                    $yearIdResult = $connection->fetchCol($yearIdSql);
                    $yearId = $yearIdResult[0];
                  
                    //Optained Product From Options option id
                    $yearIdOptionSql = "SELECT option_id FROM ".$eavAttributeOptionTable." WHERE attribute_id = ".$yearId;
                    $yearIdOptionSqlResult = $connection->fetchCol($yearIdOptionSql);                    
                    //End of Optained Product From attribute id  ----- PRODUCT FROM

                    //Obtained Old Qty
                    /*$oldQtyArr = $this->updateStockQtyBefore($response,$yearIdOptionSqlResult,$eavAttributeOptionValueTable,$connection,$productFromOptionValueId,$cataloginventoryStockItemTable,$storeScope);*/

                    $oldQtyArr = $this->updateStockQtyBefore($response,$yearIdOptionSqlResult,$eavAttributeOptionValueTable,$connection,$cataloginventoryStockItemTable,$storeScope);
					
					$productFactory = $this->objectManager->get('Magento\Catalog\Model\ResourceModel\ProductFactory');
					$poductReource = $productFactory->create();
					$attributeCode = 'dot';
					$dotOptionValue = '';
					$attribute = $poductReource->getAttribute($attributeCode);
					
                    foreach ($response as $data) {
                        /*$dataSku = $data['sku'];
                        $dataYear = $data['year'];
                        $dataQty = $data['qty'];*/

                        $dataItcode = trim($data['ITCODE']);
                        $dataDescription = trim($data['ITDESC']);
                        // $dataSku = $data['MATNR'];
                        // $dataBrand = $data['MATKL'];
                        //$dataYear = trim($data['ITMODEL']);
						$dataYear = trim($data['ITSCAT']);
                        $dataQty = trim($data['STOCK']);
						$cost = trim($data['SPRICE']);
                        // $yearOptionValueId = '';
                        
                        // foreach ($yearIdOptionSqlResult as $yearId) {
                        //     $yearIdOptionValueSql = "SELECT option_id FROM ".$eavAttributeOptionValueTable." WHERE option_id = ".$yearId." AND value = ".$dataYear." LIMIT 1";
                        //     $yearIdOptionValueSqlResult = $connection->fetchCol($yearIdOptionValueSql);
                        //     if(!empty($yearIdOptionValueSqlResult)){
                        //         $yearOptionValueId = $yearIdOptionValueSqlResult[0];
                        //         continue;
                        //     }
                        // }
                        
						if ($attribute->usesSource()) {
							$dotOptionValue = $attribute->getSource()->getOptionId($dataYear);
						}
						
                        $_product = $this->productCollectionFactory->create()
                                    ->addAttributeToSelect('*')
                                    ->addAttributeToFilter('lookin_code', $dataItcode)
                                    ->addAttributeToFilter('dot', $dotOptionValue);
                        
                        $product = $_product->getData();
                        
                        if(!empty($product) || $product != null){
                            $productId = $product[0]['entity_id'];                            

                            if($productId != null && $productId != ""){
                                //Update Stock Item Table
								$prodSku = $product[0]['sku'];
                                $isInStock = 0;
                                if($dataQty > 3){
                                    $isInStock = 1;
									
									$updateStocksql = "UPDATE ".$cataloginventoryStockItemTable. " SET qty = ".$dataQty." , is_in_stock = ".$isInStock." where product_id = ".$productId."";
									$connection->query($updateStocksql);
									$this->_logger->info("Auto Stock Update for Product Sku ".$productId."-".$dataItcode."-".$dataYear);
                                }
                                /*  else
                                {
                                    $dataQty = 0;
                                } */
                                
                                //echo "Stock Update for Product Sku ".$productId."-".$dataSku."-".$dataYear." </br>";
                                $successcount++;
                                $responseRowData[] = array($dataItcode,$prodSku,$dataDescription,$dataYear,$oldQtyArr[$productId],$dataQty,$dataQty,'Success','Updated');
                            }
                        }else{
                            $this->_logger->info("Auto Failed Stock Update for Product Sku ".$dataItcode."-".$dataYear);
                            //echo "Failed Stock Update for Product Sku ".$dataSku."-".$dataYear." </br>";
                            $failedcount++;
                            $responseRowData[] = array($dataItcode,'No SKU',$dataDescription,$dataYear,'N/A','N/A',$dataQty,'Failed','Product Not Found');
                        }
                        $totalcount++;
                    }

                    if(count($responseRowData) > 1){
                        $this->_createcsvfile($responseRowData, $rfcid);
                    }

                    $this->_reIndexingAll();

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

            $outputFile = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath().'rfcfiles/'."Lookin_Stock_Update_".$this->getTodaysDate().".csv";

            $handle = fopen($outputFile, 'w');
            foreach ($responseRow as $response) {
                fputcsv($handle, $response);
            }

            $rfc = $this->objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
            $rfc->setData('rfc_manual_path', "Lookin_Stock_Update_".$this->getTodaysDate().".csv");
            $rfc->save();
        }
    }

    /*public function updateStockQtyBefore($response,$yearIdOptionSqlResult,$eavAttributeOptionValueTable,$connection,$productFromOptionValueId,$cataloginventoryStockItemTable,$storeScope){*/
    public function updateStockQtyBefore($response,$yearIdOptionSqlResult,$eavAttributeOptionValueTable,$connection,$cataloginventoryStockItemTable,$storeScope){
        if (count($response) > 0) {
            $oldQtyArray = array();
			
			$productFactory = $this->objectManager->get('Magento\Catalog\Model\ResourceModel\ProductFactory');
			$poductReource = $productFactory->create();
			$attributeCode = 'dot';
			$dotOptionValue = '';
			$attribute = $poductReource->getAttribute($attributeCode);
			
            foreach ($response as $data) {
                /*$dataSku = $data['sku'];
                $dataYear = $data['year'];
                $dataQty = $data['qty'];*/

                $dataItcode = trim($data['ITCODE']);
                // $dataSku = $data['MATNR'];
                // $dataBrand = $data['MATKL'];
               // $dataYear = $data['ITMODEL'];
				$dataYear = trim($data['ITSCAT']);
                $dataQty = trim($data['STOCK']);
                // $yearOptionValueId = '';
                
                foreach ($yearIdOptionSqlResult as $yearId) {
                    $yearIdOptionValueSql = "SELECT option_id FROM ".$eavAttributeOptionValueTable." WHERE option_id = ".$yearId." AND value = ".$dataYear." LIMIT 1";
                    $yearIdOptionValueSqlResult = $connection->fetchCol($yearIdOptionValueSql);
                    if(!empty($yearIdOptionValueSqlResult)){
                        $yearOptionValueId = $yearIdOptionValueSqlResult[0];
                        continue;
                    }
                }
                
				if ($attribute->usesSource()) {
					$dotOptionValue = $attribute->getSource()->getOptionId($dataYear);
				}
				
                $_product = $this->productCollectionFactory->create()
                            ->addAttributeToSelect('*')
                            ->addAttributeToFilter('lookin_code', $dataItcode)
                            ->addAttributeToFilter('dot', $dotOptionValue);
                
                $product = $_product->getData();
                
                if(!empty($product) || $product != null){
                    $productId = $product[0]['entity_id'];                            
                
                    if($productId != null && $productId != ""){
                        //Update Stock Item Table
                        /*$isInStock = 0;
                        if($dataQty > 0){
                            $isInStock = 1;
                        }*/
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
}