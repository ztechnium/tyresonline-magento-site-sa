<?php
/**
 * Copyright © 2015 Hdweb. All rights reserved.
 */

namespace Hdweb\Booking\Controller\Adminhtml\Items;

class NewAction extends \Hdweb\Booking\Controller\Adminhtml\Items
{

    public function execute()
    {
        $this->_forward('edit');
    }
}
