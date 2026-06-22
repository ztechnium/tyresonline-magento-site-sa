<?php

namespace Mageplaza\GoogleTagManager\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mageplaza\GoogleTagManager\Helper\Data;
use Mageplaza\GoogleTagManager\Model\Config\Source\EventList as Event;

/**
 * Class CustomerLogin
 * @package Mageplaza\GoogleTagManager\Observer
 */
class CustomerLogin implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * CustomerLogin constructor.
     *
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->_helper = $helper;
    }

    /**
     * @param Observer $observer
     *
     * @return CustomerLogin
     */
    public function execute(Observer $observer)
    {
        $canShowEvents = $this->_helper->getShowEvents();

        if ($this->_helper->isEnabled() && in_array(Event::LOGIN, $canShowEvents)) {
            $this->setGTMLoginData();
        }

        return $this;
    }

    /**
     * @return void
     */
    protected function setGTMLoginData()
    {
        if ($this->_helper->getConfigGTM('enabled')) {
            $this->_helper->getSessionManager()->setGTMLoginData($this->_helper->getGTMLoginData());
        }
    }
}
