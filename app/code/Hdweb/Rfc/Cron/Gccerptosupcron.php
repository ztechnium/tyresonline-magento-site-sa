<?php

namespace Hdweb\Rfc\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;

class Gccerptosupcron
{
	protected $_logger;
	protected $scopeConfig;
	protected $_timezoneInterface;
	protected $_objectManager;
	protected $_resouceConnection;
    protected $_filesystem;
    protected $_storeManager;

	public function __construct(
		\Psr\Log\LoggerInterface $logger,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
		\Magento\Framework\App\ResourceConnection $resouceConnection,
        \Magento\Framework\Filesystem $_filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager
	){
		$this->_logger = $logger;
		$this->_objectManager = $objectManager;
		$this->scopeConfig = $scopeConfig;
		$this->_timezoneInterface = $timezoneInterface;
		$this->_resouceConnection = $resouceConnection;
        $this->_filesystem = $_filesystem;
        $this->_storeManager = $storeManager;
	}

	public function execute(){
		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $rfcEnable = 1;//$this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_enable', $storeScope);

		if($rfcEnable == 1){
			
			$connection = $this->_resouceConnection->getConnection();
			$rfcUrl = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_url', $storeScope);
			$rfcUsername = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_username', $storeScope);
			$rfcPassword = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_password', $storeScope);
			$rfcFunction = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_function', $storeScope);
			$rfcEnableEmail = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_enable_email', $storeScope);
			$rfcEmailids = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_emailids', $storeScope);
			$rfcSupplierTable = $this->_resouceConnection->getTableName('rfc_supplierproducts');
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
			
			$method = 'Auto';
			$totalcount   = 0;
			$successcount = 0;
			$failedcount  = 0;
			
			$rfc          = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc');
			$rfc->setData('rfc_name', 'GCC ERP To Supplier');
			/* $rfc->setData('rfc_url', $rfcUrl);
			$rfc->setData('rfc_username', $rfcUsername);
			$rfc->setData('rfc_password', $rfcPassword); */
			$rfc->setData('rfc_datetime', $this->getTodaysDate());
			$rfc->setData('rfc_enable', $rfcEnable);
			$rfc->setData('rfc_status', 'Running');
			$rfc->setData('rfc_run_method', $method);
			$rfc->save();
			$rfcid = $rfc->getRfcId();
			
            $expectedCount = 2000; // Need to be in store rfc configuration
            $query = "SELECT * FROM [gcccoastmsdb].[dbo].[WITMAST] WHERE [STOCK] > 0 order by WTRFDATE DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            //$result = $stmt->fetchAll();
            $response = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            // echo "<pre>";
            // print_r($result);
            
            if(count($response) > 0){
				$currentDate = date("Y-m-d H:i:s");
				$actualDate = $response[0]['WTRFDATE'];
				$actualCount = count($response);
				$subject = '';
				$dateDifference = $this->getDateDifference($actualDate, $currentDate);
				if($expectedCount > $actualCount){
					//email error shoot
					$supplierCode     = 'Gulfcost';
					$subject = 'Alert '.$supplierCode.' RFC Notification';
					$this->sendEmailNotification($subject, $expectedCount, $actualCount, $dateDifference, $currentDate, $actualDate);
					$rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
					$rfc->setData('rfc_datetime', $this->getTodaysDate());
					$rfc->setData('rfc_status', 'Failed');
					$rfc->setData('rfc_total_record', $totalcount);
					$rfc->setData('rfc_total_sucess', $successcount);
					$rfc->setData('rfc_total_fail', $failedcount);
					$rfc->save();
				}else{
					//email success shoot
					$supplierCode    = 'Gulfcost';
					$subject = 'Success '.$supplierCode.' RFC Notification';
					$responseRowData   = array();
					$responseRowData[] = array('Supplier Code', 'Item Code', 'Item Desc', 'Item Brand', 'Item Size', 'RunFlat', 'Item Year', 'Item Qty', 'Item Price', 'Item Price2', 'Sell Price', 'Item Origin', 'Item Load', 'Type', 'Product ID', 'Product SKU', 'Product Qty', 'Product Price', 'Product Offer', 'Product Status', 'Status');
					/* Start Delete records from RFC Supplier Products Table */
					$sql = "DELETE FROM " . $rfcSupplierTable . " WHERE supplier_code = 'Gulfcost'";
					$connection->query($sql);
					/* End Delete records from RFC Supplier Products Table */
					foreach ($response as $data) {
						$type             = 'Supplier';
						$dataItcode       = trim($data['ITCODE']);
						$dataDescription  = trim($data['ITDESC']);
						$brand            = trim($data['BRANDNAME']);
						$lowercaseBrand   = strtolower($brand);
						$dataBrand        = ucwords($lowercaseBrand);
						$itemSize         = trim($data['ITCATE']);
						$dataYear         = trim($data['ITMODEL']);
						$dataQty          = trim($data['STOCK']);
						$itemPrice        = trim($data['SPRICE']);
						$itemPrice1       = trim($data['SPRICE1']);
						$sellPrice        = trim($data['FITTPRICE']);
						$origin           = trim($data['ORIGINNAME']);
						$lowercaseOrigin  = strtolower($origin);
						$itemOrigin       = ucwords($lowercaseOrigin);
						$itemLoad         = trim($data['LOADINDEX']);
						$itemUpdatedDate  = trim($data['UPDATEDON']);
						$itemWriteDate    = trim($data['WTRFDATE']);
						$itemExecutedDate = date('Y-m-d H:i:s');
						
						$webProductSku = '';
						$webProductId = '';
						$webProductStatus = '';
						$webProductOffer = "NULL";
						$webProductQty = 0;
						$webProductPrice = 0;
						$runFlat    = '';
						$hasRunFlat = '';
						$productData = $this->getWebproductdata($dataItcode, $dataYear);
						
						if(!empty($productData)) {
						
							$webProductSku = $productData['sku'];
							$webProductId = $productData['entity_id'];
							$webProductPrice = $productData['price'];
							$webProductQty = $productData['qty'];
							$webProductStatus = $productData['status'];
							$webProductOffer = $productData['offer'];
							
							if($productData['offer'] !=''){
								$webProductOffer = $productData['offer'];						
							}else{
								$webProductOffer = "NULL";				
							}

							if($productData['qty'] !=''){
								$webProductQty = $productData['qty'];                       
							}else{
								$webProductQty = 0;              
							}

							if($productData['price'] !=''){
								$webProductPrice = $productData['price'];                       
							}else{
								$webProductPrice = 0;              
							}
							
							
							if (strpos($dataDescription, 'ROF') !== false) {
								$runFlat = 'Yes';
							}
							if (strpos($dataDescription, 'RFT') !== false) {
								$runFlat = 'Yes';
							}
							if ($runFlat != '') {
								$hasRunFlat = $runFlat;
							}
							$successcount++;
							$responseRowData[] = array($supplierCode, $dataItcode, $dataDescription, $dataBrand, $itemSize, $hasRunFlat, $dataYear, $dataQty, $itemPrice, $itemPrice1, $sellPrice, $itemOrigin, $itemLoad, $type, $webProductId, $webProductSku, $webProductQty, $webProductPrice, $webProductOffer, $webProductStatus, 'Success');
						}else{
							$failedcount++;
							$responseRowData[] = array($supplierCode, $dataItcode, $dataDescription, $dataBrand, $itemSize, $hasRunFlat, $dataYear, $dataQty, $itemPrice, $itemPrice1, $sellPrice, $itemOrigin, $itemLoad, $type, 'Product Not Found', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'Failed');
						}
						
						$model = $this->_objectManager->create('Hdweb\Rfc\Model\Supplierproducts');
						$model->setData('supplier_code', $supplierCode);
						$model->setData('item_code', $dataItcode);
						$model->setData('item_desc', $dataDescription);
						$model->setData('item_brand', $dataBrand);
						$model->setData('item_size', $itemSize);
						$model->setData('item_runflat', $hasRunFlat);
						$model->setData('item_year', $dataYear);
						$model->setData('item_qty', $dataQty);
						$model->setData('item_price', $itemPrice);
						$model->setData('item_price2', $itemPrice1);
						$model->setData('item_sell_price', $sellPrice);
						//$model->setData('item_offer', '');
						$model->setData('item_origin', $itemOrigin);
						$model->setData('item_load', $itemLoad);
						$model->setData('type', $type);
						$model->setData('web_product_id', $webProductId);
						$model->setData('web_product_sku', $webProductSku);
						$model->setData('web_product_qty', $webProductQty);
						$model->setData('web_product_price', $webProductPrice);
						$model->setData('web_product_offer', $webProductOffer);
						$model->setData('web_product_status', $webProductStatus);
						$model->setData('item_updated_date', $itemUpdatedDate);
						$model->setData('item_write_date', $itemWriteDate);
						$model->setData('item_executed_date', $itemExecutedDate);
						$model->save();
						$totalcount++;
						
					}
					
					if (count($responseRowData) > 1) {
						$this->_createcsvfile($responseRowData, $rfcid);
					}
					
					$rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
					$rfc->setData('rfc_datetime', $this->getTodaysDate());
					$rfc->setData('rfc_status', 'Success');
					$rfc->setData('rfc_total_record', $totalcount);
					$rfc->setData('rfc_total_sucess', $successcount);
					$rfc->setData('rfc_total_fail', $failedcount);
					$rfc->save();
					
					// RFC email notification
				   // $this->sendEmailNotification($subject, $expectedCount, $actualCount, $dateDifference, $currentDate, $actualDate);
				}	
				
			}

		} else {
            $this->_logger->info('RFC Settings are disabled.');
            //echo "RFC Settings are disabled."; //die();
        }
	}
	
	public function getWebproductdata($itemCode, $itemYear)
    {

        $connection                   = $this->_resouceConnection->getConnection();
        $eavEntityTypeTable           = $this->_resouceConnection->getTableName('eav_entity_type');
        $eavAttributeTable            = $this->_resouceConnection->getTableName('eav_attribute');
        $eavAttributeOptionTable      = $this->_resouceConnection->getTableName('eav_attribute_option');
        $eavAttributeOptionValueTable = $this->_resouceConnection->getTableName('eav_attribute_option_value');
        //Optained Catalog Entity Id
        $catalogEntitySql = "SELECT entity_type_id FROM " . $eavEntityTypeTable . " WHERE entity_type_code = 'catalog_product' LIMIT 1";
        $resultCatalog    = $connection->fetchCol($catalogEntitySql);
        $catalogEntityId  = $resultCatalog[0];

        //Optained Product From attribute id  ----- PRODUCT YEAR
        $yearIdSql    = "SELECT attribute_id FROM " . $eavAttributeTable . " WHERE attribute_code = 'dot' AND entity_type_id = " . $catalogEntityId . " LIMIT 1";
        $yearIdResult = $connection->fetchCol($yearIdSql);
        $yearId       = $yearIdResult[0];
        $yearattrId   = $yearIdResult[0];

        $gcc_codeIdSql    = "SELECT attribute_id FROM " . $eavAttributeTable . " WHERE attribute_code = 'gcc_code' AND entity_type_id = " . $catalogEntityId . " LIMIT 1";
        $gcc_codeIdResult = $connection->fetchCol($gcc_codeIdSql);
        $gcc_codeId       = $gcc_codeIdResult[0];

        //Optained Product From Options option id
        $yearIdOptionSql       = "SELECT option_id FROM " . $eavAttributeOptionTable . " WHERE attribute_id = " . $yearId;
        $yearIdOptionSqlResult = $connection->fetchCol($yearIdOptionSql);
        //End of Optained Product From attribute id  ----- PRODUCT FROM

        $yearOptionValueId = '';
        // echo $yearId . "----" . $itemYear;
        foreach ($yearIdOptionSqlResult as $yearId) {
            $yearIdOptionValueSql       = "SELECT option_id FROM " . $eavAttributeOptionValueTable . " WHERE option_id = " . $yearId . " AND value = " . $itemYear . " LIMIT 1";
            $yearIdOptionValueSqlResult = $connection->fetchCol($yearIdOptionValueSql);
            if (!empty($yearIdOptionValueSqlResult)) {
                $yearOptionValueId = $yearIdOptionValueSqlResult[0];
                continue;
            }
        }

        $gccattribute_id  = $gcc_codeId;
        $yearattribute_id = $yearattrId;

        $query = "SELECT e.*,at_gcc_code_default.value as 'gccattr',at_dot.value as 'dotattr' FROM catalog_product_entity AS e INNER JOIN catalog_product_entity_varchar AS at_gcc_code_default ON at_gcc_code_default.entity_id = e.entity_id
            AND at_gcc_code_default.attribute_id =" . $gccattribute_id . " INNER JOIN catalog_product_entity_int AS at_dot ON at_dot.entity_id= e.entity_id AND at_dot.attribute_id = " . $yearattribute_id . " WHERE at_gcc_code_default.value = '" . $itemCode . "' AND  at_dot.value = '" . $yearOptionValueId . "' ";

        $result = $connection->fetchAll($query);
		
        $productData = array();
        if (count($result) > 0) {
            foreach ($result as $product) {

                $sku               = $product['sku'];
                $entity_id         = $product['entity_id'];
                $productRepository = $this->_objectManager->get('\Magento\Catalog\Model\ProductRepository');
                $product           = $productRepository->getById($entity_id);
                $productStockObj          = $this->_objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($product->getId());
                $productData['entity_id'] = $product->getId();
                $productData['sku']       = $product->getSku();
                $productData['price']     = $product->getPrice();
                $productData['offer']     = $product->getResource()->getAttribute('offers')->getFrontend()->getValue($product); //$product->getOffers();
                $productData['status']    = $product->getStatus();
                $productData['qty']       = $productStockObj->getQty();
            }
            return $productData;
        }

    }
	
	public function getTodaysDate()
    {
        $localeTimezone = $this->_timezoneInterface->getConfigTimezone('store', $this->getStore());
        date_default_timezone_set($localeTimezone);
        return $this->_timezoneInterface->date()->format('Y-m-d H:i:s');
    }
	
	public function getStore()
    {
        return $this->_storeManager->getStore();
    }
	
	public function _createcsvfile($responseRow, $rfcid)
    {
        if (count($responseRow) > 0) {

            $csvMediapath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'rfcfiles/';
            if (!is_dir($csvMediapath)) {
                $csvMediapath = mkdir($csvMediapath);
            }

            $outputFile = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'rfcfiles/' . "GCC_ERP_To_Supplier_" . $this->getTodaysDate() . ".csv";

            $handle = fopen($outputFile, 'w');
            foreach ($responseRow as $response) {
                fputcsv($handle, $response);
            }

            $rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
            $rfc->setData('rfc_manual_path', "GCC_ERP_To_Supplier_" . $this->getTodaysDate() . ".csv");
            $rfc->save();
        }
    }
	
	public function sendEmailNotification($subject, $expectedCount, $actualCount, $dateDifference, $currentDate, $actualDate)
	{
		
		$objectManager     = \Magento\Framework\App\ObjectManager::getInstance();
		$storeManager      = $objectManager->create('Magento\Store\Model\StoreManagerInterface');
		$storeScope        = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$_transportBuilder = $objectManager->create('Hdweb\Purchaseorder\Model\Mail\TransportBuilder');
        $inlineTranslation = $objectManager->create('Magento\Framework\Translate\Inline\StateInterface');
		$email = $this->scopeConfig->getValue('trans_email/ident_support/email', $storeScope);
        $name  = $this->scopeConfig->getValue('trans_email/ident_support/name', $storeScope);
		$from = array('email' => $email, 'name' => $name);
		$to = 'dhruv.hdit@gmail.com';
		$emailTemplateId  = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/rfc_email_notification_template', $storeScope);
		if($emailTemplateId != ''){
			$templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeManager->getStore()->getId());
			$rfcitemtable = '<table cellspacing="0" style="border: 2px dashed #FB4314; width: 100%;"> 
							<tr> 
								<th>Expected Records:</th><td>' . $expectedCount . '</td> 
							</tr> 
							<tr style="background-color: #e0e0e0;"> 
								<th>Actual Records:</th><td>' . $actualCount . '</td> 
							</tr>
							<tr> 
								<th>Hour Difference:</th><td>' . $dateDifference . '</td> 
							</tr> 
							<tr style="background-color: #e0e0e0;"> 
								<th>Executed Date/Time:</th><td>' . $currentDate . '</td> 
							</tr>						
							<tr> 
								<th>Actual Date/Time:</th><td>' . $actualDate . '</td> 
							</tr> 
						</table>';
						
			$templateVars = array('subject' => $subject, 'rfcitemtable' => $rfcitemtable);
			$transport = $_transportBuilder->setTemplateIdentifier($emailTemplateId)
				->setTemplateOptions($templateOptions)
				->setTemplateVars($templateVars)
				->setFrom($from)
				->addTo($to) // $vendor_email
				->getTransport();
			$transport->sendMessage();
			$inlineTranslation->resume();
		}
	}
	
	public function getDateDifference($actualDate, $currentDate){
		// Declare and define two dates 
		$date1 = strtotime($actualDate); 
		$date2 = strtotime($currentDate); 

		// Formulate the Difference between two dates 
		$diff = abs($date2 - $date1); 


		// To get the year divide the resultant date into 
		// total seconds in a year (365*60*60*24) 
		$years = floor($diff / (365*60*60*24)); 


		// To get the month, subtract it with years and 
		// divide the resultant date into 
		// total seconds in a month (30*60*60*24) 
		$months = floor(($diff - $years * 365*60*60*24) 
									/ (30*60*60*24)); 


		// To get the day, subtract it with years and 
		// months and divide the resultant date into 
		// total seconds in a days (60*60*24) 
		$days = floor(($diff - $years * 365*60*60*24 - 
					$months*30*60*60*24)/ (60*60*24)); 


		// To get the hour, subtract it with years, 
		// months & seconds and divide the resultant 
		// date into total seconds in a hours (60*60) 
		$hours = floor(($diff - $years * 365*60*60*24 
			- $months*30*60*60*24 - $days*60*60*24) 
										/ (60*60)); 


		// To get the minutes, subtract it with years, 
		// months, seconds and hours and divide the 
		// resultant date into total seconds i.e. 60 
		$minutes = floor(($diff - $years * 365*60*60*24 
				- $months*30*60*60*24 - $days*60*60*24 
								- $hours*60*60)/ 60); 


		// To get the minutes, subtract it with years, 
		// months, seconds, hours and minutes 
		$seconds = floor(($diff - $years * 365*60*60*24 
				- $months*30*60*60*24 - $days*60*60*24 
						- $hours*60*60 - $minutes*60)); 

		$difference = sprintf("%d years, %d months, %d days, %d hours, "
			. "%d minutes, %d seconds", $years, $months, 
					$days, $hours, $minutes, $seconds);
		return $difference;
		/* printf("%d years, %d months, %d days, %d hours, "
			. "%d minutes, %d seconds", $years, $months, 
					$days, $hours, $minutes, $seconds); */
		
	}
}