<?php

namespace Hdweb\Coreoverride\Plugin\Controller\Adminhtml\Order;

class AddComment
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->_request = $context->getRequest();
    }

    public function aroundExecute(\Magento\Sales\Controller\Adminhtml\Order\AddComment $subject, \Closure $proceed)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderId = $this->_request->getParam('order_id');
        $order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($orderId);
        $orderCurrentStatus = $order->getDataByKey('status');
        $data = $this->_request->getPost('history');
        $adminUser = $objectManager->get('Magento\Backend\Model\Auth\Session')->getUser();
        if ($data['comment'] != '') {
            $data['comment'] = $data['comment'].' - BY '. $adminUser->getFirstname(). ' '.$adminUser->getLastname();
            
        }
        if($data['status'] != $orderCurrentStatus) {
            if ($data['comment'] == '') {
                $data['comment'] = $data['comment'].' - BY '. $adminUser->getFirstname(). ' '.$adminUser->getLastname();
            }
            
        }
        $this->_request->setPostValue('history',$data);

        $returnValue = $proceed();

        return $returnValue;
    }
}
?>