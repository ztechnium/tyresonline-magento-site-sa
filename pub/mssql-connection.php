
<?php
try {
$dbuser = 'gssusr24coast';
$dbpass = 'Gcc0!12345DB';
$dbhost = 'gcccoastmsdb1.cujpiuqzsj4l.ap-south-1.rds.amazonaws.com';
$dbname='gcccoastmsdb';
$conn = new PDO("dblib:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
}catch (PDOException $e) {
echo "Error : " . $e->getMessage() . "<br/>";
die();
}
$query = "SELECT * FROM [gcccoastmsdb].[dbo].[WITMAST]";
$stmt = $conn->prepare($query);
$stmt->execute();
//$result = $stmt->fetchAll();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($result);
exit;



use \Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$instance = \Magento\Framework\App\ObjectManager::getInstance();
$state = $objectManager->get('\Magento\Framework\App\State');
$state->setAreaCode('frontend');
$product_collections = $instance->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
 $stockRegistry = $instance->create('Magento\CatalogInventory\Api\StockRegistryInterface');

foreach ($result as $key => $val) {
	
	$_product = $product_collections->create()
                                    ->addAttributeToSelect('*')
                                    ->addAttributeToFilter('itcode', $val['ITCODE']);
   if(count($_product) > 0 ){
   	  echo $_product->getFirstItem()->getSku();
   	   $productobj=$_product->getFirstItem();
   	    $stockItem = $stockRegistry->getStockItem($productobj->getId());
   	    $stockItem->setData('qty', $val['STOCK']); 
   	    if($val['STOCK'] >  0){
   	    	$stockItem->setData('is_in_stock',1);
   	    }else{
            $stockItem->setData('is_in_stock',0);
   	    }
        
        $stockItem->save(); 
   	  echo "<br>";
   }                                 
}

                               