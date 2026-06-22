<?php

namespace Hdweb\Tyrefinder\Model\ResourceModel\Installerbrandrim;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Hdweb\Tyrefinder\Model\Installerbrandrim',
            'Hdweb\Tyrefinder\Model\ResourceModel\Installerbrandrim'
        );
    }
}
