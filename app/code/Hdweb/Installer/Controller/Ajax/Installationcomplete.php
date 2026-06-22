<?php
namespace Hdweb\Installer\Controller\Ajax;
use Magento\Framework\Controller\ResultFactory;
class Installationcomplete extends \Magento\Framework\App\Action\Action {

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function execute() {
        $order_increment_id = $this->getRequest()->getParam('order_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($order_increment_id) {
			$orderObj = $this->_objectManager->get('Magento\Sales\Model\Order');
			$orderCollectionFactory = $this->_objectManager->get('Magento\Sales\Model\ResourceModel\Order\CollectionFactory');
			$orderCollection = $orderCollectionFactory->create()
							->addAttributeToFilter('status', array('in' => array('pending','canceled','complete')))
							->addAttributeToFilter('is_notify_installer', ['eq'=> $order_increment_id]);
			//echo "<pre>"; print_r($orderCollection->getData());die;  
			if(count($orderCollection->getData()) > 0){
				foreach($orderCollection as $orderData){
					$orderId = $orderData->getId();
					$order = $orderObj->load($orderId);
					$isNotifyInstaller = $order->getIsNotifyInstaller();
					if($isNotifyInstaller != ''){
						$customerEmail = $order->getCustomerEmail();
						$orderIncrementId = $order->getIncrementId();
						$order->setState('processing', true);
						$order->setStatus('installation_completed');
						$order->setIsNotifyInstaller(NULL);
						$order->addStatusHistoryComment('Notify Installer : Installation completed from installer.');
						$order->save();
						$this->_objectManager->create('Hdweb\Rfc\Helper\Data')->sendInstallationEmailNotification($orderIncrementId, $customerEmail);
						$this->messageManager->addSuccessMessage(__('The order marked as installation completed.'));
					}else{
						$this->messageManager->addErrorMessage(__('The link you followed has expired.'));
					}
				}
			}else{
				$this->messageManager->addErrorMessage(__('The link you followed has expired.'));
			}
		}
		$url = $this->_objectManager->get('Magento\Framework\UrlInterface');
		$result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
		$storeManager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface'); 
		$redirectUrl = $storeManager->getStore()->getBaseUrl().'contact';
        $result->setUrl($url->getUrl($redirectUrl));
        return $result;
    }
}