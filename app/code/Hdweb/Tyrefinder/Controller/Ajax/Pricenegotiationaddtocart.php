<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;

use Magento\Store\Model\ScopeInterface;

class Pricenegotiationaddtocart extends \Magento\Framework\App\Action\Action
{	
	
	protected $resultJsonFactory;
	protected $productCollectionFactory;
	protected $productFactory;
	public $scopeConfig;
	public $tyrefinderListingHelper;
	protected $cart;
	protected $formKey;   
    
    public function __construct(\Magento\Framework\App\Action\Context $context,
    	\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Catalog\Model\ProductFactory $productFactory,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Hdweb\Tyrefinder\Helper\Productlisting $tyrefinderListingHelper,
		\Magento\Checkout\Model\Cart $cart,
		\Magento\Framework\Data\Form\FormKey $formKey
	) {
    	$this->resultJsonFactory = $resultJsonFactory;
		$this->productCollectionFactory = $productCollectionFactory;
		$this->productFactory = $productFactory;
		$this->scopeConfig = $scopeConfig;
		$this->tyrefinderListingHelper = $tyrefinderListingHelper;
		$this->cart = $cart;
		$this->formKey = $formKey;
		
        parent::__construct($context);
    }
    
    public function execute()
    {	

    	$productId = $this->getRequest()->getParam('id');
    	$coupon = $this->getRequest()->getParam('coupon');
    	$qty = $this->getRequest()->getParam('qty');
		$addproid = $this->getRequest()->getParam('addproid');
    	
        $params = array(
                    'form_key' => $this->formKey->getFormKey(),
                    'product' => $productId, 
                    'qty'   =>$qty
                );
		$addProParams = array(
			'form_key' => $this->formKey->getFormKey(),
			'product' => $addproid, 
			'qty'   =>1
		);              
        $product = $this->productFactory->create()->load($productId);
		$addproProduct = $this->productFactory->create()->load($addproid);
        $this->cart->addProduct($product, $params);
		$this->cart->addProduct($addproProduct, $addProParams);
        $this->cart->save();
        $this->cart->getQuote()->setCouponCode($coupon)->collectTotals()->save();
        $this->_redirect('checkout/cart/');
    }
}

