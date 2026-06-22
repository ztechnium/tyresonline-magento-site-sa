<?php
/**
 * Landofcoder
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Landofcoder.com license that is
 * available through the world-wide-web at this URL:
 * http://landofcoder.com/license
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Landofcoder
 * @package    Lof_AdvancedReports
 * @copyright  Copyright (c) 2016 Landofcoder (http://www.landofcoder.com/)
 * @license    http://www.landofcoder.com/LICENSE-1.0.html
 */
namespace Lof\AdvancedReports\Block\Adminhtml\Grid\Column\Renderer; 

use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;

/**
 * Adminhtml grid item renderer date
 */
class Telephone extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
		echo '<pre>';print_r($row->getData());die;
       // $orderIncrementId = $row->getData($this->getColumn()->getIndex()); 
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
		//$orderInterface = $objectManager->get('Magento\Sales\Api\Data\OrderInterface'); 
	//	$order = $orderInterface->loadByIncrementId($orderIncrementId);
		$orderId = $row->getOrderId();
		$resouceConnection = $objectManager->get('Magento\Framework\App\ResourceConnection');
		$connection = $resouceConnection->getConnection();
		$sql = "SELECT sales_order_address.telephone FROM sales_order_address LEFT JOIN sales_order ON sales_order_address.parent_id = sales_order.entity_id where sales_order_address.address_type='billing' AND sales_order_address.parent_id =".$orderId;
		echo $sql;die; 
		$telephoneInfo = $connection->fetchOne($sql);
		$telephone = '';
		if($telephoneInfo != ''){
			$telephone = $telephoneInfo;
		}
		
        return $orderId;
        
    }
}
