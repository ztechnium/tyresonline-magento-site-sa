<?php

namespace Hdweb\Coreoverride\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesOrderCreditmemoAfter implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    	$creditmemo = $observer->getEvent()->getCreditmemo();
        $creditmemoId = $creditmemo->getData('increment_id');
        $order = $creditmemo->getOrder();
        $adminUser = $objectManager->get('Magento\Backend\Model\Auth\Session')->getUser();
        $order->addStatusHistoryComment('Credit Memo #'.$creditmemoId.' is created - BY '. $adminUser->getFirstname(). ' '.$adminUser->getLastname());
        $order->save();
    }
}