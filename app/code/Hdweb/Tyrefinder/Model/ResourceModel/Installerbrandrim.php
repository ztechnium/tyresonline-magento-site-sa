<?php
namespace Hdweb\Tyrefinder\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Installerbrandrim extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('installer_brand_rim', 'id');
    }
}
