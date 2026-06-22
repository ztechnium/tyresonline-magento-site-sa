<?php

namespace Hdweb\Installer\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SaveOrderSaveBeforeObserver implements ObserverInterface
{

    protected $_checkoutSession;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_checkoutSession = $checkoutSession;
    }

    public function execute(EventObserver $observer)
    {

        $quote      = $observer->getQuote();
        $pickupdate = $quote->getDeliveryDate();
        $pickuptime = $quote->getDeliveryComment();

        $order = $observer->getOrder();

        if (isset($pickupdate)) {
            $order->setDeliveryDate($pickupdate);
        }

        if (isset($pickuptime)) {
            $order->setDeliveryComment($pickuptime);
        }

        return $this;
    }

}
