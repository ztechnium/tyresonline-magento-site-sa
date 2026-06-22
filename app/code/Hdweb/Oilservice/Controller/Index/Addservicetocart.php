<?php
namespace Hdweb\Oilservice\Controller\Index;

class Addservicetocart extends \Magento\Framework\App\Action\Action
{	
	protected $resultPageFactory;
	protected $cart;
	protected $product;
	protected $formKey;
	protected $resultRedirectFactory;
	protected $_objectManager;
	
    public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Checkout\Model\Cart $cart,
		\Magento\Catalog\Model\ProductFactory $product,
		\Magento\Framework\Data\Form\FormKey $formKey,
		\Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
		\Magento\Framework\ObjectManagerInterface $objectManager
	) {
		$this->resultPageFactory = $resultPageFactory;
		$this->cart = $cart;
		$this->product = $product;
		$this->formKey = $formKey;
		$this->resultRedirectFactory = $resultRedirectFactory;
		$this->_objectManager = $objectManager;
    	parent::__construct($context);
    }
    
    public function execute()
    {
		// Add bundle to cart : MB

		$serviceproduct = $this->getRequest()->getParam('serviceproduct');
		//echo '<pre>';print_r($serviceproduct);die;
		$product_oil_litre = $this->getRequest()->getParam('product_oil_litre');
        $qty = 1;
		if($this->getRequest()->getParam('qty')){
			$qty = $this->getRequest()->getParam('qty');
		}
		$package_type = '';
		if($this->getRequest()->getParam('package_type')){
			$package_type = $this->getRequest()->getParam('package_type');
			
			$this->removeServiceProductCart(); // remove existing service products before add to cart
		}

		$productIds = array();
		foreach ($serviceproduct as $key => $product_id) {
			if ($product_id == '') {
                continue;
            }
			$productModel = $this->product->create();
			$_product = $productModel->load($product_id);
			$productIds[] = $product_id;
			try {
				$option_params = array();
				$options = array();
				
				$option_params['qty'] = $qty;
				$option_params['product'] = $product_id;
				if($productModel->getHasOptions()){
					$oilPerLitre = 1;
					if($product_oil_litre != ''){
						$oilPerLitre = $product_oil_litre;
					}
					foreach ($productModel->getOptions() as $o) {
						foreach ($o->getValues() as $value) {
							if($value->getTitle() == $oilPerLitre){
								$options[$value['option_id']] = $value['option_type_id'];
								break;
							}else{
								$options[$value['option_id']] = $value['option_type_id'];
							}
						}
					}
				}
				
				$option_params['options'] = $options;
				$this->cart->addProduct($productModel, $option_params);
			} catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            }
		}
		$checkoutSession = $this->_objectManager->get('Magento\Checkout\Model\Session');
		//$checkoutSession->setIsOilServiceProduct(1);
		$this->cart->save();
		$items = $this->cart->getQuote()->getAllItems();
		if($items){
			foreach ($items as $item){
				$itemProductId = $item->getProductId();
				if(in_array($itemProductId, $productIds)){
					if($package_type != ''){
						$item->setIsOilServiceProduct(1);
						$item->setOilServicePackage($package_type);
					}
					$item->getProduct()->setIsSuperMode(true);
				}
			}
		}
		$checkoutSession->getQuote()->save();
		$this->_redirect('checkout/cart/');
		/* $resultRedirect = $this->resultRedirectFactory->create();
		$resultRedirect->setPath('checkout/cart');
		return $resultRedirect; */
    }	
	
	public function removeServiceProductCart(){
		$items = $this->cart->getQuote()->getAllItems();
		if($items){
			$itemModel = $this->_objectManager->create('Magento\Quote\Model\Quote\Item');
			$this->cart->getQuote()->setTotalsCollectedFlag(false)->save();
			foreach ($items as $item){
				$itemId = $item->getItemId();
				$itemObj = $itemModel->load($itemId);
				if($itemObj->getIsOilServiceProduct()){
					$this->cart->removeItem($itemId);
				}
			}
			
		}
	}
}

