<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hdweb\Oilservice\Controller\Cart;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * Action Delete.
 *
 * Deletes item from cart.
 */
class Delete extends \Magento\Checkout\Controller\Cart\Delete
{
    /**
     * Delete shopping cart item action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$checkoutSession = $objectManager->get('Magento\Checkout\Model\Session');
		$itemModel = $objectManager->create('Magento\Quote\Model\Quote\Item');
		
		if (!$this->_formKeyValidator->validate($this->getRequest())) {
			return $this->resultRedirectFactory->create()->setPath('*/*/');
		}

		$id = (int)$this->getRequest()->getParam('id');
		if ($id) {
			try {
				$itemObj = $itemModel->load($id);
				if($itemObj->getIsOilServiceProduct()){
					$allItems = $checkoutSession->getQuote()->getAllItems();
					foreach ($allItems as $item) 
					 {
						if($item->getIsOilServiceProduct()){
							$cartItemId = $item->getItemId();
							/* $itemObj = $itemModel->load($cartItemId);
							$itemObj->delete();	 */
							$this->cart->removeItem($cartItemId)->save();
						}
					 }
				}else{
					if($itemObj->getSku() == 'Mobile-Tyre-Van-Tyre-Service'){
						setcookie("mobilevanservice_data", "", time() - 3600, '/');
					}
					$this->cart->removeItem($id);	
				}
				
				// We should set Totals to be recollected once more because of Cart model as usually is loading
				// before action executing and in case when triggerRecollect setted as true recollecting will
				// executed and the flag will be true already.
				$this->cart->getQuote()->setTotalsCollectedFlag(false);
				$this->cart->save();
			} catch (\Exception $e) {
				$this->messageManager->addErrorMessage(__('We can\'t remove the item.'));
				$this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
			}
		}
        
        $defaultUrl = $this->_objectManager->create(\Magento\Framework\UrlInterface::class)->getUrl('*/*');
        return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl($defaultUrl));
    }
}
