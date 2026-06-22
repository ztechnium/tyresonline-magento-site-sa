<?php
namespace Hdweb\Coreoverride\Observer;

class CategoryLoadAfter implements \Magento\Framework\Event\ObserverInterface {

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    private $request;
    protected $config;
	protected $_objectManager;

    public function __construct(
     \Psr\Log\LoggerInterface $logger,
     \Magento\Framework\App\RequestInterface $request,
     \Magento\Eav\Model\Config $config,
	 \Magento\Framework\ObjectManagerInterface $objectManager
    ) {

        $this->logger = $logger;
        $this->request = $request;
        $this->config = $config;  
		$this->_objectManager = $objectManager;		
    }
    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {

        $category = $observer->getEvent()->getCategory();
        $brandName = $this->request->getParam('mgs_brand');
		$params = $this->request->getParams();
        $_finderhelper   = $this->_objectManager->create('Hdweb\Tyrefinder\Helper\Data');
		$SearchTyreValue = $_finderhelper->getSearchValue();
		//echo $category->getId();die;
		$autopartsCategories = array(7,8,37,38,49);
		$brandTitle = '';
		$patternName = $this->request->getParam('pattern');
		if(is_array($brandName)){
			$brandName = implode(',', $brandName);
		}
		if(in_array($category->getId(), $autopartsCategories)){
			$brandText = '';
			if ($brandName !== null) {
				$brandText=ucfirst($brandName);
			}
			$title = "Buy ".$brandText. " ".$category->getName()." | Order Today at TyresOnline";
			//$desc="If you are looking for high quality ".$category->getName()." in Saudi Arabia. Buy ".$brandText." ".$category->getName().". Explore ".$brandText." " .$category->getName()." variety & affordable price on Tyresonline";

		   $category->setMetaTitle($title);
		   //$category->setMetaDescription($desc);
		}else{
			if(isset($brandName) && !empty($brandName) && ($category instanceof \Magento\Catalog\Model\Category) ) {
			   if(is_array($brandName)){
				   $brandName = implode(", ", $brandName);
			   }
			   $brandText = '';
				if ($brandName !== null) {
					$brandText=ucfirst($brandName);
				}
			   $title = "Buy ".$brandText." Tyres Online | Order Today at TyresOnline";
			   //$desc="If you are looking for high quality tyres in dubai, abu dhabi and sharjah. Buy ".$brandText." tires. Explore ".$brandText." tyre variety & affordable price on Tyresonline.ae";

			   $category->setMetaTitle($title);
			  // $category->setMetaDescription($desc);
			}
			if(!empty($SearchTyreValue) && ($category instanceof \Magento\Catalog\Model\Category)){
					
				  if(isset($brandName) && !empty($brandName) && ($category instanceof \Magento\Catalog\Model\Category) ) {
						if(is_array($brandName)){
						   $brandName = implode(", ", $brandName);
					   }
					   $brandText = '';
					   if ($brandName !== null) {
						   $brandText=ucfirst($brandName);
					   }
						$brandTitle = $brandText. " |";
				  }		  
					
				   $title = "Buy ".$SearchTyreValue." Tyres Online | ".$brandTitle." Order Today at TyresOnline";
				   //$desc="If you are looking for high quality tyres in dubai, abu dhabi and sharjah. Buy ".$SearchTyreValue." tires. Explore ".$SearchTyreValue." tyre variety & affordable price on Tyresonline.ae";
				   $category->setMetaTitle($title);
				  // $category->setMetaDescription($desc);
			}
			if(isset($patternName) && !empty($patternName) && ($category instanceof \Magento\Catalog\Model\Category) ) {
			   if(is_array($patternName)){
				   $patternName = implode(", ", $patternName);
			   }
			   $patternText = '';
				if ($patternName !== null) {
					$patternText=ucfirst($patternName);
				}
			   $title = "Buy ".$patternText." Tyres Online | Order Today at TyresOnline";
			   $category->setMetaTitle($title);
			}
		}
        
    }

}