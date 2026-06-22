<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hdweb\Coreoverride\Controller\Adminhtml\Order;

class VoidPayment extends \Magento\Sales\Controller\Adminhtml\Order\VoidPayment
{
    /**
     * Attempt to void the order payment
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $order = $this->_initOrder();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($order) {
            try {
                // workaround for backwards compatibility
				$customerEmail = $order->getCustomerEmail();
				$orderIncrementId = $order->getIncrementId();
				$order_amount     = $order->getGrandTotal();
				$order_amount = number_format($order_amount, 2, '.', '');
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$objectManager->create('Hdweb\Rfc\Helper\Data')->sendVoidEmailNotification($orderIncrementId, $customerEmail, $order_amount);
                $order->getPayment()->void(new \Magento\Framework\DataObject());
				$order->setState('canceled', true);
				$order->setStatus('voided');
                $order->save();
				
                $this->messageManager->addSuccessMessage(__('The payment has been voided.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We can\'t void the payment right now.'));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
            $resultRedirect->setPath('sales/*/view', ['order_id' => $order->getId()]);
            return $resultRedirect;
        }
        $resultRedirect->setPath('sales/*/');
        return $resultRedirect;
    }
}
