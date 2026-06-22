<?php

namespace Meetanshi\WorldpayHp\Controller\Payment;

use Magento\Framework\App\Action;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Redirect
 * @package Meetanshi\WorldpayHp\Controller\Payment
 */
class Redirect extends Action\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Redirect constructor.
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Worldpay Hosted Payment'));
        return $resultPage;
    }
}
