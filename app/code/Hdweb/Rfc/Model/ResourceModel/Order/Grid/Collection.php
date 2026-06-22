<?php

namespace Hdweb\Rfc\Model\ResourceModel\Order\Grid;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OriginalCollection;

/**
 * Order grid extended collection
 */
class Collection extends OriginalCollection
{

    protected function _initSelect()
    {
        $this->addFilterToMap('status', 'main_table.status');
        $this->addFilterToMap('customer_id', 'main_table.customer_id');

        parent::_initSelect();
    }

    protected function _renderFiltersBefore()
    {
    $joinTable = $this->getTable('sales_order_address');
    $this->getSelect()->joinLeft($joinTable, "main_table.entity_id = 
    {$joinTable}.parent_id AND {$joinTable}.address_type = 'billing'", 
      ['telephone']);
       parent::_renderFiltersBefore();
   }
    
}