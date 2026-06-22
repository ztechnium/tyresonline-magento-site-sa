<?php
namespace Hdweb\Rfc\Model\Plugin\Sales\Order;
 
class Grid
{
 
    public static $table = 'sales_order_grid';
    public static $leftJoinTable = 'sales_order';
 
    public function afterSearch($intercepter, $collection)
    {
        /* if ($collection->getMainTable() === $collection->getConnection()->getTableName(self::$table)) {
 
            $leftJoinTableName = $collection->getConnection()->getTableName(self::$leftJoinTable);
 
            $collection
                ->getSelect()
                ->joinLeft(
                    ['co'=>$leftJoinTableName],
                    "co.entity_id = main_table.entity_id",
                    [
                        'erp_order_status' => 'co.erp_order_status',
                        'erp_invoice_status' => 'co.erp_invoice_status',
                        'erp_po_status' => 'co.erp_po_status',
                        'pickupavailable' => 'co.pickupavailable',
                    ]
                );
 
            $where = $collection->getSelect()->getPart(\Magento\Framework\DB\Select::WHERE);
 
            $collection->getSelect()->setPart(\Magento\Framework\DB\Select::WHERE, $where);
 
            //echo $collection->getSelect()->__toString();die;
 
 
        } */
        return $collection;
 
 
    }
 
 
}