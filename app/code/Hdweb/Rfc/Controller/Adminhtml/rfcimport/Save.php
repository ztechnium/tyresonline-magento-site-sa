<?php
namespace Hdweb\Rfc\Controller\Adminhtml\rfcimport;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;


class Save extends \Magento\Backend\App\Action
{
	protected $resultPageFactory;
    protected $_logger;
    protected $scopeConfig;
    protected $_timezoneInterface;
    protected $_resouceConnection;
    protected $rfcCollection;
    protected $_filesystem;
    protected $_storeManager;
    protected $_messageManager;
	
    /**
     * @param Action\Context $context
     */
    public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\App\ResourceConnection $resouceConnection,
        \Hdweb\Rfc\Model\ResourceModel\Rfc\Collection $rfcCollection,
        \Magento\Framework\Filesystem $_filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager
	)
    {	
		$this->resultPageFactory  = $resultPageFactory;
        $this->_logger            = $logger;
        $this->scopeConfig        = $scopeConfig;
        $this->_timezoneInterface = $timezoneInterface;
        $this->_resouceConnection = $resouceConnection;
        $this->rfcCollection      = $rfcCollection;
        $this->_filesystem        = $_filesystem;
        $this->_storeManager      = $storeManager;
        $this->_messageManager    = $messageManager;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
		$resultRedirect = $this->resultRedirectFactory->create();
        $storeScope     = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$rfcEnable = 1;
        $data = $this->getRequest()->getPostValue();
		$supplierData = explode("-code-", $data['supplier_code']);
		
		$supplierCode = $supplierData[0];
		$prodattrCode = $supplierData[1];

		$csvFile = $_FILES['csv_upload']['tmp_name'];
        if ($supplierCode != '' && $csvFile != '' && $prodattrCode != '') {
			$this->importRfc($supplierCode, $csvFile, $prodattrCode);
			
			return $resultRedirect->setPath('*/*/edit');		
            
        } else {
            $this->_messageManager->addError('Something went wrong with RFC import');
			return $resultRedirect->setPath('*/*/edit');
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        //$resultRedirect = $this->resultRedirectFactory->create();
        
        //return $resultRedirect->setPath('*/*/');
    }
	
	public function getWebproductdata($itemCode, $itemYear, $prodattrCode)
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

        $gcc_codeIdSql    = "SELECT attribute_id FROM " . $eavAttributeTable . " WHERE attribute_code = '" . $prodattrCode . "' AND entity_type_id = " . $catalogEntityId . " LIMIT 1";
        $gcc_codeIdResult = $connection->fetchCol($gcc_codeIdSql);
        $gcc_codeId       = $gcc_codeIdResult[0];
		
        //Optained Product From Options option id
        $yearIdOptionSql       = "SELECT option_id FROM " . $eavAttributeOptionTable . " WHERE attribute_id = " . $yearId;
        $yearIdOptionSqlResult = $connection->fetchCol($yearIdOptionSql);
        //End of Optained Product From attribute id  ----- PRODUCT FROM

        $yearOptionValueId = '';
		
        // echo $yearId . "----" . $itemYear;
        foreach ($yearIdOptionSqlResult as $yearId) {
            $yearIdOptionValueSql       = "SELECT option_id FROM " . $eavAttributeOptionValueTable . " WHERE option_id = " . $yearId . " AND value = '" . $itemYear . "' LIMIT 1";
            $yearIdOptionValueSqlResult = $connection->fetchCol($yearIdOptionValueSql);
            if (!empty($yearIdOptionValueSqlResult)) {
                $yearOptionValueId = $yearIdOptionValueSqlResult[0];
                continue;
            }
        }

        $gccattribute_id  = $gcc_codeId;
        $yearattribute_id = $yearattrId;
		$at_attr_code_default = 'at_'.$prodattrCode.'_default';

        $query = "SELECT e.*," . $at_attr_code_default. ".value as 'gccattr',at_dot.value as 'dotattr' FROM catalog_product_entity AS e INNER JOIN catalog_product_entity_varchar AS " . $at_attr_code_default. " ON " . $at_attr_code_default. ".entity_id = e.entity_id
            AND " . $at_attr_code_default. ".attribute_id =" . $gccattribute_id . " INNER JOIN catalog_product_entity_int AS at_dot ON at_dot.entity_id= e.entity_id AND at_dot.attribute_id = " . $yearattribute_id . " WHERE " . $at_attr_code_default. ".value = '" . $itemCode . "' AND  at_dot.value = '" . $yearOptionValueId . "' ";
		
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
	
	public function getIpAddress()
    {
        $remoteAddress = $this->_objectManager->create('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');
        return $remoteAddress->getRemoteAddress();
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
	
	public function _createcsvfile($responseRow, $rfcid, $supplierCode)
    {
        if (count($responseRow) > 0) {

            $csvMediapath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'rfcfiles/';
            if (!is_dir($csvMediapath)) {
                $csvMediapath = mkdir($csvMediapath);
            }

            $outputFile = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'rfcfiles/' . $supplierCode."_ERP_To_Supplier_" . $this->getTodaysDate() . ".csv";

            $handle = fopen($outputFile, 'w');
            foreach ($responseRow as $response) {
                fputcsv($handle, $response);
            }

            $rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
            $rfc->setData('rfc_manual_path', $supplierCode."_ERP_To_Supplier_" . $this->getTodaysDate() . ".csv");
            $rfc->save();
        }
    }
	
	public function importRfc($supplierCode, $csvFile, $prodattrCode){
		$resultRedirect = $this->resultRedirectFactory->create();
        $storeScope     = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$rfcEnable = 1;
		$csv = $this->_objectManager->get('Magento\Framework\File\Csv');
		$csvDataRecords = $csv->getData($csvFile);
		$ipaddress = $this->getIpAddress();
		$method = 'Manually';
		$totalcount   = 0;
		$successcount = 0;
		$failedcount  = 0;

		$rfc          = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc');
		$rfc->setData('rfc_name', $supplierCode.' ERP To Supplier');
		/* $rfc->setData('rfc_url', $rfcUrl);
		$rfc->setData('rfc_username', $rfcUsername);
		$rfc->setData('rfc_password', $rfcPassword); */
		$rfc->setData('rfc_datetime', $this->getTodaysDate());
		$rfc->setData('rfc_enable', $rfcEnable);
		$rfc->setData('rfc_status', 'Running');
		$rfc->setData('rfc_run_method', $method);
		$rfc->setData('rfc_ip_address', $ipaddress);
		$rfc->save();
		$rfcid = $rfc->getRfcId();

		$connection       = $this->_resouceConnection->getConnection();
		$rfcUrl           = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_url', $storeScope);
		$rfcUsername      = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_username', $storeScope);
		$rfcPassword      = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_password', $storeScope);
		$rfcFunction      = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_function', $storeScope);
		$rfcEnableEmail   = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_enable_email', $storeScope);
		$rfcEmailids      = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_emailids', $storeScope);
		$rfcSupplierTable = $this->_resouceConnection->getTableName('rfc_supplierproducts');

		$responseRowData   = array();
		$responseRowData[] = array('Supplier Code', 'Item Code', 'Item Desc', 'Item Brand', 'Item Size', 'RunFlat', 'Item Year', 'Item Qty', 'Item Price', 'Item Price2', 'Sell Price', 'Item Origin', 'Item Load', 'Type', 'Product ID', 'Product SKU', 'Product Qty', 'Product Price', 'Product Offer', 'Product Status', 'Status');
		//$i = 1; 
		unset($csvDataRecords[0]); //removes the header from csv file
		if(count($csvDataRecords) > 0){
			/* Start Delete records from RFC Supplier Products Table */
			$sql = "DELETE FROM " . $rfcSupplierTable . " WHERE supplier_code = '" . $supplierCode . "'";
			$connection->query($sql);
			/* End Delete records from RFC Supplier Products Table */
			foreach($csvDataRecords as $csvData){
						$dataItcode       = trim($csvData[0]);
						$dataDescription  = trim($csvData[1]);
						$brand            = trim($csvData[2]);
						$lowercaseBrand   = strtolower($brand);
						$dataBrand        = ucwords($lowercaseBrand);
						$itemSize         = trim($csvData[3]);
						$hasRunFlat       = trim($csvData[4]);
						$dataYear         = trim($csvData[5]);
						$dataQty          = trim($csvData[6]);
						$itemPrice        = trim($csvData[7]);
						$itemPrice1       = trim($csvData[8]);
						$sellPrice        = trim($csvData[9]);
						$itemLoad         = trim($csvData[10]);
						$origin           = trim($csvData[11]);
						$lowercaseOrigin  = strtolower($origin);
						$itemOrigin       = ucwords($lowercaseOrigin);
						$type             = trim($csvData[12]);
						
						$itemUpdatedDate  = '';//trim($csvData[$i]['UPDATEDON']);
						$itemWriteDate    = trim($csvData[13]);
						$itemExecutedDate = date('Y-m-d H:i:s');
						
						$webProductSku = '';
						$webProductId = '';
						$webProductStatus = '';
						$webProductOffer = "NULL";
						$webProductQty = 0;
						$webProductPrice = 0;

						$productData = $this->getWebproductdata($dataItcode, $dataYear, $prodattrCode);
						
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
							
							
							/* if (strpos($dataDescription, 'ROF') !== false) {
								$runFlat = 'Yes';
							}
							if (strpos($dataDescription, 'RFT') !== false) {
								$runFlat = 'Yes';
							}
							if ($runFlat != '') {
								$hasRunFlat = $runFlat;
							} */
						
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
					$this->_createcsvfile($responseRowData, $rfcid, $supplierCode);
				}
				$rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
				$rfc->setData('rfc_datetime', $this->getTodaysDate());
				$rfc->setData('rfc_status', 'Success');
				$rfc->setData('rfc_total_record', $totalcount);
				$rfc->setData('rfc_total_sucess', $successcount);
				$rfc->setData('rfc_total_fail', $failedcount);
				$rfc->save();
				$this->_messageManager->addSuccess('RFC successfuly executed');
		}else{
			$this->_messageManager->addError('There are no records in csv file');
		}
		
	}
}