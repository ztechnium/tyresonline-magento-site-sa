<?php
namespace Hdweb\Rfc\Model\ResourceModel;

class Rfcmaster extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('rfc_master', 'rfc_master_id');
    }
}
?>