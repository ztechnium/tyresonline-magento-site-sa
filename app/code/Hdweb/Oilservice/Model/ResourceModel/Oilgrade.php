<?php
namespace Hdweb\Oilservice\Model\ResourceModel;

class Oilgrade extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('hdweb_oilgrade', 'oilgrade_id');
    }
}
?>