<?php

namespace Hdweb\Rfc\Model\ResourceModel\Supplierproducts;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Hdweb\Rfc\Model\Supplierproducts', 'Hdweb\Rfc\Model\ResourceModel\Supplierproducts');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }

}
?>