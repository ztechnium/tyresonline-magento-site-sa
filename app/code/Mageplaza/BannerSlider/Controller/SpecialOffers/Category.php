<?php

namespace Mageplaza\BannerSlider\Controller\SpecialOffers;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Category extends Action
{

    public function __construct(
        Context $context
    ) {
        //add here
        parent::__construct($context);
    }

    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }
}
