<?php

namespace Meetanshi\WorldpayHp\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Meetanshi\WorldpayHp\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;

/**
 * Class Redirect
 * @package Meetanshi\WorldpayHp\Block
 */
class Redirect extends Template
{
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * Redirect constructor.
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     * @param Context $context
     * @param Data $helper
     */
    public function __construct(
        Session $checkoutSession,
        OrderFactory $orderFactory,
        Context $context,
        Data $helper
    )
    {
        $this->orderFactory = $orderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getForm()
    {
        return $this->helper->getPaymentForm($this->getOrder());
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        $orderIncrementId = $this->checkoutSession->getLastRealOrderId();
        $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
        return $order;
    }
}