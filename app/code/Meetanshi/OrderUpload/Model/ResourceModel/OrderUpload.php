<?php

namespace Meetanshi\OrderUpload\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class OrderUpload
 * @package Meetanshi\OrderUpload\Model\ResourceModel
 */
class OrderUpload extends AbstractDb
{
    /**
     *
     */
    protected function _construct()
    {
        $this->_init('meetanshi_orderupload', 'id');
    }
}
