<?php

namespace Meetanshi\OrderUpload\Model\ResourceModel\OrderUpload;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package Meetanshi\OrderUpload\Model\ResourceModel\OrderUpload
 */
class Collection extends AbstractCollection
{
    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Meetanshi\OrderUpload\Model\OrderUpload', 'Meetanshi\OrderUpload\Model\ResourceModel\OrderUpload');
    }
}
