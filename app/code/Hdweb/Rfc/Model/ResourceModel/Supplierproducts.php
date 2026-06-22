<?php
namespace Hdweb\Rfc\Model\ResourceModel;

class Supplierproducts extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('rfc_supplierproducts', 'supplierproducts_id');
    }
}
?>