<?php

namespace Mageplaza\GoogleTagManager\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Mageplaza\GoogleTagManager\Helper\Data;
use Mageplaza\GoogleTagManager\Model\Config\Source\EventList as Event;

/**
 * Class CustomerSignUp
 * @package Mageplaza\GoogleTagManager\Observer
 */
class CustomerSignUp implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * CustomerSignUp constructor.
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
     * @return CustomerSignUp
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $canShowEvents = $this->_helper->getShowEvents();

        if ($this->_helper->isEnabled() && in_array(Event::REGISTRATION, $canShowEvents)) {
            $customer     = $observer->getData('customer');
            $customerName = $customer->getFirstname() . ' ' . $customer->getMiddlename() . ' ' . $customer->getLastname();

            $this->setGTMSignUpData();
            $this->setPixelSignUpData($customerName);
        }

        return $this;
    }

    /**
     * @return void
     */
    protected function setGTMSignUpData()
    {
        if ($this->_helper->getConfigGTM('enabled')) {
            $this->_helper->getSessionManager()->setGTMSignUpData($this->_helper->getGTMSignUpData());
        }
    }

    /**
     * @param $customerName
     *
     * @return void
     * @throws NoSuchEntityException
     */
    protected function setPixelSignUpData($customerName)
    {
        if ($this->_helper->getConfigPixel('enabled')) {
            $data = [
                'content_name' => $customerName,
                'currency'     => $this->_helper->getCurrentCurrency(),
                'status'       => true
            ];

            $this->_helper->getSessionManager()->setPixelSignUpData($data);
        }
    }
}
