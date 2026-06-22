<?php
namespace Hdweb\Addattribute\Helper;

class Productdetails extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $ruleFactory;
    protected $catalogruleFactory;
    protected $productFactory;
    protected $productCollectionFactory;
    protected $_storeManager;
    protected $_customerSession;
    //protected $_brandFactory;
    protected $request;
    protected $_priceHelper;
    protected $_resource;
    protected $scopeConfig;
    

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\CatalogRule\Model\RuleFactory $catalogruleFactory,
        \Magento\Catalog\Model\Product $productFactory,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Framework\HTTP\PhpEnvironment\Request $request,
        //\Mageplaza\Shopbybrand\Model\BrandFactory $brandFactory,
        \Magento\Framework\Serialize\Serializer\Json $serialize,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
        ) {
        $this->ruleFactory = $ruleFactory;
        $this->catalogruleFactory = $catalogruleFactory;
        $this->productFactory = $productFactory;
        $this->_productloader = $_productloader;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        $this->date = $date;
        //$this->_brandFactory = $brandFactory;
        $this->request = $request;
        $this->_priceHelper = $priceHelper;
        $this->serialize = $serialize;
        $this->_resource = $resource;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }    

    public function getCurrencySymbol() {
        return $this ->_storeManager-> getStore()->getBaseCurrency()->getCurrencySymbol();
    }

    public function getAttributeValue($_product, $_attribute){
        $_attributeId = $_product->getData($_attribute);
        $attr = $_product->getResource()->getAttribute($_attribute);
        if ($attr->usesSource()) {
            $attributeValue = $attr->getSource()->getOptionText($_attributeId);
        }
        return $attributeValue;
    }

    public function getTyreShortParams($_product, $long = NULL){
        $productAttributes = '';

        $width = $this->getAttributeValue($_product,'width');
        $profile = $this->getAttributeValue($_product,'profile');
        $construction = $this->getAttributeValue($_product,'construction');
        $diameter = $this->getAttributeValue($_product,'diameter');
        $vehicle_marking = $_product->getData('vehicle_marking');

        if ($this->getAttributeValue($_product,'width')) {
            if($profile =='None' || $profile ==''){
                $productAttributes =  $width . ' ' . $construction . $diameter .  $vehicle_marking;
            }else{
                $productAttributes =  $width . '/' . $profile . ' ' . $construction . $diameter .  $vehicle_marking;
            }    
        } elseif ($this->getAttributeValue($_product,'width')) {
            $productAttributes =  $this->getAttributeValue($_product,'width') . '/' . $this->getAttributeValue($_product,'profile') . ' ' . $this->getAttributeValue($_product,'construction') . $this->getAttributeValue($_product,'diameter');
        }

        if ($long) {
            $productAttributes =  $productAttributes . ' ' . $_product->getLoadIndex()
                . $this->getAttributeValue($_product,'speed_index') . ' ' . $_product->getLoadRange();

            $productAttributes = str_replace($_product->getData('vehicle_marking'), '', $productAttributes) . '&nbsp;' . $_product->getData('vehicle_marking');
        }

        return $productAttributes;
    }


    public function getProductSize($_product, $long = NULL){
        $productAttributes = '';
        $profile ='';
        $profile =$this->getAttributeValue($_product,'height');

        if($profile =='None' || $profile ==''){
            $productAttributes =  $this->getAttributeValue($_product,'width') . ' ' . $this->getAttributeValue($_product,'construction') . $this->getAttributeValue($_product,'rim') . $_product->getData('vehicle_marking');
        }else{
            $productAttributes =  $this->getAttributeValue($_product,'width') . '/' . $this->getAttributeValue($_product,'height') . ' ' . $this->getAttributeValue($_product,'construction') . $this->getAttributeValue($_product,'rim') . $_product->getData('vehicle_marking');
        }  
        return $productAttributes;
    }

    public function getProductIndex($_product, $long = NULL){
       $product_data = $this->_productloader->create()->load($_product->getId());

        $productAttributes = '';
        $productAttributes =  $product_data->getResource()->getAttribute('load_index')->getFrontend()->getValue($product_data) . $product_data->getResource()->getAttribute('speed_index')->getFrontend()->getValue($product_data);

        return $productAttributes;
    }

     public function getProductBrand($_product, $long = NULL){
       $product_data = $this->_productloader->create()->load($_product->getId());
        $productAttributes = '';
        $productAttributes =  $product_data->getResource()->getAttribute('brand')->getFrontend()->getValue($product_data);
        return strtoupper($productAttributes);
    }


    public function getStoreManager(){
        return $this->_storeManager;
    }

    public function getCustomerGroupId(){
        return $this->_customerSession->getCustomer()->getGroupId();
    }

    public function getCurrentWebsiteId(){
        return $this->_storeManager->getStore()->getWebsiteId();
    }

    public function getBrandDetails($manufacturerId){
        $brands = $this->_brandFactory->create()->load($manufacturerId,'option_id');
        return $brands;
    }

    public function checkRuleId($_product){

        //Display Special offer image on listing page by Parth Shah
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
        $store = $objectManager->get('Magento\Framework\Locale\Resolver');
        $_rules = $this->ruleFactory->create()->getCollection()->addFieldToFilter('coupon_type',1);
        $_catalogrules = $this->catalogruleFactory->create()->getCollection();
        $_currentTime = strtotime($this->date->date());
        $queryString = $this->request->getServer('QUERY_STRING');
        if($queryString != null || $queryString != ""){
            $queryStringArr = explode('=', $queryString);
            $ruleQuery = $queryStringArr[0];
            if( isset($queryStringArr[1])) {
            $ruleId = $queryStringArr[1];            
            
            if($ruleQuery == 'salesrule_id'){
                $rule = $objectManager->create('Magento\SalesRule\Model\Rule')->load($ruleId);
                if($rule->getIsActive() == 1){
                    if(in_array($this->getCurrentWebsiteId(), $rule->getData('website_ids'))){
                        if(in_array($this->getCustomerGroupId(), $rule->getData('customer_group_ids'))){
                            $fromDate = $rule->getFromDate();
                            $toDate = $rule->getToDate();
                            if (isset($fromDate) && $_currentTime >= strtotime($fromDate)) {
                                if (isset($toDate)) {
                                    if (strtotime($toDate) >= $_currentTime) {
                                        $rule_data = $this->serialize->unserialize($rule->getActionsSerialized());
                                        if(array_key_exists("conditions",$rule_data)){
                                            $sku = explode(",", $rule_data['conditions']['0']['value']);
                                            $sku =array_map('trim',$sku);
                                            if (in_array($_product->getSku(), $sku))
                                            {
                                                $ruleName = $rule->getName();
                                                $color_text = $rule->getColorText();
                                                
                                                if($store->getLocale() == "ar_SA")
                                                {   
                                                    if($rule->getRtlOfferImage())
                                                        $ruleLabel = "salesrule/offerimage/".$rule->getRtlOfferImage();
                                                    else
                                                        $ruleLabel = null;
                                                }
                                                else
                                                {
                                                    if($rule->getOfferImage())
                                                        $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();    
                                                    else
                                                        $ruleLabel = null;
													
													if($rule->getRuleBanner())
														$ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
													else
														$ruletabLabel = null;
													
													if($rule->getBundleRuleBanner())
														$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
													else
														$rulebundleLabel = null;
													if($rule->getRtlRuleBanner())
														$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
													else
														$ruleRtlbannerLabel = null;
																									 	
                                                }
                                                
                                                return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                            } 
                                        }else{
                                            $rule_data = $this->serialize->unserialize($rule->getConditionsSerialized());
                                            if(array_key_exists("conditions",$rule_data)){
                                                $ruleD = $rule_data['conditions']['0'];
                                                if(array_key_exists("conditions",$ruleD)){
                                                    $ruleData = $ruleD['conditions'];
                                                    $ruleDataMain = $ruleData[0];
                                                    $sku = explode(",", $ruleDataMain['value']);
                                                    $sku =array_map('trim',$sku);
                                                    if (in_array($_product->getSku(), $sku))
                                                    {
                                                        $ruleName = $rule->getName();
                                                        $color_text = $rule->getColorText();

                                                        if($store->getLocale() == "ar_SA")
                                                        {
                                                            if($rule->getRtlOfferImage())
                                                                $ruleLabel = "salesrule/offerimage/".$rule->getRtlOfferImage();
                                                            else
                                                                $ruleLabel = null;
                                                        }
                                                        else
                                                        {
                                                            if($rule->getOfferImage())
                                                                $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();    
                                                            else
                                                                $ruleLabel = null;
															
															if($rule->getRuleBanner())
																$ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
															else
																$ruletabLabel = null;
															
															if($rule->getBundleRuleBanner())
																$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
															else
																$rulebundleLabel = null;
															if($rule->getRtlRuleBanner())
																$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
															else
																$ruleRtlbannerLabel = null;
                                                        }
                                                        return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                                    }
                                                }
                                            }   
                                        }
                                    }
                                }
                                else
                                {
                                    $rule_data = $this->serialize->unserialize($rule->getActionsSerialized());
                                    if(array_key_exists("conditions",$rule_data)){
                                        $sku = explode(",", $rule_data['conditions']['0']['value']);
                                        $sku =array_map('trim',$sku);
                                        if (in_array($_product->getSku(), $sku))
                                        {
                                            $ruleName = $rule->getName();
                                            $color_text = $rule->getColorText();

                                            if($rule->getOfferImage())
                                                $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();
                                            else
                                                $ruleLabel = null;
											
											if($rule->getRuleBanner())
												$ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
											else
												$ruletabLabel = null;
											
											if($rule->getBundleRuleBanner())
												$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
											else
												$rulebundleLabel = null;
											
											if($rule->getRtlRuleBanner())
												$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
											else
												$ruleRtlbannerLabel = null;

                                            return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);

                                        }
                                    }else{
                                        $rule_data = $this->serialize->unserialize($rule->getConditionsSerialized());
                                        if(array_key_exists("conditions",$rule_data)){
                                            $ruleD = $rule_data['conditions']['0'];
                                            if(array_key_exists("conditions",$ruleD)){
                                                $ruleData = $ruleD['conditions'];
                                                $ruleDataMain = $ruleData[0];
                                                $sku = explode(",", $ruleDataMain['value']);
                                                $sku =array_map('trim',$sku);
                                                if (in_array($_product->getSku(), $sku))
                                                {
                                                    $ruleName = $rule->getName();
                                                    $color_text = $rule->getColorText();

                                                    if($store->getLocale() == "ar_SA")
                                                    {
                                                        if($rule->getRtlOfferImage())
                                                            $ruleLabel = "salesrule/offerimage/".$rule->getRtlOfferImage();
                                                        else
                                                            $ruleLabel = null;
                                                    }
                                                    else
                                                    {
                                                        if($rule->getOfferImage())
                                                            $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();    
                                                        else
                                                            $ruleLabel = null;
														
														if($rule->getRuleBanner())
															$ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
														else
															$ruletabLabel = null;
														
														if($rule->getBundleRuleBanner())
															$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
														else
															$rulebundleLabel = null;
														if($rule->getRtlRuleBanner())
															$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
														else
															$ruleRtlbannerLabel = null;
                                                    }
                                                    return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                                }
                                            }
                                        }   
                                    }
                                }
                            }
                        }
                    }
                }

            }elseif($ruleQuery == 'catalogrule_id'){
                $rule = $objectManager->create('Magento\CatalogRule\Model\Rule')->load($ruleId);
                if($rule->getIsActive() == 1){
                    if(in_array($this->getCurrentWebsiteId(), $rule->getData('website_ids'))){
                        if(in_array($this->getCustomerGroupId(), $rule->getData('customer_group_ids'))){
                            $fromDate = $rule->getFromDate();
                            $toDate = $rule->getToDate();
                            if (isset($fromDate) && $_currentTime >= strtotime($fromDate)) {
                                if (isset($toDate)) {
                                    if (strtotime($toDate) >= $_currentTime) {

                                        $unSerconditions = $this->serialize->unserialize($rule->getConditionsSerialized());
                                        if(array_key_exists("conditions",$unSerconditions)){
                                            $ruleConditions = $unSerconditions['conditions'];
                                            $ruleCondtionDetailArray = $ruleConditions[0];
                                            $attribute = $ruleCondtionDetailArray['attribute'];
                                            $operator = $ruleCondtionDetailArray['operator'];
                                            $value = $ruleCondtionDetailArray['value'];
                                            $valueArray = explode(', ', $value);

                                            if($attribute == 'category_ids'){
                                                $productCategories = $_product->getCategoryIds();
                                                if(count(array_intersect($productCategories, $valueArray)) != 0){
                                                    $ruleName = $rule->getName();
                                                    $color_text = $rule->getColorText();

                                                    if($store->getLocale() == "ar_SA")
                                                    {   
                                                        if($rule->getRtlOfferImage())
                                                            $ruleLabel = "catalogrule/offerimage/".$rule->getRtlOfferImage();
                                                        else
                                                            $ruleLabel = null;
                                                    }
                                                    else
                                                    {
                                                        if($rule->getOfferImage())
                                                            $ruleLabel = "catalogrule/offerimage/".$rule->getOfferImage();    
                                                        else
                                                            $ruleLabel = null;
														
														if($rule->getRuleBanner())
															$ruletabLabel = "catalogrule/offerimage/".$rule->getRuleBanner();    
														else
															$ruletabLabel = null;
														
														if($rule->getBundleRuleBanner())
															$rulebundleLabel = "catalogrule/offerimage/".$rule->getBundleRuleBanner();    
														else
															$rulebundleLabel = null;
														if($rule->getRtlRuleBanner())
															$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
														else
															$ruleRtlbannerLabel = null;
                                                    }
                                                 return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                                }
                                            }else{
                                                $productAttribute = $_product->getData($attribute);
                                                if(in_array($productAttribute, $valueArray)){
                                                    $ruleName = $rule->getName();
                                                    $color_text = $rule->getColorText();


                                                    if($rule->getOfferImage())
                                                        $ruleLabel = "catalogrule/offerimage/".$rule->getOfferImage();
                                                    else
                                                        $ruleLabel = null;
													
													if($rule->getRuleBanner())
															$ruletabLabel = "catalogrule/offerimage/".$rule->getRuleBanner();    
														else
															$ruletabLabel = null;
														
													if($rule->getBundleRuleBanner())
															$rulebundleLabel = "catalogrule/offerimage/".$rule->getBundleRuleBanner();    
														else
															$rulebundleLabel = null;
														
													if($rule->getRtlRuleBanner())
															$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
														else
															$ruleRtlbannerLabel = null;		

                                                    return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                                }                            
                                            }
                                        }
                                    }
                                }
                            }else{
                                $unSerconditions = $this->serialize->unserialize($rule->getConditionsSerialized());
                                if(array_key_exists("conditions",$unSerconditions)){
                                    $ruleConditions = $unSerconditions['conditions'];
                                    $ruleCondtionDetailArray = $ruleConditions[0];
                                    $attribute = $ruleCondtionDetailArray['attribute'];
                                    $operator = $ruleCondtionDetailArray['operator'];
                                    $value = $ruleCondtionDetailArray['value'];
                                    $valueArray = explode(', ', $value);

                                    if($attribute == 'category_ids'){
                                        $productCategories = $_product->getCategoryIds();
                                        if(count(array_intersect($productCategories, $valueArray)) != 0){
                                            $ruleName = $rule->getName();
                                            $color_text = $rule->getColorText();
                                            if($store->getLocale() == "ar_SA")
                                            {   
                                                if($rule->getRtlOfferImage())
                                                    $ruleLabel = "catalogrule/offerimage/".$rule->getRtlOfferImage();
                                                else
                                                    $ruleLabel = null;
                                            }
                                            else
                                            {
                                                if($rule->getOfferImage())
                                                    $ruleLabel = "catalogrule/offerimage/".$rule->getOfferImage();    
                                                else
                                                    $ruleLabel = null;
												
												if($rule->getRuleBanner())
													$ruletabLabel = "catalogrule/offerimage/".$rule->getRuleBanner();    
												else
													$ruletabLabel = null;
												
												if($rule->getBundleRuleBanner())
													$rulebundleLabel = "catalogrule/offerimage/".$rule->getBundleRuleBanner();    
												else
													$rulebundleLabel = null;
												if($rule->getRtlRuleBanner())
													$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
												else
													$ruleRtlbannerLabel = null;	
                                            }
                                            return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                        }
                                    }else{
                                        $productAttribute = $_product->getData($attribute);
                                        if(in_array($productAttribute, $valueArray)){
                                            $ruleName = $rule->getName();
                                            $color_text = $rule->getColorText();

                                            if($store->getLocale() == "ar_SA")
                                            {   
                                                if($rule->getRtlOfferImage())
                                                    $ruleLabel = "catalogrule/offerimage/".$rule->getRtlOfferImage();
                                                else
                                                    $ruleLabel = null;
                                            }
                                            else
                                            {
                                                if($rule->getOfferImage())
                                                    $ruleLabel = "catalogrule/offerimage/".$rule->getOfferImage();    
                                                else
                                                    $ruleLabel = null;
												
												if($rule->getRuleBanner())
													$ruletabLabel = "catalogrule/offerimage/".$rule->getRuleBanner();    
												else
													$ruletabLabel = null;
												
												if($rule->getBundleRuleBanner())
													$rulebundleLabel = "catalogrule/offerimage/".$rule->getBundleRuleBanner();    
												else
													$rulebundleLabel = null;
												if($rule->getRtlRuleBanner())
													$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
												else
													$ruleRtlbannerLabel = null;
                                            }
                                            return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

            }
            else
            {
                foreach($_rules as $rule){
                    $fromDate = $rule->getFromDate();
                    $toDate = $rule->getToDate();
                    if (isset($fromDate) && $_currentTime >= strtotime($fromDate)) {
                        if (isset($toDate)) {
                            if (strtotime($toDate) >= $_currentTime) {
                                $rule_data = $this->serialize->unserialize($rule->getActionsSerialized());
                                if(array_key_exists("conditions",$rule_data)){
                                    $sku = explode(",", $rule_data['conditions']['0']['value']);
                                    $sku =array_map('trim',$sku);
                                    if (in_array($_product->getSku(), $sku))
                                    {
                                        $ruleName = $rule->getName();
                                        $ruleId = $rule->getId();
                                        $color_text = $rule->getColorText();

                                        if($store->getLocale() == "ar_SA")
                                        {
                                            if($rule->getRtlOfferImage())
                                                $ruleLabel = "salesrule/offerimage/".$rule->getRtlOfferImage();
                                            else
                                                $ruleLabel = null;
                                        }
                                        else
                                        {
                                            if($rule->getOfferImage())
                                                $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();    
                                            else
                                                $ruleLabel = null;
											
											if($rule->getRuleBanner())
                                                $ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
                                            else
                                                $ruletabLabel = null;
											
											if($rule->getBundleRuleBanner())
												$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
											else
												$rulebundleLabel = null;
											
											if($rule->getRtlRuleBanner())
												$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
											else
												$ruleRtlbannerLabel = null;
											
                                        }
                                        return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                    }
                                }
                            }
                        }
                        else
                        {
                            $rule_data = $this->serialize->unserialize($rule->getActionsSerialized());
                            if(array_key_exists("conditions",$rule_data)){
                                $sku = explode(",", $rule_data['conditions']['0']['value']);
                                $sku =array_map('trim',$sku);
                                if (in_array($_product->getSku(), $sku))
                                {
                                    $ruleName = $rule->getName();
                                    $ruleId = $rule->getId();
                                    $color_text = $rule->getColorText();
                                    if($store->getLocale() == "ar_SA")
                                    {
                                        if($rule->getRtlOfferImage())
                                            $ruleLabel = "salesrule/offerimage/".$rule->getRtlOfferImage();
                                        else
                                            $ruleLabel = null;
                                    }
                                    else
                                    {
                                        if($rule->getOfferImage())
                                            $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();    
                                        else
                                            $ruleLabel = null;
										
										if($rule->getRuleBanner())
											$ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
										else
											$ruletabLabel = null;
										if($rule->getBundleRuleBanner())
											$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
										else
											$rulebundleLabel = null;
										
										if($rule->getRtlRuleBanner())
												$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
											else
												$ruleRtlbannerLabel = null;
                                    }
                                    return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                }
                            }
                        }
                    }
                }
            }
         }
        }
        else
        {
            foreach($_rules as $rule){
                if($rule->getIsActive() == 1){
                    if(in_array($this->getCurrentWebsiteId(), $rule->getData('website_ids'))){
                        if(in_array($this->getCustomerGroupId(), $rule->getData('customer_group_ids'))){
                            $fromDate = $rule->getFromDate();
                            $toDate = $rule->getToDate();
                            if (isset($fromDate) && $_currentTime >= strtotime($fromDate)) {
                                if (isset($toDate)) {
                                    if (strtotime($toDate) >= $_currentTime) {
                                        $rule_data = $this->serialize->unserialize($rule->getActionsSerialized());
                                        if(array_key_exists("conditions",$rule_data)){
                                            $sku = explode(",", $rule_data['conditions']['0']['value']);
                                            $sku =array_map('trim',$sku);
                                            if (in_array($_product->getSku(), $sku))
                                            {
                                                $ruleName = $rule->getName();
                                                $ruleId = $rule->getId();
                                                $color_text = $rule->getColorText();
                                                if($store->getLocale() == "ar_SA")
                                                {
                                                    if($rule->getRtlOfferImage())
                                                        $ruleLabel = "salesrule/offerimage/".$rule->getRtlOfferImage();
                                                    else
                                                        $ruleLabel = null;
                                                }
                                                else
                                                {
                                                    if($rule->getOfferImage())
                                                        $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();    
                                                    else
                                                        $ruleLabel = null;
													
													if($rule->getRuleBanner())
														$ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
													else
														$ruletabLabel = null;
													
													if($rule->getBundleRuleBanner())
														$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
													else
														$rulebundleLabel = null;
													
													if($rule->getRtlRuleBanner())
														$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
													else
														$ruleRtlbannerLabel = null;
                                                }
                                                
                                                return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                            } 
                                        }else{
                                            $rule_data = $this->serialize->unserialize($rule->getConditionsSerialized());
                                            if(array_key_exists("conditions",$rule_data)){
                                                $ruleD = $rule_data['conditions']['0'];
                                                if(array_key_exists("conditions",$ruleD)){
                                                    $ruleData = $ruleD['conditions'];
                                                    $ruleDataMain = $ruleData[0];
                                                    $sku = explode(",", $ruleDataMain['value']);
                                                    $sku =array_map('trim',$sku);
                                                    if (in_array($_product->getSku(), $sku))
                                                    {
                                                        $ruleName = $rule->getName();
                                                        $ruleId = $rule->getId();
                                                        $color_text = $rule->getColorText();

                                                        if($store->getLocale() == "ar_SA")
                                                        {
                                                            if($rule->getRtlOfferImage())
                                                                $ruleLabel = "salesrule/offerimage/".$rule->getRtlOfferImage();
                                                            else
                                                                $ruleLabel = null;
                                                        }
                                                        else
                                                        {
                                                            if($rule->getOfferImage())
                                                                $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();    
                                                            else
                                                                $ruleLabel = null;
															
															if($rule->getRuleBanner())
																$ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
															else
																$ruletabLabel = null;
															
															if($rule->getBundleRuleBanner())
																$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
															else
																$rulebundleLabel = null;
															
															if($rule->getRtlRuleBanner())
																$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
															else
																$ruleRtlbannerLabel = null;
                                                        }
                                                 
                                                    return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                                    }
                                                }
                                            }   
                                        }
                                    }
                                }
                                else
                                {
                                    $rule_data =$this->serialize->unserialize($rule->getActionsSerialized());
                                    if(array_key_exists("conditions",$rule_data)){
                                        $sku = explode(",", $rule_data['conditions']['0']['value']);
                                        $sku =array_map('trim',$sku);
                                        if (in_array($_product->getSku(), $sku))
                                        {
                                            $ruleName = $rule->getName();
                                            $ruleId = $rule->getId();
                                            $color_text = $rule->getColorText();

                                            if($rule->getOfferImage())
                                                $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();
                                            else
                                                $ruleLabel = null;
											
											if($rule->getRuleBanner())
												$ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
											else
												$ruletabLabel = null;
											
											if($rule->getBundleRuleBanner())
												$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
											else
												$rulebundleLabel = null;
											
											if($rule->getRtlRuleBanner())
												$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
											else
												$ruleRtlbannerLabel = null;

                                            return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                        }
                                    }else{
                                            $rule_data = $this->serialize->unserialize($rule->getConditionsSerialized());
                                            if(array_key_exists("conditions",$rule_data)){
                                                $ruleD = $rule_data['conditions']['0'];
                                                if(array_key_exists("conditions",$ruleD)){
                                                    $ruleData = $ruleD['conditions'];
                                                    $ruleDataMain = $ruleData[0];
                                                    $sku = explode(",", $ruleDataMain['value']);
                                                    $sku =array_map('trim',$sku);
                                                    if (in_array($_product->getSku(), $sku))
                                                    {
                                                        $ruleName = $rule->getName();
                                                        $ruleId = $rule->getId();
                                                        $color_text = $rule->getColorText();

                                                        if($store->getLocale() == "ar_SA")
                                                        {
                                                            if($rule->getRtlOfferImage())
                                                                $ruleLabel = "salesrule/offerimage/".$rule->getRtlOfferImage();
                                                            else
                                                                $ruleLabel = null;
                                                        }
                                                        else
                                                        {
                                                            if($rule->getOfferImage())
                                                                $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();    
                                                            else
                                                                $ruleLabel = null;
															
															if($rule->getRuleBanner())
															$ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
															else
															$ruletabLabel = null;
															
															if($rule->getBundleRuleBanner())
																$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
															else
																$rulebundleLabel = null;
															if($rule->getRtlRuleBanner())
																$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
															else
																$ruleRtlbannerLabel = null;
                                                        }
                                                        return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                                    }
                                                }
                                            }   
                                        }
                                }
                            }
                        }
                    }
                }
            }

            foreach ($_catalogrules as $rule) {

                if($rule->getIsActive() == 1){
                    if(in_array($this->getCurrentWebsiteId(), $rule->getData('website_ids'))){
                        if(in_array($this->getCustomerGroupId(), $rule->getData('customer_group_ids'))){
                            $fromDate = $rule->getFromDate();
                            $toDate = $rule->getToDate();
                            if (isset($fromDate) && $_currentTime >= strtotime($fromDate)) {
                                if (isset($toDate)) {
                                    if (strtotime($toDate) >= $_currentTime) {

                                        $unSerconditions = $this->serialize->unserialize($rule->getConditionsSerialized());
                                        if(array_key_exists("conditions",$unSerconditions)){
                                            $ruleConditions = $unSerconditions['conditions'];
                                            $ruleCondtionDetailArray = $ruleConditions[0];
                                            $attribute = $ruleCondtionDetailArray['attribute'];
                                            $operator = $ruleCondtionDetailArray['operator'];
                                            $value = $ruleCondtionDetailArray['value'];
                                            $valueArray = explode(', ', $value);

                                            if($attribute == 'category_ids'){
                                                $productCategories = $_product->getCategoryIds();
                                                if(count(array_intersect($productCategories, $valueArray)) != 0){
                                                    $ruleName = $rule->getName();
                                                    $ruleId = $rule->getId();
                                                    $color_text = $rule->getColorText();

                                                   if($store->getLocale() == "ar_SA")
                                                    {
                                                        if($rule->getRtlOfferImage())
                                                            $ruleLabel = "salesrule/offerimage/".$rule->getRtlOfferImage();
                                                        else
                                                            $ruleLabel = null;
                                                    }
                                                    else
                                                    {
                                                        if($rule->getOfferImage())
                                                            $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();    
                                                        else
                                                            $ruleLabel = null;
														if($rule->getRuleBanner())
															$ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
														else
															$ruletabLabel = null;
														
														if($rule->getBundleRuleBanner())
															$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
														else
															$rulebundleLabel = null;
														
														if($rule->getRtlRuleBanner())
															$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
														else
															$ruleRtlbannerLabel = null;
                                                    }
                                                    return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                                }
                                            }else{
                                                $productAttribute = $_product->getData($attribute);
                                                if(in_array($productAttribute, $valueArray)){
                                                    $ruleName = $rule->getName();
                                                    $ruleId = $rule->getId();
                                                    $color_text = $rule->getColorText();

                                                    if($store->getLocale() == "ar_SA")
                                                    {
                                                        if($rule->getRtlOfferImage())
                                                            $ruleLabel = "salesrule/offerimage/".$rule->getRtlOfferImage();
                                                        else
                                                            $ruleLabel = null;
                                                    }
                                                    else
                                                    {
                                                        if($rule->getOfferImage())
                                                            $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();    
                                                        else
                                                            $ruleLabel = null;
														
														if($rule->getRuleBanner())
															$ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
														else
															$ruletabLabel = null;
														
														if($rule->getBundleRuleBanner())
															$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
														else
															$rulebundleLabel = null;
														
														if($rule->getRtlRuleBanner())
															$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
														else
															$ruleRtlbannerLabel = null;
                                                    }
                                                    return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                                }                            
                                            }
                                        }
                                    }
                                }
                            }else{
                                $unSerconditions = $this->serialize->unserialize($rule->getConditionsSerialized());
                                if(array_key_exists("conditions",$unSerconditions)){
                                    $ruleConditions = $unSerconditions['conditions'];
                                    $ruleCondtionDetailArray = $ruleConditions[0];
                                    $attribute = $ruleCondtionDetailArray['attribute'];
                                    $operator = $ruleCondtionDetailArray['operator'];
                                    $value = $ruleCondtionDetailArray['value'];
                                    $valueArray = explode(', ', $value);

                                    if($attribute == 'category_ids'){
                                        $productCategories = $_product->getCategoryIds();
                                        if(count(array_intersect($productCategories, $valueArray)) != 0){
                                            $ruleName = $rule->getName();
                                            $ruleId = $rule->getId();
                                            $color_text = $rule->getColorText();

                                            if($store->getLocale() == "ar_SA")
                                            {
                                                if($rule->getRtlOfferImage())
                                                    $ruleLabel = "salesrule/offerimage/".$rule->getRtlOfferImage();
                                                else
                                                    $ruleLabel = null;
                                            }
                                            else
                                            {
                                                if($rule->getOfferImage())
                                                    $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();    
                                                else
                                                    $ruleLabel = null;
												
												if($rule->getRuleBanner())
													$ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
												else
													$ruletabLabel = null;
												
												if($rule->getBundleRuleBanner())
													$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
												else
													$rulebundleLabel = null;
												
												if($rule->getRtlRuleBanner())
													$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
												else
													$ruleRtlbannerLabel = null;
														
                                            }
                                            return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                        }
                                    }else{
                                        $productAttribute = $_product->getData($attribute);
                                        if(in_array($productAttribute, $valueArray)){
                                            $ruleName = $rule->getName();
                                            $ruleId = $rule->getId();
                                            $color_text = $rule->getColorText();
                                            if($store->getLocale() == "ar_SA")
                                            {
                                                if($rule->getRtlOfferImage())
                                                    $ruleLabel = "salesrule/offerimage/".$rule->getRtlOfferImage();
                                                else
                                                    $ruleLabel = null;
                                            }
                                            else
                                            {
                                                if($rule->getOfferImage())
                                                    $ruleLabel = "salesrule/offerimage/".$rule->getOfferImage();    
                                                else
                                                    $ruleLabel = null;
												
												if($rule->getRuleBanner())
													$ruletabLabel = "salesrule/offerimage/".$rule->getRuleBanner();    
												else
													$ruletabLabel = null;
												
												if($rule->getBundleRuleBanner())
													$rulebundleLabel = "salesrule/offerimage/".$rule->getBundleRuleBanner();    
												else
													$rulebundleLabel = null;
												
												if($rule->getRtlRuleBanner())
													$ruleRtlbannerLabel = "salesrule/offerimage/".$rule->getRtlRuleBanner();    
												else
													$ruleRtlbannerLabel = null;
                                            }
                                            return array($ruleName, $ruleLabel,$color_text,$rule->getDiscountStep(),$rule->getDiscountAmount(),$ruletabLabel,$rulebundleLabel,$ruleRtlbannerLabel, $ruleId);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }   

    }

    //  public function checkRuleId($_product){
    //     $test=array('','','','');
    //     return $test;
    // }

    public function getSalesruleDiscount($product)
    {
        $_rules = $this->ruleFactory->create()->getCollection()->addFieldToFilter('coupon_type',1);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
        $_currentTime = strtotime($this->date->date());
        $currencysymbol = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $currency = $currencysymbol->getStore()->getCurrentCurrencyCode();
        foreach ($_rules as $rule) {
            if($rule->getIsActive() == 1)
            {
                $fromDate = $rule->getFromDate();
                $toDate = $rule->getToDate();
                if (isset($fromDate) && $_currentTime >= strtotime($fromDate)) {
                    if (isset($toDate)) {
                        if (strtotime($toDate) >= $_currentTime) {
                            $rule_data = $this->serialize->unserialize($rule->getActionsSerialized());
                            if(array_key_exists("conditions",$rule_data)){
                                $sku = explode(",", $rule_data['conditions']['0']['value']);
                                $sku =array_map('trim',$sku);
                                if (in_array($product->getSku(), $sku))
                                {
                                    $dis_apply_type = $rule->getData('simple_action');
                                    $dis_per = $rule->getData('discount_amount'); 
                                    $finalPrice = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                                    if($dis_per > 0){
                                        $dis_amt = $finalPrice - ($finalPrice / $dis_per);
                                        return "<div class='price-box price-final_price'>
                                        <span class='price-container price-final_price tax weee'>
                                        <span class='price-wrapper price-including-tax'>
                                        <span class='price'><span><span>".$currency."</span></span>".number_format($dis_amt,2)."</span></span>
                                            <span class='custom_incl_label'>".__('Incl. VAT Price')."</span>
                                        </span></div>";
                                    }else{
                                       $dis_amt = $finalPrice; 
                                       return "<div class='pricing-section_custom'>
                                            <div class='custom_simple_price_label'> 
                                                <span class='custom_simple_price'>
                                                <b>".$currency."</b>".number_format($dis_amt,2)."
                                            </span>
                                            </div> 
                                            <span class='custom_incl_label'>".__('Incl. VAT Price')."</span></div>";
                                    }
                                    

                                    
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    public function getRelatedCollection($product)
    {

        $collection = $this->productCollectionFactory->create()
                    ->addAttributeToSelect('*')
                    ->addFieldToFilter('entity_id', array('nin' => array($product->getId())))
                    ->addAttributeToFilter('width',$product->getWidth())          
                    ->addAttributeToFilter('profile',$product->getProfile()) 
                    ->addAttributeToFilter('construction',$product->getConstruction())
                    ->addAttributeToFilter('diameter',$product->getDiameter())
                    ->addAttributeToFilter('status', array('eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED))
                    //->addAttributeToFilter('vehicle_marking',$product->getVehicleMarking())
                    ->setPageSize(8)
                    ->load();
    
        return $collection;
    }

     public function getpatternRelatedCollection($product)
    {

        $collection = $this->productCollectionFactory->create()
                    ->addAttributeToSelect('*')
                    ->addFieldToFilter('entity_id', array('nin' => array($product->getId())))
                    ->addAttributeToFilter('pattern',$product->getPattern())          
                    ->addAttributeToFilter('brand',$product->getBrand()) 
                    ->addAttributeToFilter('status', array('eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED))
                    //->addAttributeToFilter('vehicle_marking',$product->getVehicleMarking())
                   //->setPageSize(8)
                    ->load();
    
        return $collection;
    }

    public function getDiscountPrice($_product)
    {   

        $originalPrice = $_product->getPrice();
        $finalPrice = $_product->getPriceInfo()->getPrice('final_price')->getAmount()->getBaseAmount();

        $percentage = 0;
        if ($originalPrice > $finalPrice) {
            $percentage = number_format(($originalPrice - $finalPrice) * 100 / $originalPrice,0);
        }

        if ($percentage) {
            return "<span class='discount-price'>".$percentage."% ".__('off')."</span>";
        }
    }
    public function getSetofPrice($_product)
    {   
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $vatpercentage=$this->scopeConfig->getValue('productsearch/general/vet_percent_id', $storeScope); 

        if($this->getSalesDiscountPrice($_product))
        {
            //$finalPrice = $this->getSalesDiscountPrice($_product);
			 $finalPrice = $_product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
        }
        else
        {
            $finalPrice = $_product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
        }
        $setofprice = $finalPrice * 4;
        $vatvalue=($setofprice * $vatpercentage ) / 100;
        $setofprice=number_format($setofprice  + $vatvalue,2); 
        $setofprice=str_replace(',', '', $setofprice);
        $setofpriceset =  $this->priceSymbol($setofprice);
        return $setofpriceset;
    }
    public function priceSymbol($price) {
        if ($price) {
            $convertPrice = $this->_priceHelper->currency($price, true, false);
            return $convertPrice;
        }
    }
    public function getBundleSetPrice($frontProduct,$rearProduct)
    {
        // if($this->getSalesDiscountPrice($frontProduct))
        // {
        //     $frontPrice = $this->getSalesDiscountPrice($frontProduct);
        // }
        // else
        // {
            $frontPrice = $frontProduct->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();    
        //}
        // if($this->getSalesDiscountPrice($rearProduct))
        // {
        //     $rearPrice = $this->getSalesDiscountPrice($rearProduct);
        // }
        // else
        // {
            $rearPrice  = $rearProduct->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();    
       // }
      
        $frontPrice=str_replace(',','',$frontPrice);
        $rearPrice=str_replace(',','',$rearPrice);

        $frontSetPrice = (float) $frontPrice * 2;
        $rearPrice     = (float) $rearPrice * 2;
        $setofprice    = $frontSetPrice + $rearPrice;
		$setofprice    = $setofprice + ($setofprice * 0.05);
        $setofpriceset =  $this->priceSymbol($setofprice);
        return $setofpriceset;
    }
    public function getBundleSetPricenosymbol($frontProduct,$rearProduct)
    {
        // if($this->getSalesDiscountPrice($frontProduct))
        // {
        //     $frontPrice = $this->getSalesDiscountPrice($frontProduct);
        // }
        // else
        // {
            $frontPrice = $frontProduct->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();    
        // }
        // if($this->getSalesDiscountPrice($rearProduct))
        // {
        //     $rearPrice = $this->getSalesDiscountPrice($rearProduct);
        // }
        // else
        // {
            $rearPrice  = $rearProduct->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();    
       // }
        $frontSetPrice = (float) $frontPrice * 2;
        $rearPrice     = (float) $rearPrice * 2;
        $setofprice    = $frontSetPrice + $rearPrice;
        $setofprice    = $setofprice + ($setofprice * 0.05);
       // $setofpriceset =  $this->priceSymbol($setofprice);
        return $setofprice;
    }

    public function getSalesDiscountPrice($product)
    {
        $_rules = $this->ruleFactory->create()->getCollection()->addFieldToFilter('coupon_type',1);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
        $_currentTime = strtotime($this->date->date());
        $currencysymbol = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $currency = $currencysymbol->getStore()->getCurrentCurrencyCode();
        foreach ($_rules as $rule) {
            if($rule->getIsActive() == 1)
            {
                $fromDate = $rule->getFromDate();
                $toDate = $rule->getToDate();
                if (isset($fromDate) && $_currentTime >= strtotime($fromDate)) {
                    if (isset($toDate)) {
                        if (strtotime($toDate) >= $_currentTime) {
                            $rule_data = $this->serialize->unserialize($rule->getActionsSerialized());
                            if(array_key_exists("conditions",$rule_data)){
                                $sku = explode(",", $rule_data['conditions']['0']['value']);
                                $sku =array_map('trim',$sku);
                                if (in_array($product->getSku(), $sku))
                                {
                                    $dis_apply_type = $rule->getData('simple_action');
                                    $dis_per = $rule->getData('discount_amount'); 
                                    $finalPrice = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                                    //$dis_amt = $finalPrice - ($finalPrice / $dis_per);
                                    if($dis_per != 0){
                                        $dis_amt = $finalPrice - ($finalPrice / $dis_per);    
                                        return number_format($dis_amt,2);   
                                    }
                                    else{
                                        return 0;    
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

     public function getCustomRating($product,$brandReview = false)
    {  
        $current_website_id =  $this ->_storeManager->getStore()->getWebsiteId();
        if($brandReview)
        {
            $name = $product['name'];
        }
        else
        {
            $name = $product->getName();
        }
        
        $summaryData = $this->productCollectionFactory->create()->addAttributeToSelect('entity_id')->addWebsiteFilter($current_website_id);
        $summaryData->addAttributeToFilter('name',array('like' => $name));
        // echo '<pre>';
        // print_r($summaryData->getAllIds());
        // die;
        // $summaryData->getSelect() 
        //             ->join(array('reviewRating' => 'review_entity_summary'), 'e.entity_id = reviewRating.entity_pk_value',array('reviewRating.rating_summary')); 
        // $summaryData->addAttributeToFilter('name',array('like' => $name))
        //             ->setOrder('reviewRating.rating_summary', 'DESC'); 

        // $summaryData->getSelect() 
        //             ->join(array('review' => 'review'), 'review.entity_pk_value = reviewRating.entity_pk_value',array('review.review_id')); 

        // $summaryData->getSelect() 
        //             ->join(array('reviewEntity' => 'review_detail'), 'reviewEntity.review_id = review.review_id',array('store' => 'reviewEntity.store_id')); 
        if(!empty($summaryData->getAllIds())){


        // Update to get rreview rating related product name : uc : 15 Jan 2018
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $reviewFactory = $objectManager->create('Magento\Review\Model\Review')->getCollection()
                    ->addFieldToFilter('main_table.entity_pk_value',array('in',$summaryData->getAllIds()));
        $reviewFactory->getSelect() 
                      ->join(array('reviewRating' => 'review_entity_summary'), 'main_table.entity_pk_value = reviewRating.entity_pk_value',array('reviewRating.rating_summary')); 
         $reviewFactory->getSelect() 
                       ->join(array('reviewEntity' => 'review_detail'), 'reviewEntity.review_id = main_table.review_id',array('store' => 'reviewEntity.store_id'));
        $reviewFactory->getSelect()->where( 'reviewRating.store_id = reviewEntity.store_id');

        
       // Update to get rreview rating related product name : uc : 15 Jan 2018
        $i = 0; 
        $ratingSummary = 0;
        foreach ($reviewFactory->getData() as $value)
        { 
            if($value['rating_summary']) 
            { 
                $i++; 
                $ratingSummary += $value['rating_summary']; 
            } 
        } 
        
        if($ratingSummary) {
            $rates = array('rate' => $ratingSummary/$i,
                        'rate_count' => $i);
            return $rates;
        }
        else 
            return false;

        }
        return false;
    }
      public function getlatestRating($product,$brandReview = false)
        {
          $lastreview=array();  
                $current_website_id =  $this ->_storeManager->getStore()->getWebsiteId();
                if($brandReview)
                {
                    $name = $product['name'];
                }
                else
                {
                    $name = $product->getName();
                }
                
                $summaryData = $this->productCollectionFactory->create()->addAttributeToSelect('entity_id')->addWebsiteFilter($current_website_id);
                $summaryData->addAttributeToFilter('name',array('like' => $name));
               
                if(!empty($summaryData->getAllIds())){


                // Update to get rreview rating related product name : uc : 15 Jan 2018
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $reviewFactory = $objectManager->create('Magento\Review\Model\Review')->getCollection()
                            ->addFieldToFilter('main_table.entity_pk_value',array('in',$summaryData->getAllIds()));
                $reviewFactory->getSelect() 
                              ->join(array('reviewRating' => 'review_entity_summary'), 'main_table.entity_pk_value = reviewRating.entity_pk_value',array('reviewRating.rating_summary')); 
                 $reviewFactory->getSelect() 
                               ->join(array('reviewEntity' => 'review_detail'), 'reviewEntity.review_id = main_table.review_id',array('store' => 'reviewEntity.store_id'));
                $reviewFactory->getSelect()->where( 'reviewRating.store_id = reviewEntity.store_id');

                $allreview=$reviewFactory->getData();
                if( count ( $allreview)  >  0 ){
                   $myLastreview = end($allreview);
                   
                   return $myLastreview;
                } 
            }
            return $lastreview;
        }    

     function getcalculatedPrice($_product){
        $vatvalue = $this->scopeConfig->getValue('productsearch/general/vet_percent_id');
            $finalPrice = $_product->getFinalPrice();
            $productRuleLabel = $this->checkRuleId($_product);
            $finalPricetax=($finalPrice * $vatvalue ) / 100;
            $finalPricewithtax=$finalPrice + $finalPricetax;
            $finalPricewithtax_four_Qty=$finalPricewithtax * 4;
            $total_rule_qty="";
            $discount_amount_qty="";
            $isoffetproduct=0;
            $offer_price=0;
            $selected_qty = 4;
            $actual_strike_price=0;
            $discount_step_qty=0;
            $result=array();

           
            if(($productRuleLabel[3] != "" || $productRuleLabel[3] != null)) {
               if(($productRuleLabel[4] != "" || $productRuleLabel[4] != null)){          
                    $discount_step_qty=(int)$productRuleLabel[3];
                    if($productRuleLabel[3]>=1){    
                         $isoffetproduct=1;
                         $discount_amount_qty=(int)$productRuleLabel[4];
                         $total_rule_qty=$discount_amount_qty+$discount_step_qty;
                         $actual_strike_price= 4 * $finalPricewithtax;
                         $module_qty=4 % $discount_step_qty ;
                         $discounted_qty=4-$module_qty;
                         $pricefor_discount_item=( $discounted_qty * $finalPricewithtax  * $discount_amount_qty ) / 100; //get percenatge
                         $offer_price_with_deducted_ammount=( $discounted_qty * $finalPricewithtax) - $pricefor_discount_item;
                         $pricefor_without_discount_item=$finalPricewithtax * $module_qty ;
                         $offer_price=$offer_price_with_deducted_ammount + $pricefor_without_discount_item;
                    }else{
                        $offer_price = $finalPricewithtax_four_Qty;                                                     
                    }
               }
            }
          $result['offer_price']=$offer_price;
          $result['actual_strike_price']=$actual_strike_price;
          $result['finalPricewithtax_four_Qty']=$finalPricewithtax_four_Qty;         
          $result['isoffetproduct']=$isoffetproduct;    
          $result['discount_amount_qty']=$discount_amount_qty;      
          $result['discount_step_qty']=$discount_step_qty; 
          $result['finalPricewithtax']=$finalPricewithtax;
                

          return $result; 
     }    

         function getBundlecalculatedPrice($_product){
        $vatvalue = $this->scopeConfig->getValue('productsearch/general/vet_percent_id');
            $finalPrice = $_product->getFinalPrice();
            $productRuleLabel = $this->checkRuleId($_product);
            $finalPricetax=($finalPrice * $vatvalue ) / 100;
            $finalPricewithtax=$finalPrice + $finalPricetax;
            $finalPricewithtax_four_Qty=$finalPricewithtax * 2;
            $total_rule_qty="";
            $discount_amount_qty="";
            $isoffetproduct=0;
            $offer_price=0;
            $selected_qty = 2;
            $actual_strike_price=0;
            $discount_step_qty=0;
            $result=array();
             $offer_price_with_deducted_ammount='';

            if(($productRuleLabel[3] != "" || $productRuleLabel[3] != null)) {
               if(($productRuleLabel[4] != "" || $productRuleLabel[4] != null)){          
                    $discount_step_qty=(int)$productRuleLabel[3];
                    if($productRuleLabel[3]>=1){    
                         $isoffetproduct=1;
                         $discount_amount_qty=(int)$productRuleLabel[4];
                         $total_rule_qty=$discount_amount_qty+$discount_step_qty;
                         $actual_strike_price= 2 * $finalPricewithtax;
                         $module_qty=2 % $discount_step_qty ;
                         $discounted_qty=2-$module_qty;
                         $pricefor_discount_item=( $discounted_qty * $finalPricewithtax  * $discount_amount_qty ) / 100; //get percenatge
                         $offer_price_with_deducted_ammount=( $discounted_qty * $finalPricewithtax) - $pricefor_discount_item;
                         $pricefor_without_discount_item=$finalPricewithtax * $module_qty ;
                         $offer_price=$offer_price_with_deducted_ammount + $pricefor_without_discount_item;
                    }else{
                        $offer_price = $finalPricewithtax_four_Qty;                                                     
                    }
               }
            }
          $result['offer_price']=$offer_price;
          $result['actual_strike_price']=$actual_strike_price;
          $result['finalPricewithtax_four_Qty']=$finalPricewithtax_four_Qty;         
          $result['isoffetproduct']=$isoffetproduct;    
          $result['discount_amount_qty']=$discount_amount_qty;      
          $result['discount_step_qty']=$discount_step_qty; 
          $result['finalPricewithtax']=$finalPricewithtax;         
           $result['offer_price_with_deducted_ammount']=$offer_price_with_deducted_ammount;  
          return $result; 
     }  
}
?>