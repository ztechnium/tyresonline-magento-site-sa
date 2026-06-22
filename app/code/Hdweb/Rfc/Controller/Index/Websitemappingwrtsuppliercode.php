<?php 

namespace Hdweb\Rfc\Controller\Index;
 
class Websitemappingwrtsuppliercode extends \Magento\Framework\App\Action\Action {

    protected $resultPageFactory;
    protected $_logger;
    protected $scopeConfig;
    protected $_timezoneInterface;
    protected $_resouceConnection;
    protected $rfcCollection;
    protected $_filesystem;
    protected $_storeManager;
    protected $_messageManager;
	protected $request;
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
        \Hdweb\Rfc\Model\ResourceModel\Rfc\Collection $rfcCollection,
        \Magento\Framework\Filesystem $_filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Message\ManagerInterface $messageManager        
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->_logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->_timezoneInterface = $timezoneInterface;
        $this->_resouceConnection = $resouceConnection;
        $this->rfcCollection = $rfcCollection;
        $this->_filesystem = $_filesystem;
        $this->_storeManager = $storeManager;
		$this->request = $request;
        $this->_messageManager    = $messageManager;        
        parent::__construct($context);
    }

    /**
     * Execute view action
     * 
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {   
        $resultRedirect = $this->resultRedirectFactory->create();
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $rfcEnable = 1;//$this->scopeConfig->getValue('rfc_section/product_stock_rfc_group/product_stock_enable', $storeScope);
			
        if($rfcEnable == 1){
            
			$requestedCode = $this->request->getParam('supplier_code');
			$prodattrCode = '';
			if($requestedCode == 'ARD'){
				$prodattrCode = 'ard_code';
			}
			if($requestedCode == 'AB'){
				$prodattrCode = 'ab_code';
			}
			if($requestedCode == 'SAFEER'){
				$prodattrCode = 'safeer_code';
			}
			if($requestedCode == 'PSA'){
				$prodattrCode = 'psa_code';
			}
			if($requestedCode == 'Dubai Tyres'){
				$prodattrCode = 'dubai_tyres_code';
			}
			if($requestedCode == 'General Vendor'){
				$prodattrCode = 'general_vendor_code';
			}
			if($requestedCode == 'Sandance'){
				$prodattrCode = 'sandance_code';
			}
			
			
			$connection = $this->_resouceConnection->getConnection();
			
			$rfcSupplierTable = $this->_resouceConnection->getTableName('rfc_supplierproducts');
			$query = "SELECT item_code, item_year FROM " . $rfcSupplierTable . " WHERE supplier_code ='" . $requestedCode . "'";
			$result = $connection->fetchAll($query);
			
			if(count($result) > 0){
			foreach ($result as $data) {
				$dataItcode = trim($data['item_code']);
				$dataYear = trim($data['item_year']);
				$itemExecutedDate = date('Y-m-d H:i:s');
				
				$productData = $this->getWebproductdata($dataItcode, $dataYear, $prodattrCode);
				//echo '<pre>';print_r($productData);die;
				
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
                    
					//echo $dataItcode.'--'.$webProductQty.'<br>';
					$updateProductsql = "UPDATE ".$rfcSupplierTable. " SET web_product_id = ".$webProductId.", web_product_sku = '" . $webProductSku . "', web_product_qty = ".$webProductQty.", web_product_price = ".$webProductPrice.", web_product_offer = '" . $webProductOffer . "', web_product_status = ".$webProductStatus.", item_executed_date = '" . $itemExecutedDate . "' WHERE item_code = '" . $dataItcode . "' AND item_year = " . $dataYear . "";
					//echo $updateProductsql.'<br/>';
					$connection->query($updateProductsql);
				}
				
			}
                $this->_messageManager->addSuccess('RFC successfuly executed');
                return $resultRedirect->setPath('tyadmin/rfc/manage');				
			}

        }else {
           echo "RFC Settings are disabled."; die();
		}
	}
	
	public function getWebproductdata($itemCode, $itemYear, $prodattrCode){
		
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
		
		$gccattribute_id = $gcc_codeId;
        $yearattribute_id = $yearattrId;
		
		$at_attr_code_default = 'at_'.$prodattrCode.'_default';
		/* $query = "SELECT e.*,at_gcc_code_default.value as 'gccattr',at_dot.value as 'dotattr' FROM catalog_product_entity AS e INNER JOIN catalog_product_entity_varchar AS at_gcc_code_default ON at_gcc_code_default.entity_id = e.entity_id
            AND at_gcc_code_default.attribute_id =" . $gccattribute_id . " INNER JOIN catalog_product_entity_int AS at_dot ON at_dot.entity_id= e.entity_id AND at_dot.attribute_id = " . $yearattribute_id . " WHERE at_gcc_code_default.value = '" . $itemCode . "' AND  at_dot.value = " . $yearOptionValueId . " "; */
		
		$query = "SELECT e.*," . $at_attr_code_default. ".value as 'gccattr',at_dot.value as 'dotattr' FROM catalog_product_entity AS e INNER JOIN catalog_product_entity_varchar AS " . $at_attr_code_default. " ON " . $at_attr_code_default. ".entity_id = e.entity_id
            AND " . $at_attr_code_default. ".attribute_id =" . $gccattribute_id . " INNER JOIN catalog_product_entity_int AS at_dot ON at_dot.entity_id= e.entity_id AND at_dot.attribute_id = " . $yearattribute_id . " WHERE " . $at_attr_code_default. ".value = '" . $itemCode . "' AND  at_dot.value = " . $yearOptionValueId . " ";
		
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
}