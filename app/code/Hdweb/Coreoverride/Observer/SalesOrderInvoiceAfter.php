<?php

namespace Hdweb\Coreoverride\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesOrderInvoiceAfter implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    	$invoice = $observer->getEvent()->getInvoice();
        $invoiceId = $invoice->getData('increment_id');
        $order = $invoice->getOrder();
        $adminUser = $objectManager->get('Magento\Backend\Model\Auth\Session')->getUser();
        $order->addStatusHistoryComment('Invoice #'.$invoiceId.' is generated - BY '. $adminUser->getFirstname(). ' '.$adminUser->getLastname());
        $order->save();
    }
}