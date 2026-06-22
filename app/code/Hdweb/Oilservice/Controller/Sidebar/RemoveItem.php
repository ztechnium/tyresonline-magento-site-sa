<?php
namespace Hdweb\Oilservice\Controller\Sidebar;

class RemoveItem extends \Magento\Checkout\Controller\Sidebar\RemoveItem
{
    public function execute()
    {
        $itemId = (int)$this->getRequest()->getParam('item_id');
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$resultJson = $objectManager->get('Magento\Framework\Controller\Result\JsonFactory')->create();
		$error = '';
        try {
			
			$checkoutSession = $objectManager->get('Magento\Checkout\Model\Session');
			$itemModel = $objectManager->create('Magento\Quote\Model\Quote\Item');
			$itemObj = $itemModel->load($itemId);
			$this->sidebar->checkQuoteItem($itemId);
			if($itemObj->getIsOilServiceProduct()){
				$allItems = $checkoutSession->getQuote()->getAllItems();
				foreach ($allItems as $item) 
				 {
					if($item->getIsOilServiceProduct()){
						$this->sidebar->removeQuoteItem($item->getItemId());
					}
				 }
			}else{
				if($itemObj->getSku() == 'Mobile-Tyre-Van-Tyre-Service'){
					setcookie("mobilevanservice_data", "", time() - 3600, '/');
				}
				$this->sidebar->removeQuoteItem($itemId);	
			}
            
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $error = $e->getMessage();
        } catch (\Zend_Db_Exception $e) {
            $this->logger->critical($e);
            $error = __('An unspecified error occurred. Please contact us for assistance.');
        } catch (Exception $e) {
            $this->logger->critical($e);
            $error = $e->getMessage();
        }
		$resultJson->setData($this->sidebar->getResponseData($error));

        return $resultJson;
    }
}