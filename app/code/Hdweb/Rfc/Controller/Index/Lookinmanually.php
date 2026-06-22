<?php 

namespace Hdweb\Rfc\Controller\Index;
 
use Magento\Framework\App\Filesystem\DirectoryList;

class Lookinmanually extends \Magento\Framework\App\Action\Action {

    protected $resultPageFactory;
    protected $stockupdate;
    protected $_logger;
    protected $scopeConfig;
    protected $_timezoneInterface;
    protected $_resouceConnection;
    protected $rfcCollection;
    protected $productCollectionFactory;
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
        \Hdweb\Rfc\Cron\Stockupdate $stockupdate,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\App\ResourceConnection $resouceConnection,
        \Hdweb\Rfc\Model\ResourceModel\Rfc\Collection $rfcCollection,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Filesystem $_filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \Magento\Indexer\Model\Indexer\CollectionFactory $indexerCollectionFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->stockupdate = $stockupdate;
        $this->_logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->_timezoneInterface = $timezoneInterface;
        $this->_resouceConnection = $resouceConnection;
        $this->rfcCollection = $rfcCollection;
        $this->productCollectionFactory = $productCollectionFactory;
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
            $response = $stmt->fetchAll(\PDO::FETCH_ASSOC);
           
            //echo "<pre>";
           // print_r($response);die;
          
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
           // $catalogPriceIndexTable = $this->_resouceConnection->getTableName('catalog_product_index_price');
            //$catalogPriceDecimalTable = $this->_resouceConnection->getTableName('catalog_product_entity_decimal');
			$catalogDatetimeTable = $this->_resouceConnection->getTableName('catalog_product_entity_datetime');
			$lookinCostAttrId = $this->scopeConfig->getValue('productsearch/general/lookin_cost_attr_id', $storeScope);
			$lookinQtyAttrId = $this->scopeConfig->getValue('productsearch/general/lookin_qty_attr_id', $storeScope);
			$specialToDateAttrId = 78;
            /* $manually = 1;
            if ($manually == 1) {
                $method = 'Manually';
            }else{
                $method = 'Auto';
            } */
			
			$method = 'Manually';

            $totalcount = 0;
            $successcount = 0;
            $failedcount = 0;
            $rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc');
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
                $rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
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
                        echo "Unable to get server response. Mail sent successfully.";
                    } else {
                        echo "Unable to get server response. Mail could not be sent.";
                    }
                }
                // If its get an Response
            }else{
                if(is_array($response)){
                    $responseRowData = array();
                    //$responseRowData[] = array('Sku','Item Description','Year','Old Qty','Qty','Vendor Qty','Old Price','New Price','Vendor Price','Status','Message');
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
					
					$productFactory = $objectManager->get('Magento\Catalog\Model\ResourceModel\ProductFactory');
					$poductReource = $productFactory->create();
					$attributeCode = 'dot';
					$dotOptionValue = '';
					$attribute = $poductReource->getAttribute($attributeCode);
					
					//$oldPrice = '';
                    foreach ($response as $data) {

                        $dataItcode = trim($data['ITCODE']);
                        $dataDescription = trim($data['ITDESC']);
                       // $dataYear = trim($data['ITMODEL']);
					    $dataYear = trim($data['ITSCAT']);
                        $dataQty = trim($data['STOCK']);
                        $lookinCOst = trim($data['SPRICE']);
                        $lookinQty = trim($data['STOCK']);
                        
						if ($attribute->usesSource()) {
							$dotOptionValue = $attribute->getSource()->getOptionId($dataYear);
						}
							
                        $_product = $this->productCollectionFactory->create()
                                    ->addAttributeToSelect('*')
                                   // ->addAttributeToFilter('gc_item_code', $dataItcode);
                                    ->addAttributeToFilter('lookin_code', $dataItcode)
									->addAttributeToFilter('dot', $dotOptionValue);
                        
                        $product = $_product->getData();
                        
                        /* echo "<pre>";
                        print_r($product);
                        exit; */

                        if(!empty($product) || $product != null){
							
                            $productId = $product[0]['entity_id'];                            
							//$oldPrice = $productPrice->getPrice();
                            if($productId != null && $productId != ""){
                                $productObject = $_productloader->create()->load($productId);
								$specialPrice = $productObject->getSpecialPrice();
								$specialPriceFromDate = $productObject->getSpecialFromDate(); //2020-10-22 00:00:00
								$specialPriceToDate = $productObject->getSpecialToDate();
								
								//Update Stock Item Table
                                $isInStock = 0;
                                
                                if($dataQty > 0) {
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
								
                               // $updatePriceindexsql = "UPDATE ".$catalogPriceIndexTable. " SET price = ".$fittPrice." , min_price = ".$fittPrice.", max_price = ".$fittPrice.", final_price = ".$fittPrice." where entity_id = ".$productId."";
								
                               // $updatePricedecimalsql = "UPDATE ".$catalogPriceDecimalTable. " SET value = ".$fittPrice." where entity_id = ".$productId."";
								
								$updateLookinCostsql = "UPDATE ".$catalogProductEntityVarcharTable. " SET value = ".$lookinCOst." WHERE attribute_id = ".$lookinCostAttrId." AND entity_id = ".$productId."";
								
								$updateLookinQtysql = "UPDATE ".$catalogProductEntityVarcharTable. " SET value = ".$lookinQty." WHERE attribute_id = ".$lookinQtyAttrId." AND entity_id = ".$productId."";
								
                                $connection->query($updateStocksql);
								$connection->query($updateLookinCostsql);
                                $connection->query($updateLookinQtysql);
                              //  $connection->query($updatePriceindexsql);
                               // $connection->query($updatePricedecimalsql);
                                echo "Stock Update for Product Sku ".$productId."-".$dataItcode."-".$dataYear." </br>";
                                $successcount++;
                                //$responseRowData[] = array($dataItcode,$dataDescription,$dataYear,$oldQtyArr[$productId],$dataQty,$dataQty,$oldPrice,$fittPrice,$fittPrice,'Success','Updated');
                                $responseRowData[] = array($dataItcode,$dataDescription,$dataYear,$oldQtyArr[$productId],$dataQty,$dataQty,'Success','Updated');
                            }
                        }else{
                            echo "Failed Stock Update for Product Sku ".$dataItcode."-".$dataYear." </br>";
                            $failedcount++;
							//$responseRowData[] = array($dataItcode,$dataDescription,$dataYear,'N/A','N/A',$dataQty,'N/A','N/A',$fittPrice,'Failed','Product Not Found');
							$responseRowData[] = array($dataItcode,$dataDescription,$dataYear,'N/A','N/A',$dataQty,'Failed','Product Not Found');
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
                        echo "Could not connect to server. Mail sent successfully.";
                    }else {
                        echo "Could not connect to server. Mail could not be sent.";
                    }
                }

                $rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
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
                        echo "Inventory updated. Mail sent successfully!";
                    } else {
                        echo "Inventory updated. Mail could not be sent!";
                    }
                }
            }            
            
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

    public function getIpAddress(){       
        $remoteAddress = $this->_objectManager->create('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');
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

            $rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
            $rfc->setData('rfc_manual_path', "Lookin_Stock_Update_".$this->getTodaysDate().".csv");
            $rfc->save();
        }
    }

    public function updateStockQtyBefore($response,$yearIdOptionSqlResult,$eavAttributeOptionValueTable,$connection,$cataloginventoryStockItemTable,$storeScope){
        if (count($response) > 0) {
            $oldQtyArray = array();
			
			$productFactory = $this->_objectManager->get('Magento\Catalog\Model\ResourceModel\ProductFactory');
			$poductReource = $productFactory->create();
			$attributeCode = 'dot';
			$dotOptionValue = '';
			$attribute = $poductReource->getAttribute($attributeCode);
			
            foreach ($response as $data) {

                $dataItcode = trim($data['ITCODE']);
               // $dataYear = trim($data['ITMODEL']);
			    $dataYear = trim($data['ITSCAT']);
                $dataQty = trim($data['STOCK']);
                
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
                            // ->addWebsiteFilter(2)
                            ->addAttributeToFilter('lookin_code', $dataItcode)
							->addAttributeToFilter('dot', $dotOptionValue);
                
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