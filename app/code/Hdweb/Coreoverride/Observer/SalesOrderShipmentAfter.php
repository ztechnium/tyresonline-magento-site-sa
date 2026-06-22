<?php

namespace Hdweb\Coreoverride\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesOrderShipmentAfter implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $shipment = $observer->getEvent()->getShipment();
        $shipmentId = $shipment->getData('increment_id');
        /** @var \Magento\Sales\Model\Order $order */
        $order = $shipment->getOrder();
        $adminUser = $objectManager->get('Magento\Backend\Model\Auth\Session')->getUser();
        $order->addStatusHistoryComment('Shipment #'.$shipmentId.' is created - BY '. $adminUser->getFirstname(). ' '.$adminUser->getLastname());
        $order->save();
    }
}