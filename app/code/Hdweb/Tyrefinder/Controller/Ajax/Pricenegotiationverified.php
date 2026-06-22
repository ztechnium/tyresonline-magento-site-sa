<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;

use Magento\Store\Model\ScopeInterface;

class Pricenegotiationverified extends \Magento\Framework\App\Action\Action
{	
	
	protected $resultJsonFactory;
	protected $productCollectionFactory;
	protected $productFactory;
	public $scopeConfig;
	
    public function __construct(\Magento\Framework\App\Action\Context $context,
    	\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Catalog\Model\ProductFactory $productFactory,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	) {
    	$this->resultJsonFactory = $resultJsonFactory;
		$this->productCollectionFactory = $productCollectionFactory;
		$this->productFactory = $productFactory;
		$this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }
    
    public function execute()
    {	
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$curl = curl_init();
		$nibbleId = $this->getRequest()->getParam('id');
		$nibbleAPIKey = $this->scopeConfig->getValue('hdwebapi/general/nibble_apikey');
		$nibbleAPISecretKey = $this->scopeConfig->getValue('hdwebapi/general/nibble_api_secretkey');
    	curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.nibble.website/v1/session/' . $nibbleId,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'X-Api-Key: '.$nibbleAPIKey.'',
		    'X-Nibble-Api-Secret: '.$nibbleAPISecretKey.'',
            'Content-Type: application/json'
          ),
        ));

		$response = curl_exec($curl);

		curl_close($curl);

		header('Content-Type: application/json;');
		

    
    $responsData = json_decode($response);
    $productId = $responsData->productId;
    $product = $this->productFactory->create()->load($productId);
    $sku = $product->getSku();
    $originalPrice = $responsData->originalPrice;
    $negotiatedPrice = $responsData->negotiatedPrice;
    $priceDiff = $originalPrice - $negotiatedPrice;
    
    $additionalProductId = '';
    if(count($responsData->additionalProducts) > 0){
        $additionalProductId = $responsData->additionalProducts[0]->productId;
    }
    
    
    $coupon['name'] = 'NPN - ' . uniqid();
    $coupon['desc'] = 'negotiated discount';
    $coupon['start'] = date('Y-m-d');
    $coupon['end'] = date('Y-m-d', strtotime("+1 day"));
    $coupon['max_redemptions'] = 1;
    $coupon['discount_type'] = 'cart_fixed';
    $coupon['discount_amount'] = $priceDiff * 0.95238;
    $coupon['product_ids'] = $productId;
    //$coupon['minimum_amount'] = $couponData['general']['minimum_amount'];
    $coupon['flag_is_free_shipping'] = 'no';
    $coupon['redemptions'] = 1;
    $coupon['code'] = 'npn-' . uniqid();

    $mobilevanService = 0;
    if($additionalProductId && $additionalProductId == 5156){
        $coupon['discount_amount'] = $coupon['discount_amount'] + 71.393; // (142.786 / 2) mobile van 50% off
        $mobilevanService = 1;
    }
   

    $shoppingCartPriceRule = $objectManager->create('Magento\SalesRule\Model\Rule');
    
    $shoppingCartPriceRule->setName($coupon['name'])
            ->setDescription($coupon['desc'])
            ->setFromDate($coupon['start'])
            ->setToDate($coupon['end'])
            ->setUsesPerCustomer($coupon['max_redemptions'])
            ->setCustomerGroupIds(array('0', '1', '2', '3',))
            ->setIsActive(1)
            ->setSimpleAction($coupon['discount_type'])
            ->setDiscountAmount($coupon['discount_amount'])
            ->setDiscountQty(1)
            ->setApplyToShipping($coupon['flag_is_free_shipping'])
            ->setTimesUsed($coupon['redemptions'])
            ->setWebsiteIds(array('1'))
            ->setProductIds($coupon['product_ids'])
            ->setCouponType(2)
            ->setCouponCode($coupon['code'])
            ->setUsesPerCoupon(1)
            ->setMobilevanDiscount($mobilevanService)
            ->setStopRulesProcessing(1);

    $conditions = array();
    $conditions["1"] = array
        (
        "type" => "Magento\SalesRule\Model\Rule\Condition\Combine",
        "aggregator" => "all",
        "attribute" => null,
        "operator" => null,
        "value" => 1,
        "is_value_processed" => null,
    );
    $conditions["1--1"] = array
        (
        "type" => "Magento\SalesRule\Model\Rule\Condition\Product\Found",
        "attribute" => null,
        "operator" => null,
        "value" => 1,
        "is_value_processed" => null,
        "aggregator" => "all",
    );
    $conditions["1--1--1"] = array
        (
        "type" => "Magento\SalesRule\Model\Rule\Condition\Product",
        "attribute" => "sku",
        "operator" => "()",
        "value" => $sku
    );
    $conditions["1--1--1-1"] = array
        (
        "type" => "Magento\SalesRule\Model\Rule\Condition\Product",
        "attribute" => "quote_item_qty",
        "operator" => ">=",
        "value" => $responsData->quantity
    );
    $shoppingCartPriceRule->setData('conditions', $conditions);

    // Validating rule data before Saving
    $validateResult = $shoppingCartPriceRule->validateData(new \Magento\Framework\DataObject($shoppingCartPriceRule->getData()));
    if ($validateResult !== true) {
        foreach ($validateResult as $errorMessage) {
            echo $errorMessage;
        }
        return;
    }

    try {
        $shoppingCartPriceRule->loadPost($shoppingCartPriceRule->getData());
        $shoppingCartPriceRule->save();

        $ruleJob = $objectManager->get('Magento\CatalogRule\Model\Rule\Job');
        $ruleJob->applyAll();
       // echo "rule created";
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    
    $response = json_decode($response);
    $response->{"couponCode"} = $coupon['code'];
    $response = json_encode($response);
    echo $response;

    }
}

