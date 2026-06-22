<?php
namespace Hdweb\Oilservice\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const category_car_id  		= 'productsearch/general/category_car_id';
    const category_moto_id 		= 'productsearch/general/category_moto_id';
	const TECDOC_MANDATOR 		= 22275;
	const TECDOC_APIKEY   		= '2BeBXg6D4poD8ex7mow1xebeBBDaHU1aaP8nsVWQbYi1N1bhjxJa';
	const SERVICE_URL 			= 'https://webservice.tecalliance.services/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?api_key=2BeBXg6D4poD8ex7mow1xebeBBDaHU1aaP8nsVWQbYi1N1bhjxJa';
    protected $_storeManager;
    protected $eavConfig;
    protected $scopeConfig;
    protected $_customerSession;
	protected $cart;
    protected $_productRepository;
	protected $_objectManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession,
		\Magento\Checkout\Model\Cart $cart,
		\Magento\Catalog\Api\ProductRepositoryInterface $_productRepository,
		\Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_storeManager    = $storeManager;
        $this->eavConfig        = $eavConfig;
        $this->scopeConfig      = $scopeConfig;
        $this->_customerSession = $customerSession;
		$this->cart = $cart;
        $this->_productRepository  = $_productRepository;
		$this->_objectManager = $objectManager;
        parent::__construct($context);
    }
	
	public function getTechdocApiConnection($function, $params)
	{
		$request = $this->createRequest( $function, $params );
		$result = $this->callJSON( $function, $request );
		return $result;
	}
	
	public function getContext( $data, $optional_headers ) {
        $params = array(
                    'http' => array(
                    'method' => 'POST',
                    'content' => $data
                  )
        );
 
        if ( $optional_headers !== null ) {
          $params[ 'http' ][ 'header' ] = $optional_headers;
        }
        
        return stream_context_create( $params );
    }
	
	// Create request with function name and its parameters:
	public function createRequest( $functionName, $requestParams ) {
		return array(
		  $functionName => $requestParams
		);
	}
	
	// Serializing request, calling JSON endpoint & deserializing response:
	public function callJSON( $function, $request, $optional_headers = null ) {        
		$jsonRequest = json_encode( $request );
		
		$ctx = $this->getContext( $jsonRequest, $optional_headers );
		$fp = @fopen( self::SERVICE_URL, 'rb', false, $ctx );
		if ( !$fp ) {
		  throw new Exception( "Problem with $url, $php_errormsg" );
		}

		$jsonResponse = @stream_get_contents($fp);
		if ( $jsonResponse === false ) {
		  throw new Exception( "Problem reading data from $url, $php_errormsg" );
		}
		
		$response = json_decode($jsonResponse);
		
		return $response;
	}
	
	public function getTechdocProductPartDetails($partNumber)
    {
        /* Start Techdoc API */
			$function = 'getArticleDirectSearchAllNumbersWithState';
			$params   = array(
				'articleCountry'       => 'AE',
				'lang'                 => 'en',
				'articleNumber'   	   => $partNumber,
				'provider'             => self::TECDOC_MANDATOR,
			);
			
			$apiResponse   = $this->getTechdocApiConnection($function, $params);
			
			$articleIdArray  = array();
			$articleArray  = array();
			if(isset($apiResponse->data->array)){
			foreach ($apiResponse->data->array as $item) {
				//echo '<pre>';print_r($item);
				/* if($item->brandName == $brand){
					$articleIdArray[] = $item->articleId;
				}else{
					continue;
				} */
				$articleIdArray[] = $item->articleId;
			}
				$articleId = $articleIdArray[0];
				$function = 'getArticles';
				$params   = array(
					'articleCountry'      		=> 'AE',
					'lang'                 		=> 'en',
					'legacyArticleIds'	   		=> $articleId,
					'perPage'              		=> 1000,
					'includeArticleCriteria' 	=> true,
					'includePDFs' 				=> true,
					'includeImages' 			=> true,
					'provider'             		=> self::TECDOC_MANDATOR,
				);
				$apiResponse   = $this->getTechdocApiConnection($function, $params);
				
				foreach ($apiResponse->articles as $item) {
					$articleCriteria = $item->articleCriteria;
					foreach($articleCriteria as $articleInfo){
						//$criteriaDescription = $articleInfo->criteriaDescription;
						//$formattedValue = $articleInfo->formattedValue;
						/* echo $criteriaDescription.'<br/>';
						echo $formattedValue.'<br/>'; */
						$articleArray['criteriaInfo'][] = array('criteriaDescription' => $articleInfo->criteriaDescription, 'formattedValue' => $articleInfo->formattedValue);
					}
					if(isset($item->pdfs)){
						$articlePdf = $item->pdfs;
						foreach($articlePdf as $pdfInfo){
							//$pdfUrl = $pdfInfo->url;
							$articleArray['pdf'][] = $pdfInfo->url;
						}
					}
					if(isset($item->images)){
						$articleImages = $item->images;
						foreach($articleImages as $images){
							//$imagesUrl = $images->imageURL800;
							$articleArray['images'][] = $images->imageURL800;
						}
					}
				}
			}
			
			return $articleArray;
		/* End Techdoc API */
    }
	
	/* get vehicle details by car id */
	public function getTechdocVehicleDetails($carId)
    {
        /* Start Techdoc API */
		$function = 'getVehicleByIds3';
		$params   = array(
			'articleCountry'       		=> 'AE',
			'lang'                 		=> 'en',
			'carIds'   	   		   		=> array('array' => $carId),
			'countriesCarSelection'   	=> 'AE',
			'country'   				=> 'AE',
			'provider'             		=> self::TECDOC_MANDATOR,
		);
		
		$apiResponse   = $this->getTechdocApiConnection($function, $params);
		$vehicleArray = array();
		if(isset($apiResponse->data->array)){
			foreach ($apiResponse->data->array as $item) {
				if(isset($item->vehicleDetails)){
					$vehicleArray[] = $item->vehicleDetails;
				}
			}
		}
		return $vehicleArray;
		/* End Techdoc API */
    }
	
	public function isAutopartsCategory()
	{
		$autopartsCategories = array(35,36,37,38);
		$allotemcategory=array();
         foreach ($this->cart->getItems() as $key => $item) {
             $pid = $item->getProductId();
             $product = $this->_productRepository->getById($pid);
             $cats = $product->getCategoryIds();
             $allotemcategory = array_merge($allotemcategory,$cats);   
         }
		 $isAutoparts = 0;
		 if(count(array_intersect($allotemcategory,$autopartsCategories))){
			 $isAutoparts = 1;
		 }
		return $isAutoparts; 
	}

	public function isAdminOrderAutopartsCategory($order_id)
	{
		$orderRepository = $this->_objectManager->get('Magento\Sales\Api\OrderRepositoryInterface');
		$order = $orderRepository->get($order_id);
		$autopartsCategories = array(35,36,37,38);
		$allotemcategory=array();
         foreach ($order->getAllVisibleItems() as $key => $item) {
			 //echo '<pre>';print_r($item->getProductId());
			 
             $pid = $item->getProductId();
             $product = $this->_productRepository->getById($pid);
             $cats = $product->getCategoryIds();
             $allotemcategory = array_merge($allotemcategory,$cats);   
         }
		 $isAutoparts = 0;
		 if(count(array_intersect($allotemcategory,$autopartsCategories))){
			 $isAutoparts = 1;
		 }
		return $isAutoparts; 
	}
	
	public function getAttributeDropdownvalueId($attribute_value, $attribute_code){
		
		$eavConfig = $this->_objectManager->get('Magento\Eav\Model\Config');
		$optionId = $eavConfig->getAttribute('catalog_product', $attribute_code)->getSource()->getOptionId($attribute_value);
		return $optionId;
	}
	
	public function isMotorcycleCategory()
	{
		$category_moto_id = $this->_storeManager->getStore()->getConfig(self::category_moto_id);
		$isMotorcycleCategory = 0;
		if($category_moto_id != ''){
			$autopartsCategories = array($category_moto_id);
			$allotemcategory=array();
			 foreach ($this->cart->getItems() as $key => $item) {
				 $pid = $item->getProductId();
				 $product = $this->_productRepository->getById($pid);
				 $cats = $product->getCategoryIds();
				 $allotemcategory = array_merge($allotemcategory,$cats);   
			 }
			 if(count(array_intersect($allotemcategory,$autopartsCategories))){
				 $isMotorcycleCategory = 1;
			 }
		}
		return $isMotorcycleCategory; 
	}
	
	public function getWpblogList()
	{
		$servername = "localhost";
		$database = "tyresonline-blog-new";
		$username = "tyoblogusr-new";
		$password = "tyodevblog-1112";
		// Create connection
		$conn = mysqli_connect($servername, $username, $password, $database);
		// Check connection
		if ($conn->connect_error) {
		  die("Connection failed: " . $conn->connect_error);
		}
		
		//$baseUrl = $this->_storeManager->getStore()->getBaseUrl();

		$sql = "SELECT p.id AS post_id, p.post_title AS post_title, SUBSTRING(p.post_content, 1, 300) AS post_content, p.post_name AS post_url, p.post_date AS post_date, p.post_title, concat('https://www.tyresonline.ae/blog','/wp-content/uploads/',pm2.meta_value) AS post_image FROM `wp_posts` AS p INNER JOIN `wp_postmeta` AS pm1 ON p.id = pm1.post_id INNER JOIN `wp_postmeta` AS pm2 ON pm1.meta_value = pm2.post_id AND pm2.meta_key = '_wp_attached_file' AND pm1.meta_key = '_thumbnail_id' ORDER BY p.id DESC LIMIT 0,8";
		$result = $conn->query($sql);
		$resultArray = array();
		if($result->num_rows > 0){
		  while($row = $result->fetch_assoc()) {
			$resultArray[] = $row;
		  }
		} 
		$conn->close();
		return $resultArray;
	}
	
	public function getStoredVehicleData()
	{
		$vehicleArray = array();
		if(isset($_COOKIE['storedVehicleData'])){
			$vehicle_make_id = $_COOKIE['storedVehicleData']['vehicle_make_id'];
			$vehicle_make_label = $_COOKIE['storedVehicleData']['vehicle_make_label'];
			$vehicle_model_id = $_COOKIE['storedVehicleData']['vehicle_model_id'];
			$vehicle_model_label = $_COOKIE['storedVehicleData']['vehicle_model_label'];
			$vehicle_engine_id = $_COOKIE['storedVehicleData']['vehicle_engine_id'];
			$vehicle_engine_label = $_COOKIE['storedVehicleData']['vehicle_engine_label'];
			$oil_per_litre = '';
			if(isset($_COOKIE['storedVehicleData']['oil_per_litre'])){
				$oil_per_litre = $_COOKIE['storedVehicleData']['oil_per_litre'];
			}
			$mappingId = '';
			if(isset($_COOKIE['storedVehicleData']['mapping_id'])){
				$mappingId = $_COOKIE['storedVehicleData']['mapping_id'];
			}
			
			$vehicle_image = 'https://webservice.tecalliance.services/pegasus-3-0/documents/'.self::TECDOC_MANDATOR.'/DR'.$vehicle_engine_id.'/0?api_key='.self::TECDOC_APIKEY.'';
			
			$vehicleArray = array('vehicle_make_id' => $vehicle_make_id, 'vehicle_make_label' => $vehicle_make_label, 'vehicle_model_id' => $vehicle_model_id, 'vehicle_model_label' => $vehicle_model_label, 'vehicle_engine_id' => $vehicle_engine_id, 'vehicle_engine_label' => $vehicle_engine_label, 'vehicle_image' => $vehicle_image, 'oil_per_litre' => $oil_per_litre, 'mapping_id' => $mappingId);
		}
		// set vehicle info for test purpose //
		//$vehicleArray = array('vehicle_make_id' => 5, 'vehicle_make_label' => 'AUDI', 'vehicle_model_id' => 8986, 'vehicle_model_label' => 'A7 Sportback (4GA, 4GF) - (07.2010 - 05.2018)', 'vehicle_engine_id' => 107858, 'vehicle_engine_label' => '2.0 TFSI', 'vehicle_image' => 'https://webservice.tecalliance.services/pegasus-3-0/documents/22275/DR107858/0?api_key=2BeBXg6D4poD8ex7mow1xebeBBDaHU1aaP8nsVWQbYi1N1bhjxJa', 'oil_per_litre' => 4.6, 'mapping_id' => 2090);
		return $vehicleArray;
	}
	
	public function getServicePacksCollection($vehicle_make = null, $vehicle_make_label = null, $vehicle_model = null, $vehicle_model_label = null, $vehicle_engine = null, $vehicle_engine_label = null)
	{
		$oilTechDocCategoryId = 101994;
		$oilfilterTechDocCategoryId = 100470;
		$airfilterTechDocCategoryId = 100260;
		$acfilterTechDocCategoryId = 100263;
    	//$assemblyGroupNodeIds = $oilTechDocCategoryId;
		
		if(!empty($vehicle_make) && !empty($vehicle_model) && !empty($vehicle_engine)){
		}else{
			//$storedVehicleData   = $this->getStoredVehicleData();
			$storedVehicleData   = $this->getUserVehicleDetail();
			if(count($storedVehicleData) > 0){
				$vehicle_make = $storedVehicleData['vehicle_make_id'];
				$vehicle_make_label = $storedVehicleData['vehicle_make_label'];
				$vehicle_model = $storedVehicleData['vehicle_model_id'];
				$vehicle_model_label = $storedVehicleData['vehicle_model_label'];
				$vehicle_engine = $storedVehicleData['vehicle_engine_id'];
				$vehicle_engine_label = $storedVehicleData['vehicle_engine_label'];  
				$vehicle_oil_litre = $storedVehicleData['oil_per_litre']; 
			}
		}
		$oilchangeProducts = '';
		$oilfilterProducts = '';
		$airfilterProducts = '';
		$acfilterProducts = '';
		$hasServicePacks = 0;
		if(!empty($vehicle_make) && !empty($vehicle_model) && !empty($vehicle_engine)){
			$oilgradeCollection = $this->_objectManager->get('Hdweb\Oilservice\Model\OilgradeFactory')->create()->getCollection()
								  ->addFieldToSelect('oil_litre')
								  ->addFieldToFilter('make_id', ['eq' => $vehicle_make])
								  ->addFieldToFilter('model_id', ['eq' => $vehicle_model])
								  ->addFieldToFilter('engine_id', ['eq' => $vehicle_engine])
								  ->getFirstItem();
								  
			$oilPerLitre = '';					  
			if(count($oilgradeCollection->getData()) > 0){
				$oilPerLitre = $oilgradeCollection->getOilLitre();
			}
			
			setcookie ("storedVehicleData[vehicle_make_id]", $vehicle_make, time() + (86400 * 30), "/");
			setcookie ("storedVehicleData[vehicle_make_label]", $vehicle_make_label, time() + (86400 * 30), "/");
			setcookie ("storedVehicleData[vehicle_model_id]", $vehicle_model, time() + (86400 * 30), "/");
			setcookie ("storedVehicleData[vehicle_model_label]", $vehicle_model_label, time() + (86400 * 30), "/");
			setcookie ("storedVehicleData[vehicle_engine_id]", $vehicle_engine, time() + (86400 * 30), "/");
			setcookie ("storedVehicleData[vehicle_engine_label]", $vehicle_engine_label, time() + (86400 * 30), "/");
			setcookie ("storedVehicleData[oil_per_litre]", $oilPerLitre, time() + (86400 * 30), "/");
			
			$oilchangeProducts  = $this->getOilChangeProductsCollection($vehicle_engine, $oilTechDocCategoryId, $this->_objectManager); // oil change

			$oilfilterProducts  = $this->getOilFilterProductsCollection($vehicle_engine, $oilfilterTechDocCategoryId, $this->_objectManager); // oil filter
			
			$airfilterProducts  = $this->getOilFilterProductsCollection($vehicle_engine, $airfilterTechDocCategoryId, $this->_objectManager); // air filter
			
			$acfilterProducts  = $this->getOilFilterProductsCollection($vehicle_engine, $acfilterTechDocCategoryId, $this->_objectManager); // ac filter
			
			if(count($oilchangeProducts) > 0 && count($oilfilterProducts) > 0 && count($airfilterProducts) > 0 && count($acfilterProducts) > 0){
				$hasServicePacks = 1;
			}else{
				$hasServicePacks = 0;
			}
			
			$result = array('oilchangecollection' => $oilchangeProducts, 'oilfiltercollection' => $oilfilterProducts, 'airfiltercollection' => $airfilterProducts, 'acfiltercollection' => $acfilterProducts, 'hasServicePacks' => $hasServicePacks);
			return $result;
		}else{
			$result = array('oilchangecollection' => $oilchangeProducts, 'oilfiltercollection' => $oilfilterProducts, 'airfiltercollection' => $airfilterProducts, 'acfiltercollection' => $acfilterProducts, 'hasServicePacks' => $hasServicePacks);
			return $result;
		}
		
	}
	
	public function getOilChangeProductsCollection($carId, $assemblyGroupNodeIds, $objectManager)
	{
		
		$function = 'getArticles';
			$params   = array(
				'articleCountry'         => 'AE',
				'lang'                   => 'en',
				'linkageTargetType'      => 'P',
				'assemblyGroupNodeIds'   => $assemblyGroupNodeIds,
				'linkageTargetId'        => $carId,
				'perPage'                => 1000,
				'includeArticleCriteria' => true,
				'provider'               => self::TECDOC_MANDATOR,
			);
			
			$apiResponse   = $this->getTechdocApiConnection($function, $params);
			$oilGradeArray = array();
			$oilGradeOptionIds = array();
			foreach ($apiResponse->articles as $item) {
				//echo '<pre>';print_r($item->articleCriteria);
				foreach ($item->articleCriteria as $oilData) {
					$criteriaId = $oilData->criteriaId;
					if ($criteriaId != 2467) {
						continue;
					}
					$oilGradeArray[] = $oilData->formattedValue;
					//$oilGradeOptionIds[] = $objectManager->create('Hdweb\Oilservice\Helper\Data')->getAttributeDropdownvalueId($oilData->formattedValue, 'oil_grade');
				}

			}
			$productCollectionFactory =  $objectManager->get('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
			$collection = $productCollectionFactory->create();
			if (count($oilGradeArray) > 0) {
				$oilGradeArray = array_unique($oilGradeArray);
				//$oilGradeOptionIds = array_unique($oilGradeOptionIds);
				$collection->addAttributeToSelect('*');
				$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
				$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
				
				$defaultoilGrade = '5W-40';
				$defaultBrandId = '1663';
				if (in_array('5W-40', $oilGradeArray)){
					$collection->addAttributeToFilter('article_number', ['eq' => $defaultoilGrade]);
					$collection->addAttributeToFilter('mgs_brand', ['eq' => $defaultBrandId]);
				}
				
				$collection->addAttributeToFilter('article_number', ['in' => $oilGradeArray]);
			} /* else{
				$defaultoilGrade = '5W-40';
				$defaultBrandId = '1663';
				$productCollectionFactory =  $objectManager->get('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
				$collection = $productCollectionFactory->create();
				$collection->addAttributeToSelect('*');
				$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
				$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
				$collection->addAttributeToFilter('article_number', ['eq' => $defaultoilGrade]);
				$collection->addAttributeToFilter('mgs_brand', ['eq' => $defaultBrandId]);
			} */
			return $collection;
	}
	
	public function getOilFilterProductsCollection($carId, $assemblyGroupNodeIds, $objectManager)
	{
		$function = 'getArticles';
		$params   = array(
			'articleCountry'       => 'AE',
			'lang'                 => 'en',
			'linkageTargetType'    => 'P',
			'assemblyGroupNodeIds' => $assemblyGroupNodeIds,
			'linkageTargetId'      => $carId,
			'perPage'              => 1000,
			'provider'             => self::TECDOC_MANDATOR,
		);
		$apiResponse   = $this->getTechdocApiConnection($function, $params);
		$articleArray  = array();
		foreach ($apiResponse->articles as $item) {
			$articleArray[] = $item->articleNumber;
		}
		
		$oemnumber = $this->getOEMNumbers($articleArray);
		if($oemnumber){
			$articleNumbers = array_unique (array_merge ($articleArray, $oemnumber));
		}else{
			$articleNumbers = array_unique($articleArray);
		}
		
		$productCollectionFactory =  $objectManager->get('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
		$collection = $productCollectionFactory->create();
		$collection->addAttributeToSelect('*');
		$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
		$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
		//$collection->addAttributeToFilter('article_number', ['in' => $articleArray]);
		$collection->addAttributeToFilter('article_number', ['in' => $articleNumbers]);
		$collection->setOrder('price', 'DESC');
		$collection->setPageSize(1);
		return $collection;
	}
	
	public function getOilgradeVehicleModel($makeId)
	{	
	$oilgradeCollection = $this->_objectManager->get('Hdweb\Oilservice\Model\OilgradeFactory')->create()->getCollection()
						->distinct(true)
						->addFieldToSelect('model_id')
						->addFieldToSelect('model')
						->addFieldToFilter('make_id', $makeId)
					    ->addFieldToFilter('oil_litre',  array('neq' => ''));
		$vehicleModel = array();				
		foreach($oilgradeCollection as $modelData){
			$modelId = $modelData->getModelId();
			$model = $modelData->getModel();
			$vehicleModel[] = array('modelId' => $modelId, 'modelname' => $model);
		}
	
	return $vehicleModel;	
	}
	
	public function getOilgradeVehicleEngine($makeId, $modelId)
	{
		$oilgradeCollection = $this->_objectManager->get('Hdweb\Oilservice\Model\OilgradeFactory')->create()->getCollection()
						->distinct(true)
						->addFieldToSelect('engine_id')
						->addFieldToSelect('engine')
						->addFieldToSelect('oil_litre')
						->addFieldToFilter('make_id', $makeId)
						->addFieldToFilter('model_id', $modelId)
					    ->addFieldToFilter('oil_litre',  array('neq' => ''));
					
		$vehicleEngine = array();				
		foreach($oilgradeCollection as $engineData){
			$engineId = $engineData->getEngineId();
			$engine = $engineData->getEngine();
			//$engineText = $engine.' (Oil Litre '.$engineData->getOilLitre().')';
			$engineText = $engine;
			$vehicleEngine[] = array('carId' => $engineId, 'carName' => $engineText);
		}
	
		return $vehicleEngine;	
	}
	
	public function getCartTyreSize()
	{
		$storedVehicleData   = $this->getStoredVehicleData();
		$autopartsCollection = array();
		if(count($storedVehicleData) > 0){ 
			$mappingId = $storedVehicleData['mapping_id']; 
			if($mappingId != ''){
				$autopartsCollection = $this->_objectManager->get('Hdweb\Oilservice\Model\AutopartsFactory')->create()->getCollection()
									->addFieldToFilter('mapping_id', ['eq' => $mappingId]);
				//echo '<pre>';print_r($autopartsCollection->getData());					
			}
		}
		return $autopartsCollection;
	}
	
	public function setVehicleData($make, $model, $year, $engine)
	{
		$vehicleData = array();
		$collection = $this->_objectManager->get('Hdweb\Oilservice\Model\AutopartsFactory')->create()->getCollection()
							  ->addFieldToSelect('mapping_id')
							  ->addFieldToFilter('make', array('like' => '%'.$make.'%'))
							  ->addFieldToFilter('model', array('like' => '%'.$model.'%'))
							  ->addFieldToFilter('year', array('like' => '%'.$year.'%'))
							  ->addFieldToFilter('engine', array('like' => '%'.$engine.'%'))
							  ->getFirstItem();
		if(count($collection->getData()) > 0){
			$mappingId = $collection->getMappingId();
			$oilgradeCollection = $this->_objectManager->get('Hdweb\Oilservice\Model\OilgradeFactory')->create()->getCollection()
								  ->addFieldToFilter('mapping_id', ['eq' => $mappingId])
								  ->getFirstItem();
			if(count($oilgradeCollection->getData()) > 0){ 
				setcookie ("storedVehicleData[vehicle_make_id]", $oilgradeCollection['make_id'], time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[vehicle_make_label]", $oilgradeCollection['make'], time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[vehicle_model_id]", $oilgradeCollection['model_id'], time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[vehicle_model_label]", $oilgradeCollection['model'], time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[vehicle_engine_id]", $oilgradeCollection['engine_id'], time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[vehicle_engine_label]", $oilgradeCollection['engine'], time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[oil_per_litre]", $oilgradeCollection['oil_litre'], time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[mapping_id]", $mappingId, time() + (86400 * 30), "/");
			}						
		}					  
		return $this;
	}
	
	public function storeUserVehicleDetail($vehicleDetails, $customerId, $customerEmail)
	{
		if(!empty($vehicleDetails) && !empty($customerId)){
			$vehicle_make = $vehicleDetails['add_vehicle_make'];
			$vehicle_make_label = $vehicleDetails['add_vehicle_make_label'];
			$vehicle_model = $vehicleDetails['add_vehicle_model'];
			$vehicle_model_label = $vehicleDetails['add_vehicle_model_label'];
			$vehicle_engine  = $vehicleDetails['add_vehicle_engine'];
			$vehicle_engine_label = $vehicleDetails['add_vehicle_engine_label'];
			
			if(!empty($vehicle_make) && !empty($vehicle_model) && !empty($vehicle_engine)){
				$oilgradeCollection = $this->_objectManager->get('Hdweb\Oilservice\Model\OilgradeFactory')->create()->getCollection()
									  ->addFieldToSelect('oil_litre')
									  ->addFieldToSelect('mapping_id')
									  ->addFieldToFilter('make_id', ['eq' => $vehicle_make])
									  ->addFieldToFilter('model_id', ['eq' => $vehicle_model])
									  ->addFieldToFilter('engine_id', ['eq' => $vehicle_engine])
									  ->getFirstItem();				  
				$oilPerLitre = '';					  
				if(count($oilgradeCollection->getData()) > 0){
					$oilPerLitre = $oilgradeCollection->getOilLitre();
					$mappingId = $oilgradeCollection->getMappingId();
					
				}
				
				$vehicleArray = array('vehicle_make_id' => $vehicle_make, 'vehicle_make_label' => $vehicle_make_label, 'vehicle_model_id' => $vehicle_model, 'vehicle_model_label' => $vehicle_model_label, 'vehicle_engine_id' => $vehicle_engine, 'vehicle_engine_label' => $vehicle_engine_label, 'oil_per_litre' => $oilPerLitre, 'mapping_id' => $mappingId);
				
				setcookie ("storedVehicleData[vehicle_make_id]", $vehicle_make, time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[vehicle_make_label]", $vehicle_make_label, time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[vehicle_model_id]", $vehicle_model, time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[vehicle_model_label]", $vehicle_model_label, time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[vehicle_engine_id]", $vehicle_engine, time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[vehicle_engine_label]", $vehicle_engine_label, time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[oil_per_litre]", $oilPerLitre, time() + (86400 * 30), "/");
				setcookie ("storedVehicleData[mapping_id]", $mappingId, time() + (86400 * 30), "/");
			}
		}
		return $this;
	}
	
	public function getOEMNumbers($articleArray)
    {
		$function = 'getArticleDirectSearchAllNumbersWithState';
		foreach($articleArray as $articleNumber){
			
			$params   = array(
				'articleCountry'       => 'AE',
				'lang'                 => 'en',
				'articleNumber'   	   => $articleNumber,
				'provider'             => self::TECDOC_MANDATOR,
			);
			
			$apiResponse   = $this->getTechdocApiConnection($function, $params);
			$articleIdArray  = array();
			$function = 'getArticles';
			foreach ($apiResponse->data->array as $item) {
				$articleId = $item->articleId;
				$params = array(
					'articleCountry'      		=> 'AE',
					'lang'                 		=> 'en',
					'legacyArticleIds'	   		=> $articleId,
					'perPage'              		=> 1000,
					'includeAll' 				=> true,
					'provider'             		=> self::TECDOC_MANDATOR,
				);
				$apiResponse   = $this->getTechdocApiConnection($function, $params);
				$oemNumbersArray  = array();
				foreach ($apiResponse->articles as $item) {
					if(isset($item->oemNumbers)){
						$articleOemNumbers = $item->oemNumbers;
						foreach($articleOemNumbers as $oemNumbersInfo){
							$oemNumbersArray[] = $oemNumbersInfo->articleNumber;
						}
						$oemNumbersArray = array_unique($oemNumbersArray);
					}
				}
				return $oemNumbersArray;
			}
		}
		
	}
	
	public function getTechdocVehicleOptions()
	{
	  $function = 'getManufacturers';
	  $params = array(
				  'country' => 'AE',
				  'lang' => 'en',
				  'linkingTargetType' => 'V',
				  'provider' => self::TECDOC_MANDATOR
				);		
		$response = $this->getTechdocApiConnection($function, $params);
		$apiResponse = array();
		foreach($response->data->array as $item){
			//echo '<pre>';print_r($item->manuId);				
			$input = strtolower(preg_replace("/[^a-zA-Z]+/", "-", $item->manuName));
			$slugName = rtrim($input, "-");
			if($item->manuName == 'FORD ASIA / OCEANIA' || $item->manuName == 'FORD AUSTRALIA' || $item->manuName == 'MAYBACH'){
				continue;
			}
			$apiResponse[] = array('menuId' => $item->manuId, 'manuName' => $item->manuName, 'slug' => $slugName);
		}
		
		return $apiResponse;
	}
	
	public function createUserVehicleKey($vehicleArray, $customerId = '', $customerEmail = '')
	{
		$vehicle_detail = json_encode($vehicleArray);
		$vehicle_details = serialize($vehicle_detail);
		//echo '<pre>';print_r($vehicle_details);die;
		$resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
		$connection = $resource->getConnection();
		$tableName = $resource->getTableName('hdweb_user_vehicle_info');
		
		if($customerId != ''){ 
			$query = 'SELECT id FROM ' . $tableName . ' WHERE `customer_id` ='.$customerId;
			$vehicleId = $connection->fetchOne($query);
			if($vehicleId != ''){
				$data = ["vehicle_detail" => $vehicle_details];
				$where = ['id = ?' => (int)$vehicleId];
				$updatedRows = $connection->update($tableName, $data, $where);
			}else{
				//Insert Data into table
				$data = [
					//'user_vehicle_key' => $userVehicleKey,
					'customer_id' => $customerId,
					'customer_email' => $customerEmail,
					'vehicle_detail' => $vehicle_details
				];
				$connection->insert($tableName, $data);
			}
		}else{
			if(isset($_COOKIE['user_vehicle_key'])){ /* update vehicle information */ 
				$query = 'SELECT id FROM ' . $tableName . ' WHERE `user_vehicle_key` ="' . $_COOKIE['user_vehicle_key'] . '"';
				$vehicleId = $connection->fetchOne($query);
				if($vehicleId != ''){ /* update veh*/ 
					$data = ["vehicle_detail" => $vehicle_details];
					$where = ['id = ?' => (int)$vehicleId];
					$updatedRows = $connection->update($tableName, $data, $where);
				}
			}else{ /* add vehicle information */ 
				$str = rand();
				$userVehicleKey = md5($str);
				setcookie ("user_vehicle_key", $userVehicleKey, time() + (86400 * 30), "/");
				
				//Insert Data into table
				$data = [
					'user_vehicle_key' => $userVehicleKey,
					'vehicle_detail' => $vehicle_details
				];
				$connection->insert($tableName, $data);
			}	
		}
		return $this;
	}
	
	public function getUserVehicleDetail()
	{
		$resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
		$connection = $resource->getConnection();
		$tableName = $resource->getTableName('hdweb_user_vehicle_info');
		$vehicleArray = array();
		if(isset($_COOKIE['user_vehicle_key'])){
			$query = 'SELECT vehicle_detail FROM ' . $tableName . ' WHERE `user_vehicle_key` ="' . $_COOKIE['user_vehicle_key'] . '"';
			$vehicleDetail = $connection->fetchOne($query);			
			$vehicle_details = unserialize($vehicleDetail);
			$vehicleInfo = json_decode($vehicle_details);
			
			$vehicle_image = 'https://webservice.tecalliance.services/pegasus-3-0/documents/'.self::TECDOC_MANDATOR.'/DR'.$vehicleInfo->vehicle_engine_id.'/0?api_key='.self::TECDOC_APIKEY.'';
			$vehicleArray = array('vehicle_make_id' => $vehicleInfo->vehicle_make_id, 'vehicle_make_label' => $vehicleInfo->vehicle_make_label, 'vehicle_model_id' => $vehicleInfo->vehicle_model_id, 'vehicle_model_label' => $vehicleInfo->vehicle_model_label, 'vehicle_engine_id' => $vehicleInfo->vehicle_engine_id, 'vehicle_engine_label' => $vehicleInfo->vehicle_engine_label, 'vehicle_image' => $vehicle_image, 'oil_per_litre' => $vehicleInfo->oil_per_litre, 'mapping_id' => $vehicleInfo->mapping_id);
		}
		return $vehicleArray;
	}
	
	public function getTechDocBattery()
    {
        /* Start Techdoc API */
		$request 			  = $this->_objectManager->get('Magento\Framework\App\Request\Http');  
        $menuId               = $request->getParam('menuid');
        $modelId              = $request->getParam('modelid');
        $carId                = $request->getParam('carid');
        $assemblyGroupNodeIds = $request->getParam('partscateid');
        if ((isset($menuId) && !empty($menuId)) && (isset($modelId) && !empty($modelId)) && (isset($carId) && !empty($carId)) && (isset($assemblyGroupNodeIds) && !empty($assemblyGroupNodeIds))) {

            $function = 'getArticles';
            $params   = array(
                'articleCountry'       => 'AE',
                'lang'                 => 'en',
                'linkageTargetType'    => 'P',
                'assemblyGroupNodeIds' => $assemblyGroupNodeIds,
                'linkageTargetId'      => $carId,
                'perPage'              => 1000,
                'provider'             => self::TECDOC_MANDATOR,
            );
            $apiResponse   = $this->getTechdocApiConnection($function, $params);
            $articleArray  = array();
            foreach ($apiResponse->articles as $item) {
                $articleArray[] = $item->articleNumber;
            }

            return $articleArray;
        }
        /* End Techdoc API */
    }
	
	public function getTechDocOilGrade()
    {
        /* Start Techdoc API */
        $request 			  = $this->_objectManager->get('Magento\Framework\App\Request\Http');  
        $menuId               = $request->getParam('menuid');
        $modelId              = $request->getParam('modelid');
        $carId                = $request->getParam('carid');
        $assemblyGroupNodeIds = $request->getParam('partscateid');
        if ((isset($menuId) && !empty($menuId)) && (isset($modelId) && !empty($modelId)) && (isset($carId) && !empty($carId)) && (isset($assemblyGroupNodeIds) && !empty($assemblyGroupNodeIds))) {

            $function = 'getArticles';
            $params   = array(
                'articleCountry'         => 'AE',
                'lang'                   => 'en',
                'linkageTargetType'      => 'P',
                'assemblyGroupNodeIds'   => $assemblyGroupNodeIds,
                'linkageTargetId'        => $carId,
                'perPage'                => 1000,
                'includeArticleCriteria' => true,
                'provider'               => self::TECDOC_MANDATOR,
            );
            $apiResponse   = $this->getTechdocApiConnection($function, $params);
            $oilGradeArray = array();
            $oilGradeOptionIds = array();
            foreach ($apiResponse->articles as $item) {
                //echo '<pre>';print_r($item->articleCriteria);
                foreach ($item->articleCriteria as $oilData) {
                    $criteriaId = $oilData->criteriaId;
                    if ($criteriaId != 2467) {
                        continue;
                    }
                    $oilGradeArray[] = $oilData->formattedValue;
                }

            }
            if (count($oilGradeArray) > 0) {
                $oilGradeArray = array_unique($oilGradeArray);
                //$oilGradeOptionIds = array_unique($oilGradeOptionIds);
            }
			
			/* $oilgradeCollection = $this->_objectManager->get('Hdweb\Oilservice\Model\OilgradeFactory')->create()->getCollection()
								  ->addFieldToSelect('oil_litre')
								  ->addFieldToFilter('make_id', ['eq' => $menuId])
								  ->addFieldToFilter('model_id', ['eq' => $modelId])
								  ->addFieldToFilter('engine_id', ['eq' => $carId])
								  ->getFirstItem();
			$oilGradeArray['oil_litre_collection'] = $oilgradeCollection->getData(); */
			//echo '<pre>';print_r($oilGradeArray);die;
           return $oilGradeArray;
        }
        /* End Techdoc API */
    }
	
	public function getArticlesbySearch($article_number)
    {
        /* Start Techdoc API */
        $function = 'getArticles';
        $params   = array(
            'articleCountry' => 'AE',
            'lang'           => 'en',
            'searchQuery'    => $article_number,
            'searchType'     => 10,
            'perPage'        => 1000,
            'provider'       => self::TECDOC_MANDATOR,
        );
        $apiResponse   = $this->getTechdocApiConnection($function, $params);
        $articleArray  = array();
        foreach ($apiResponse->articles as $item) {
            $articleArray[] = $item->articleNumber;
        }
        return $articleArray;
        /* End Techdoc API */
    }
	
	public function getBatteryRelatedProducts()
    {
		$storedVehicleData   = $this->getUserVehicleDetail();
	    $batteryArray = array();
		$productlistingHelper = $this->_objectManager->get('Hdweb\Tyrefinder\Helper\Productlisting');
		$batteryPageLink = $this->_storeManager->getStore()->getBaseUrl().'all-auto-parts/battery.html';
		$assemblyGroupNodeIds = '100042';
		$batteryCatId = '7';
		
		$productCollectionFactory =  $this->_objectManager->get('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
		$collection = $productCollectionFactory->create();
		$collection->addAttributeToSelect('price');
		$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
		$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
		$collection->addCategoriesFilter(['in' => $batteryCatId]);
		$collection->setOrder('price', 'DESC');
		
		if(count($storedVehicleData) > 0){
			$vehicle_make = $storedVehicleData['vehicle_make_id'];
			$vehicle_make_label = $storedVehicleData['vehicle_make_label'];
			$vehicle_model = $storedVehicleData['vehicle_model_id'];
			$vehicle_model_label = $storedVehicleData['vehicle_model_label'];
			$vehicle_engine = $storedVehicleData['vehicle_engine_id'];
			$vehicle_engine_label = $storedVehicleData['vehicle_engine_label'];  
			$vehicle_oil_litre = $storedVehicleData['oil_per_litre']; 
			$function = 'getArticles';
            $params   = array(
                'articleCountry'       => 'AE',
                'lang'                 => 'en',
                'linkageTargetType'    => 'P',
                'assemblyGroupNodeIds' => $assemblyGroupNodeIds,
                'linkageTargetId'      => $vehicle_engine,
                'perPage'              => 1000,
                'provider'             => self::TECDOC_MANDATOR,
            );
            $apiResponse   = $this->getTechdocApiConnection($function, $params);
            $techDocBatteryArticles  = array();
            foreach ($apiResponse->articles as $item) {
                $techDocBatteryArticles[] = $item->articleNumber;
            }
			
			$batteryPageLink = $batteryPageLink.'?menuid='.$vehicle_make.'&modelid='.$vehicle_model.'&carid='.$vehicle_engine.'&partscateid='.$assemblyGroupNodeIds.'&categoryid='.$batteryCatId.'';
			
			
			if (count($techDocBatteryArticles) > 0) {
				$collection->addAttributeToFilter('article_number', ['in' => $techDocBatteryArticles]);
				if(count($collection->getData()) > 0){
					$maxPrice = $collection->getMaxPrice();
					$minPrice = $collection->getMinPrice();
					$vatMinPrice = $productlistingHelper->getVatIncPrice($minPrice);
					$vatMaxPrice = $productlistingHelper->getVatIncPrice($maxPrice);
					$batteryArray = array(
									'min_price' => $minPrice,
									'max_price' => $maxPrice,
									'vat_min_price' => $vatMinPrice,
									'vat_max_price' => $vatMaxPrice,
									'view_link' => $batteryPageLink
									);
				}else{
					$collectionClone = clone $collection;
					$collection->clear();
					$collection = $productCollectionFactory->create();
					$collection->addAttributeToSelect('price');
					$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
					$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
					$collection->addCategoriesFilter(['in' => $batteryCatId]);
					$collection->setOrder('price', 'DESC');
					
					$maxPrice = $collection->getMaxPrice();
					$minPrice = $collection->getMinPrice();
					$vatMinPrice = $productlistingHelper->getVatIncPrice($minPrice);
					$vatMaxPrice = $productlistingHelper->getVatIncPrice($maxPrice);
					$batteryArray = array(
									'min_price' => $minPrice,
									'max_price' => $maxPrice,
									'vat_min_price' => $vatMinPrice,
									'vat_max_price' => $vatMaxPrice,
									'view_link' => $batteryPageLink
									);
				}
			}else{
				$maxPrice = $collection->getMaxPrice();
				$minPrice = $collection->getMinPrice();
				$vatMinPrice = $productlistingHelper->getVatIncPrice($minPrice);
				$vatMaxPrice = $productlistingHelper->getVatIncPrice($maxPrice);
				$batteryArray = array(
								'min_price' => $minPrice,
								'max_price' => $maxPrice,
								'vat_min_price' => $vatMinPrice,
								'vat_max_price' => $vatMaxPrice,
								'view_link' => $batteryPageLink
								);
			}
		}else{
				$maxPrice = $collection->getMaxPrice();
				$minPrice = $collection->getMinPrice();
				$vatMinPrice = $productlistingHelper->getVatIncPrice($minPrice);
				$vatMaxPrice = $productlistingHelper->getVatIncPrice($maxPrice);
				$batteryArray = array(
								'min_price' => $minPrice,
								'max_price' => $maxPrice,
								'vat_min_price' => $vatMinPrice,
								'vat_max_price' => $vatMaxPrice,
								'view_link' => $batteryPageLink
								);
		}
		
		return $batteryArray;
    }
}
