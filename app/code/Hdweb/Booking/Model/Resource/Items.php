<?php
/**
 * Copyright © 2015 Hdweb. All rights reserved.
 */

namespace Hdweb\Booking\Model\Resource;

class Items extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('hdweb_bookings', 'appointment_id');
    }
}
