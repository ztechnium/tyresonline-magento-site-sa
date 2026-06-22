<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;

class Addservicetocart extends \Magento\Framework\App\Action\Action
{	
	protected $resultPageFactory;
	protected $cart;
	protected $product;
	protected $formKey;
	
    public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Checkout\Model\Cart $cart,
		\Magento\Catalog\Model\ProductFactory $product,
		\Magento\Framework\Data\Form\FormKey $formKey
	) {
		$this->resultPageFactory = $resultPageFactory;
		$this->cart = $cart;
		$this->product = $product;
		$this->formKey = $formKey;
    	parent::__construct($context);
    }
    
    public function execute()
    {
		$serviceproduct = $this->getRequest()->getParam('serviceproduct');
		$selected_option_id = $this->getRequest()->getParam('selected_option_id');
		$selected_shooting_star = $this->getRequest()->getParam('selected_checkbox_option');
		$qty = 1;
		if($this->getRequest()->getParam('qty')){
			$qty = $this->getRequest()->getParam('qty');
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
					foreach ($productModel->getOptions() as $o) {
						foreach ($o->getValues() as $value) {
							if($value->getOptionTypeId() == $selected_option_id){
								$options[$value['option_id']] = $value['option_type_id'];
								break;
							}else{
								if($selected_shooting_star == 1){
									$options[$value['option_id']] = $value['option_type_id'];
								}
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
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$checkoutSession = $objectManager->get('Magento\Checkout\Model\Session');
		$this->cart->save();
		$checkoutSession->getQuote()->save();
		$this->_redirect('checkout/cart/');
    }	
}

