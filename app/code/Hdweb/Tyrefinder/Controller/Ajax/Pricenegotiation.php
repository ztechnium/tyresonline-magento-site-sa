<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Session\SessionManagerInterface;

class Pricenegotiation extends \Magento\Framework\App\Action\Action
{	
	
	protected $resultJsonFactory;
	protected $productCollectionFactory;
	protected $productFactory;
	public $scopeConfig;
	public $tyrefinderListingHelper;
	
    public function __construct(\Magento\Framework\App\Action\Context $context,
    	\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Catalog\Model\ProductFactory $productFactory,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Hdweb\Tyrefinder\Helper\Productlisting $tyrefinderListingHelper
	) {
    	$this->resultJsonFactory = $resultJsonFactory;
		$this->productCollectionFactory = $productCollectionFactory;
		$this->productFactory = $productFactory;
		$this->scopeConfig = $scopeConfig;
		$this->tyrefinderListingHelper = $tyrefinderListingHelper;
		
        parent::__construct($context);
    }
    
    public function execute()
    {	
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    	$productId = $this->getRequest()->getParam('id');
    	$product = $this->productFactory->create()->load($productId);
    	$set1price = $this->tyrefinderListingHelper->getSet1price($product);
		$nibbleAPIKey = $this->scopeConfig->getValue('hdwebapi/general/nibble_apikey');
		$nibbleAPISecretKey = $this->scopeConfig->getValue('hdwebapi/general/nibble_api_secretkey');
		$nibbleAPIObjective = $this->scopeConfig->getValue('hdwebapi/general/nibble_api_objective');
		$objective = "increase_conversion";
		$sessionManager = $objectManager->get(SessionManagerInterface::class);
		$sessionId = $sessionManager->getSessionId();
		if($nibbleAPIObjective){
			$objective = $nibbleAPIObjective;
		}
    	if($product->getNegotiateWalkAwayPrice()){
    		$productWalkAwayPrice = $product->getNegotiateWalkAwayPrice();
    		$productWalkAwayPricePlus5Per = $productWalkAwayPrice + ($productWalkAwayPrice * 0.05);
    		if($productWalkAwayPricePlus5Per < $set1price){
    			$walkawayPrice = round($productWalkAwayPricePlus5Per);
    		} else {
    			$set1priceMinus2Per = $set1price * ((100-2) / 100);
	    		$walkawayPrice = round($set1priceMinus2Per);
    		}
    	} else {
	    	$set1priceMinus2Per = $set1price * ((100-2) / 100);
	    	$walkawayPrice = round($set1priceMinus2Per);
    	}
    	
    	$productDesc = $product->getName();
    	$timestamp = mt_rand(1, time());
		$randomDate = date("d M Y", $timestamp);
		//$randomSession = $randomDate . $productId;
		//$randomSession = $sessionId.$productId;
		$randomSession = $sessionId;
    	$curl = curl_init();
    	$postData = [
    				"retailerSessionId" => $randomSession,
				    "currencyCode" => "AED",
				    "productId" => $productId,
				    "productName" => $productDesc,
				    "subProductId" => $productId,
					"subProductName" => $productDesc,
					"productPrice" => $set1price,
					"walkawayPrice" => (string)$walkawayPrice,
					"objective" => $objective,
					"quantity" => 1,
					"maxQuantity" => 5,
					"negotiationType" => "product"
				];

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://api.nibble.website/v1/session',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>json_encode($postData),
		  CURLOPT_HTTPHEADER => array(
		    'X-Api-Key: '.$nibbleAPIKey.'',
		    'X-Nibble-Api-Secret: '.$nibbleAPISecretKey.'',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);

		header('Content-Type: application/json;');
		echo $response;
    }
}

