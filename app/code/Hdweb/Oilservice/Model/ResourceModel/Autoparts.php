<?php
namespace Hdweb\Oilservice\Model\ResourceModel;

class Autoparts extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('hdweb_autoparts', 'autoparts_id');
    }
}
?>