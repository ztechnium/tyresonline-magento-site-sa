<?php

namespace Hdweb\Oilservice\Model\ResourceModel\Oilgrade;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Hdweb\Oilservice\Model\Oilgrade', 'Hdweb\Oilservice\Model\ResourceModel\Oilgrade');
    }

}
?>