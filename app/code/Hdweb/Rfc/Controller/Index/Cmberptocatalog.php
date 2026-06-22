<?php

namespace Hdweb\Rfc\Controller\Index;

use Magento\Framework\App\Filesystem\DirectoryList;

class Cmberptocatalog extends \Magento\Framework\App\Action\Action
{

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
    ) {
        $this->resultPageFactory         = $resultPageFactory;
        $this->stockupdate               = $stockupdate;
        $this->_logger                   = $logger;
        $this->scopeConfig               = $scopeConfig;
        $this->_timezoneInterface        = $timezoneInterface;
        $this->_resouceConnection        = $resouceConnection;
        $this->rfcCollection             = $rfcCollection;
        $this->productCollectionFactory  = $productCollectionFactory;
        $this->_filesystem               = $_filesystem;
        $this->_storeManager             = $storeManager;
        $this->_indexerFactory           = $indexerFactory;
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

        $rfcEnable = 1;//$this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_enable', $storeScope);

        if ($rfcEnable == 1) {
			
			$rfcUrl         = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_url', $storeScope);
            $rfcUsername    = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_username', $storeScope);
            $rfcPassword    = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_password', $storeScope);
            $rfcFunction    = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_rfc_function', $storeScope);
            $rfcEnableEmail = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_enable_email', $storeScope);
            $rfcEmailids    = $this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_emailids', $storeScope);

            $parts = explode(",", $rfcEmailids);
            $to    = implode(', ', $parts);

            $ipaddress = $this->getIpAddress();
			
            $connection                       = $this->_resouceConnection->getConnection();
            $eavEntityTypeTable               = $this->_resouceConnection->getTableName('eav_entity_type');
            $eavAttributeTable                = $this->_resouceConnection->getTableName('eav_attribute');
            $eavAttributeOptionTable          = $this->_resouceConnection->getTableName('eav_attribute_option');
            $eavAttributeOptionValueTable     = $this->_resouceConnection->getTableName('eav_attribute_option_value');
            $catalogProductEntityVarcharTable = $this->_resouceConnection->getTableName('catalog_product_entity_varchar');
            $catalogProductEntityIntTable     = $this->_resouceConnection->getTableName('catalog_product_entity_int');
            $cataloginventoryStockItemTable   = $this->_resouceConnection->getTableName('cataloginventory_stock_item');
            $catalogPriceIndexTable           = $this->_resouceConnection->getTableName('catalog_product_index_price');
            $catalogPriceDecimalTable         = $this->_resouceConnection->getTableName('catalog_product_entity_decimal');
			$rfcSupplierTable = $this->_resouceConnection->getTableName('rfc_supplierproducts');
			$query = "SELECT web_product_sku, web_product_id ,sum(item_qty) as total_qty FROM " . $rfcSupplierTable . " WHERE supplier_code IN ('Gulfcost','Lookin') and web_product_sku != '' group by web_product_sku, web_product_id";
			$response = $connection->fetchAll($query);
			
			$manually = 1;
            if ($manually == 1) {
                $method = 'Manually';
            } else {
                $method = 'Auto';
            }
			
			$totalcount   = 0;
            $successcount = 0;
            $failedcount  = 0;
			
			$rfc          = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc');
            $rfc->setData('rfc_name', 'CMB ERP To Catalog');
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
			//echo '<pre>';print_r($response);die;
			if (count($response) > 0) {
				$responseRowData   = array();
				//$responseRowData[] = array('Sku', 'Item Description', 'Year', 'Old Qty', 'Qty', 'Vendor Qty', 'Old Price', 'New Price', 'Vendor Price', 'Status', 'Message', 'Price Status', 'Qty Status', 'Year Status');
				$responseRowData[] = array('Sku', 'Old Qty', 'New Qty', 'Status', 'Message', 'Qty Status');
				//Optained Catalog Entity Id
				$catalogEntitySql = "SELECT entity_type_id FROM " . $eavEntityTypeTable . " WHERE entity_type_code = 'catalog_product' LIMIT 1";
				$resultCatalog    = $connection->fetchCol($catalogEntitySql);
				$catalogEntityId  = $resultCatalog[0];

				//Optained Product From attribute id  ----- PRODUCT YEAR
				$yearIdSql    = "SELECT attribute_id FROM " . $eavAttributeTable . " WHERE attribute_code = 'dot' AND entity_type_id = " . $catalogEntityId . " LIMIT 1";
				$yearIdResult = $connection->fetchCol($yearIdSql);
				$yearId       = $yearIdResult[0];

				//Optained Product From Options option id
				$yearIdOptionSql       = "SELECT option_id FROM " . $eavAttributeOptionTable . " WHERE attribute_id = " . $yearId;
				$yearIdOptionSqlResult = $connection->fetchCol($yearIdOptionSql);
				//End of Optained Product From attribute id  ----- PRODUCT FROM
				//Obtained Old Qty
				$oldQtyArr      = $this->updateStockQtyBefore($response, $yearIdOptionSqlResult, $eavAttributeOptionValueTable, $connection, $cataloginventoryStockItemTable, $storeScope);
				//echo '<pre>';print_r($oldQtyArr);die;
				
				$objectManager  = \Magento\Framework\App\ObjectManager::getInstance();
				$_productloader = $objectManager->create('Magento\Catalog\Model\ProductFactory');
				$productObj = $objectManager->get('Magento\Catalog\Model\Product');
				$oldPrice       = '';
				$dataQty 		= '';
				foreach ($response as $data) {
					
					$productId = $data['web_product_id']; 
					$productSku = $data['web_product_sku']; 
					$dataQty = $data['total_qty']; 
                   /*  $_product = $this->productCollectionFactory->create()
                            ->addAttributeToSelect('*')
                            ->addAttributeToFilter('sku', $productSku);
                        // ->addAttributeToFilter('dot_value', $dataYear);

                    $product = $_product->getData(); */

                        /*echo "<pre>";
                        print_r($product);
                        exit;  */
						if($productObj->getIdBySku($productSku)){ 
                        if (!empty($productId) || $productId != null) {
							
                            /* $productPrice = $_productloader->create()->load($productId);
                            $oldPrice     = $productPrice->getPrice();
                            $productofer  = $productPrice->getOffers();
                            $productdot   = $productPrice->getResource()->getAttribute('dot')->getFrontend()->getValue($productPrice); */

                            //Update Stock Item Table
                            $isInStock = 0;

                            if ($dataQty > 0) {
                                $isInStock = 1;
                                $qtystatus = "Updated";
                            } else {
                                $dataQty   = 0;
                                $qtystatus = "Not Udpated Zero Found";
                            }

                            $updateStocksql = "UPDATE " . $cataloginventoryStockItemTable . " SET qty = " . $dataQty . " , is_in_stock = " . $isInStock . " where product_id = " . $productId . "";

                            $connection->query($updateStocksql);

                            /* if (empty($productofer)) {
                                $updatePriceindexsql = "UPDATE " . $catalogPriceIndexTable . " SET price = " . $fittPrice . " , min_price = " . $fittPrice . ", max_price = " . $fittPrice . ", final_price = " . $fittPrice . " where entity_id = " . $productId . "";

                                $updatePricedecimalsql = "UPDATE " . $catalogPriceDecimalTable . " SET value = " . $fittPrice . " where entity_id = " . $productId . "";

                                $connection->query($updatePriceindexsql);
                                $connection->query($updatePricedecimalsql);
                                $pricestatus = "Updated";
                            } else {
                                $fittPrice   = "";
                                $pricestatus = "Offer- Not Updated";
                            } */

                            /* $productdot = trim($productdot);
                            $dataYear   = trim($dataYear);
                            if ($dataYear == $productdot) {
                                $yearstatus = 'Same Year';
                            } else {

                                $_productobbj = $this->_objectManager->create('\Magento\Catalog\Api\ProductRepositoryInterface')->get($productPrice->getSku(), true, 0, true);
                                $producDesc   = $productPrice->getDescription();
                                $productname  = $producDesc . ' ' . $dataYear;
                                $_productobbj->setName($productname);
                                $_productobbj->save($_productobbj);
                                $dotId = $productPrice->getResource()->getAttribute("dot")->getSource()->getOptionId($dataYear);
                                $productPrice->setDot($dotId);
                                $productPrice->save();
                                $yearstatus = 'Change To ' . $dataYear;

                            } */

                            echo "Stock Update for Product Sku " . $productSku ." </br>";
                            $successcount++;
                            $responseRowData[] = array($productSku, $oldQtyArr[$productId], $dataQty, 'Success', 'Updated', $qtystatus);

                        } 
						}else {
                            echo "Failed Stock Update for Product Sku " . $productSku ." </br>";
                            $failedcount++;
                            $responseRowData[] = array($productSku, 'N/A', $dataQty, 'Failed', 'Product Not Found', 'Not Udpated');
                        }
                        $totalcount++;

                    
			 }
					if (count($responseRowData) > 1) {
						$this->_createcsvfile($responseRowData, $rfcid);
                    }
                    $this->_reIndexingAll();
					
					$rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
					$rfc->setData('rfc_datetime', $this->getTodaysDate());
					$rfc->setData('rfc_status', 'Success');
					$rfc->setData('rfc_total_record', $totalcount);
					$rfc->setData('rfc_total_sucess', $successcount);
					$rfc->setData('rfc_total_fail', $failedcount);
					$rfc->save();

					/* if ($rfcEnableEmail == 1) {
						$subject = "RFC run successfully - " . $rfcFunction;
						$message = "Stock Update RFC executed successfully.";
						$retval  = mail($to, $subject, $message);

						if ($retval == true) {
							echo "Inventory updated. Mail sent successfully!";
						} else {
							echo "Inventory updated. Mail could not be sent!";
						}
					} */
            }

            die('Complete');

        } else {
            echo "RFC Settings are disabled.";die();
        }
    }

    public function getStore()
    {
        return $this->_storeManager->getStore();
    }

    public function getTodaysDate()
    {
        $localeTimezone = $this->_timezoneInterface->getConfigTimezone('store', $this->getStore());
        date_default_timezone_set($localeTimezone);
        return $this->_timezoneInterface->date()->format('Y-m-d H:i:s');
    }

    public function getIpAddress()
    {
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

            $outputFile = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'rfcfiles/' . "Combine_ERP_To_Catalog_Stock_Update_" . $this->getTodaysDate() . ".csv";

            $handle = fopen($outputFile, 'w');
            foreach ($responseRow as $response) {
                fputcsv($handle, $response);
            }

            $rfc = $this->_objectManager->create('Hdweb\Rfc\Model\Rfc')->load($rfcid);
            $rfc->setData('rfc_manual_path', "Combine_ERP_To_Catalog_Stock_Update_" . $this->getTodaysDate() . ".csv");
            $rfc->save();
        }
    }

    public function updateStockQtyBefore($response, $yearIdOptionSqlResult, $eavAttributeOptionValueTable, $connection, $cataloginventoryStockItemTable, $storeScope)
    {
        if (count($response) > 0) {
            $oldQtyArray = array();
            foreach ($response as $data) {
				$productId = $data['web_product_id'];  
				if ($productId != null && $productId != "") {

					$getOldStocksql          = "SELECT qty FROM " . $cataloginventoryStockItemTable . " WHERE product_id = " . $productId . " LIMIT 1";
					$oldQtyResult            = $connection->fetchOne($getOldStocksql);
					//echo '<pre>';print_r($oldQtyResult);die;
					$oldQty                  = $oldQtyResult;
					$oldQtyArray[$productId] = $oldQty;
				}
            }
        }
        return $oldQtyArray;
    }

    public function _reIndexingAll()
    {
        $indexerCollection = $this->_indexerCollectionFactory->create();
        $indexerIds        = array(
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
