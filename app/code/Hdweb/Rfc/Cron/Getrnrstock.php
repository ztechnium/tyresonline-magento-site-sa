<?php

namespace Hdweb\Rfc\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;

class Getrnrstock
{
	protected $_logger;
	protected $scopeConfig;
	protected $_timezoneInterface;
	protected $_objectManager;
	protected $_resouceConnection;
	protected $rfcCollection;
    protected $_filesystem;
    protected $_storeManager;

	public function __construct(
		\Psr\Log\LoggerInterface $logger,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
		\Magento\Framework\App\ResourceConnection $resouceConnection,
		\Hdweb\Rfc\Model\ResourceModel\Rfc\Collection $rfcCollection,
        \Magento\Framework\Filesystem $_filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager
	){
		$this->_logger = $logger;
		$this->_objectManager = $objectManager;
		$this->scopeConfig = $scopeConfig;
		$this->_timezoneInterface = $timezoneInterface;
		$this->_resouceConnection = $resouceConnection;
		$this->rfcCollection      = $rfcCollection;
        $this->_filesystem = $_filesystem;
        $this->_storeManager = $storeManager;
	}

	public function execute(){
		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$companyId   = 'M001';
        $apiUsername = 'Devendra';
        $apiPassword = 'welcome123';

        $rfcEnable      = 1;
        $ipaddress      = $this->getIpAddress();
        $method         = 'Auto';
        $totalcount     = 0;
        $successcount   = 0;
        $failedcount    = 0;

        $sessionKey = $this->_objectManager->create('Hdweb\Rfc\Helper\Data')->getRnrLoginSessionKey($companyId, $apiUsername, $apiPassword);
		if ($sessionKey != '') {
            // API URL to send data
            $hosturl = "http://157.175.109.168/ABInternational_FZC/Live/";
            $rfcUrl  = $hosturl . 'Api/TransactionInt/GetItemStockData?CompanyCode=M001&ItemCode=&Batch=';

            // curl initiate
            $ch      = curl_init();
            $headers = array('Content-Type: application/json', 'SessionKey:' . $sessionKey . '');
            curl_setopt($ch, CURLOPT_URL, $rfcUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc');
            $rfc->setData('rfc_name', 'RNR - Product Stock Update');
            $rfc->setData('rfc_url', $rfcUrl);
            $rfc->setData('rfc_username', json_encode($headers));
            //$rfc->setData('rfc_password', $rfcPassword);
            $rfc->setData('rfc_datetime', $this->getTodaysDate());
            $rfc->setData('rfc_enable', $rfcEnable);
            //$rfc->setData('rfc_status', 'Running');
            $rfc->setData('rfc_run_method', $method);
            $rfc->setData('rfc_ip_address', $ipaddress);
            $rfc->save();
            $rfcid = $rfc->getRfcId();
			
            // Execute curl and assign returned data
            $result   = curl_exec($ch);
            $response = json_decode($result, true);
            /* echo "<pre>";
            print_r($response);
            die; */
            // Close curl
            curl_close($ch);

            $rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
            $rfc->setData('rfc_response_datetime', $this->getTodaysDate());
            $rfc->setData('rfc_password', $result);
            $rfc->save();
            $rfcid         = $rfc->getRfcId();
            $itemStockData = $response['Table1'];
            $stockArray    = array();
            foreach ($itemStockData as $key => $stockApiData) {
                if (!empty($stockApiData['BatchName'])) {
                    $stockArray[] = $stockApiData;
                } else {
                    continue;
                }

            }
            $connection       = $this->_resouceConnection->getConnection();
            $rfcSupplierTable = $this->_resouceConnection->getTableName('rfc_supplierproducts');
           
            if (count($stockArray) > 0) {
                /* Start Delete records from RFC Supplier Products Table */
                $sql = "DELETE FROM " . $rfcSupplierTable . " WHERE supplier_code = 'STG'";
                $connection->query($sql);
                /* End Delete records from RFC Supplier Products Table */
                $responseRowData   = array();
                $responseRowData[] = array('Supplier Code', 'Item Code', 'Item Desc', 'Item Brand', 'Item Year', 'Item Qty', 'Type', 'Product ID', 'Product SKU', 'Product Qty', 'Product Price', 'Product Offer', 'Product Status', 'Status');
                foreach ($stockArray as $stockData) {
                    $supplierCode     = 'STG';
                    $itemSize         = '';
                    $hasRunFlat       = '';
                    $itemPrice        = '';
                    $itemPrice1       = '';
                    $sellPrice        = '';
                    $itemOffer        = '';
                    $itemOrigin       = '';
                    $itemLoad         = '';
                    $type             = 'Supplier';
                    $itemUpdatedDate  = '';
                    $itemWriteDate    = '';
                    $dataBrand        = '';
                    $webProductSku    = '';
                    $webProductId     = '';
                    $webProductStatus = '';
                    $webProductOffer  = "NULL";
                    $webProductQty    = 0;
                    $webProductPrice  = 0;
                    $code             = trim($stockData['ItemCode']);//trim(str_replace('*', '', $stockData['ItemCode']));
                    $year             = trim($stockData['BatchName']);
                    $itemCode         = $code ."-".$year;
                    $prodattrCode     = 'stg_code';
                    $dataDescription  = $stockData['ItemName'];
                    if (!empty($stockData['Brand'])) {
                        $dataBrand = $stockData['Brand'];
                    }
                    if (!empty($stockData['ItemSize'])) {
                        $itemSize = $stockData['ItemSize'];
                    }
                    if (!empty($stockData['ItemRunflat'])) {
                        $hasRunFlat = $stockData['ItemRunflat'];
                    }
                    if (!empty($stockData['ItemLoad'])) {
                        $itemLoad = $stockData['ItemLoad'];
                    }
                    $itemExecutedDate = date('Y-m-d H:i:s');

                    $dataQty = $stockData['AvailableQty'];

                    $productData = $this->getWebproductdata($itemCode, $year, $prodattrCode);

                    if (!empty($productData)) {

                        $webProductSku    = $productData['sku'];
                        $webProductId     = $productData['entity_id'];
                        $webProductPrice  = $productData['price'];
                        $webProductQty    = $productData['qty'];
                        $webProductStatus = $productData['status'];
                        $webProductOffer  = $productData['offer'];

                        if ($productData['offer'] != '') {
                            $webProductOffer = $productData['offer'];
                        } else {
                            $webProductOffer = "NULL";
                        }

                        if ($productData['qty'] != '') {
                            $webProductQty = $productData['qty'];
                        } else {
                            $webProductQty = 0;
                        }

                        if ($productData['price'] != '') {
                            $webProductPrice = $productData['price'];
                        } else {
                            $webProductPrice = 0;
                        }
                        $successcount++;

                        $responseRowData[] = array($supplierCode, $itemCode, $dataDescription, $dataBrand, $year, $dataQty, $type, $webProductId, $webProductSku, $webProductQty, $webProductPrice, $webProductOffer, $webProductStatus, 'Success');
                    } else {
                        $failedcount++;
                        $responseRowData[] = array($supplierCode, $itemCode, $dataDescription, $dataBrand, $year, $dataQty, $type, 'Product Not Found', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'Failed');
                    }
                    //echo $webProductId.'<br/>';

                    $model = $this->_objectManager->create('Hdweb\Rfc\Model\Supplierproducts');
                    $model->setData('supplier_code', $supplierCode);
                    $model->setData('item_code', $itemCode);
                    $model->setData('item_desc', $dataDescription);
                    $model->setData('item_brand', $dataBrand);
                    $model->setData('item_size', $itemSize);
                    $model->setData('item_runflat', $hasRunFlat);
                    $model->setData('item_year', $year);
                    $model->setData('item_qty', $dataQty);
                    $model->setData('item_price', $itemPrice);
                    $model->setData('item_price2', $itemPrice1);
                    $model->setData('item_sell_price', $sellPrice);
                    $model->setData('item_offer', $itemOffer);
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
                //$rfc->setData('rfc_datetime', $this->getTodaysDate());
                $rfc->setData('rfc_status', 'Success');
                $rfc->setData('rfc_total_record', $totalcount);
                $rfc->setData('rfc_total_sucess', $successcount);
                $rfc->setData('rfc_total_fail', $failedcount);
                $rfc->save();
                $this->_logger->info('RNR Stock successfuly executed');
            } else {
                $this->_logger->info('Something went wrong with the RNR Stock data');
            }
        } else {
			$this->_logger->info('Session Key is not generated!.');
        }
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
            $yearIdOptionValueSql       = "SELECT option_id FROM " . $eavAttributeOptionValueTable . " WHERE option_id = " . $yearId . " AND value = " . $itemYear . " LIMIT 1";
            $yearIdOptionValueSqlResult = $connection->fetchCol($yearIdOptionValueSql);
            if (!empty($yearIdOptionValueSqlResult)) {
                $yearOptionValueId = $yearIdOptionValueSqlResult[0];
                continue;
            }
        }

        $gccattribute_id  = $gcc_codeId;
        $yearattribute_id = $yearattrId;

        $at_attr_code_default = 'at_' . $prodattrCode . '_default';

        /* $query = "SELECT e.*,at_gcc_code_default.value as 'gccattr',at_dot.value as 'dotattr' FROM catalog_product_entity AS e INNER JOIN catalog_product_entity_varchar AS at_gcc_code_default ON at_gcc_code_default.entity_id = e.entity_id
        AND at_gcc_code_default.attribute_id =" . $gccattribute_id . " INNER JOIN catalog_product_entity_int AS at_dot ON at_dot.entity_id= e.entity_id AND at_dot.attribute_id = " . $yearattribute_id . " WHERE at_gcc_code_default.value = '" . $itemCode . "' AND  at_dot.value = " . $yearOptionValueId . " "; */

        $query = "SELECT e.*," . $at_attr_code_default . ".value as 'gccattr',at_dot.value as 'dotattr' FROM catalog_product_entity AS e INNER JOIN catalog_product_entity_varchar AS " . $at_attr_code_default . " ON " . $at_attr_code_default . ".entity_id = e.entity_id AND " . $at_attr_code_default . ".attribute_id =" . $gccattribute_id . " INNER JOIN catalog_product_entity_int AS at_dot ON at_dot.entity_id= e.entity_id AND at_dot.attribute_id = " . $yearattribute_id . " WHERE " . $at_attr_code_default . ".value = '" . $itemCode . "' AND  at_dot.value = '" . $yearOptionValueId . "'";

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
	
	public function getIpAddress(){       
        $remoteAddress = $this->_objectManager->create('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');
        return $remoteAddress->getRemoteAddress();
    }
	
	public function _createcsvfile($responseRow, $rfcid)
    {
        if (count($responseRow) > 0) {

            $csvMediapath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'rfcfiles/';
            if (!is_dir($csvMediapath)) {
                $csvMediapath = mkdir($csvMediapath);
            }

            $outputFile = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'rfcfiles/' . "RNR_Product_Stock_Update_" . $this->getTodaysDate() . ".csv";

            $handle = fopen($outputFile, 'w');
            foreach ($responseRow as $response) {
                fputcsv($handle, $response);
            }

            $rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
            $rfc->setData('rfc_manual_path', "RNR_Product_Stock_Update_" . $this->getTodaysDate() . ".csv");
            $rfc->save();
        }
    }
}