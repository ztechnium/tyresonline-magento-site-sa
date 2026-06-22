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
class Fitmentcharge extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
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
		$currencysymbol = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
		$currency = $currencysymbol->getStore()->getCurrentCurrencyCode();
		$resouceConnection = $objectManager->get('Magento\Framework\App\ResourceConnection');
		$connection = $resouceConnection->getConnection();
		$sql = "SELECT purchase_order.grandtotal FROM purchase_order LEFT JOIN sales_order ON purchase_order.orderreference_no = sales_order.increment_id where purchase_order.po_type='fpo' AND purchase_order.orderreference_no =".$orderIncrementId;
		$fitmentTotal = $connection->fetchOne($sql);
		$fitmentCharges = '';
		if($fitmentTotal != ''){
			$fitmentCharges = $fitmentTotal;
		}
		
        return $fitmentCharges;
        
    }
}
