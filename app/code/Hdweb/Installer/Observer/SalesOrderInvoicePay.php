<?php
namespace Hdweb\Installer\Observer;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
class SalesOrderInvoicePay implements ObserverInterface
{   
	/**
	* @param EventObserver $observer
	* @return $this
	*/
	public function execute(EventObserver $observer)
	{
		$invoice = $observer->getEvent()->getInvoice();
		$order = $invoice->getOrder();
		$customerEmail = $order->getCustomerEmail();
		$orderIncrementId = $order->getIncrementId();
		$order_amount     = $order->getGrandTotal();
		$order_amount = number_format($order_amount, 2, '.', '');
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$objectManager->create('Hdweb\Rfc\Helper\Data')->sendAdminInvoiceEmailNotification($order);
	}
}