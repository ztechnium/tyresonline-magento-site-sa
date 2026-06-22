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
class Invoiceid extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
		
        $orderIncrementId = $row->getIncrementId();   
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
		$resouceConnection = $objectManager->get('Magento\Framework\App\ResourceConnection');
		$connection = $resouceConnection->getConnection();
		$sql = "SELECT sales_invoice_grid.increment_id FROM sales_invoice_grid LEFT JOIN sales_order ON sales_invoice_grid.order_id = sales_order.entity_id where sales_invoice_grid.order_increment_id=".$orderIncrementId;
		$invoiceId = $connection->fetchOne($sql);
		$invoiceIncrementId = '';
		if($invoiceId != ''){
			$invoiceIncrementId = $invoiceId;
		}
		
        return $invoiceIncrementId;
        
    }
}
