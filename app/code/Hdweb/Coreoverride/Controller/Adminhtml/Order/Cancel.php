<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hdweb\Coreoverride\Controller\Adminhtml\Order;

class Cancel extends \Magento\Sales\Controller\Adminhtml\Order\Cancel
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::cancel';

    /**
     * Cancel order
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->isValidPostRequest()) {
            $this->messageManager->addErrorMessage(__('You have not canceled the item.'));
            return $resultRedirect->setPath('sales/*/');
        }
        $order = $this->_initOrder();
        if ($order) {
            try {
                $this->orderManagement->cancel($order->getEntityId());
                $this->messageManager->addSuccessMessage(__('You canceled the order.'));
                //start custom code for order comment
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $adminUser = $objectManager->get('Magento\Backend\Model\Auth\Session')->getUser();
                $order->addStatusHistoryComment('Order is canceled using CANCEL action - BY '. $adminUser->getFirstname(). ' '.$adminUser->getLastname());
                $order->save();
                //end custom code for order comment
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('You have not canceled the item.'));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
            return $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
        }
        return $resultRedirect->setPath('sales/*/');
    }
}
