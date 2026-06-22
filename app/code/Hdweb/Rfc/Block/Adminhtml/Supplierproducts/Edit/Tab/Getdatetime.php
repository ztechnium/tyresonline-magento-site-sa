<?php
namespace Hdweb\Rfc\Block\Adminhtml\Supplierproducts\Edit\Tab;

use Magento\Framework\DataObject;

class Getdatetime extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
	protected $storeManagerInterface;

	public function __construct(
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface
	) {
		$this->storeManagerInterface = $storeManagerInterface;
	}

	public function render(DataObject $row)
	{
		$rfcDatetime = $row->getItemUpdatedDate();
		//$strtotime = strtotime($rfcDatetime);
		//$date = date('M j, Y',$strtotime);
        //$time = date('h:i:s A',$strtotime);
		
		//return $date." ".$time;
		return $rfcDatetime;
	}
}