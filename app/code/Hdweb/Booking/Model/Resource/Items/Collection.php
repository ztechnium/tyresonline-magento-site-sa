<?php
/**
 * Copyright © 2015 Hdweb. All rights reserved.
 */

namespace Hdweb\Booking\Model\Resource\Items;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Hdweb\Booking\Model\Items', 'Hdweb\Booking\Model\Resource\Items');
    }
}
