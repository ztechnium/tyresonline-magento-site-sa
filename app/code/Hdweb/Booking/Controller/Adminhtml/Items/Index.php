<?php
/**
 * Copyright © 2015 Hdweb. All rights reserved.
 */

namespace Hdweb\Booking\Controller\Adminhtml\Items;

class Index extends \Hdweb\Booking\Controller\Adminhtml\Items
{
    /**
     * Items list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('hdweb_booking::booking');
        $resultPage->getConfig()->getTitle()->prepend(__('Hdweb Appointments'));
        $resultPage->addBreadcrumb(__('Hdweb'), __('Hdweb'));
        $resultPage->addBreadcrumb(__('Appointments'), __('Appointments'));
        return $resultPage;
    }
}
