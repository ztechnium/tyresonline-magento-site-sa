<?php
/**
 * Copyright © 2015 Hdweb. All rights reserved.
 */

namespace Hdweb\Booking\Model;

class Items extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Hdweb\Booking\Model\Resource\Items');
    }
}
