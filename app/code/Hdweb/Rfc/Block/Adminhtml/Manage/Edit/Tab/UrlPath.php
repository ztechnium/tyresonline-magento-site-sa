<?php
namespace Hdweb\Rfc\Block\Adminhtml\Manage\Edit\Tab;

use Magento\Framework\DataObject;

class UrlPath extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
	protected $_rfcmasterFactory;
	protected $storeManagerInterface;

	public function __construct(
		\Hdweb\Rfc\Model\RfcmasterFactory $rfcmasterFactory,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface
	) {
		$this->_rfcmasterFactory = $rfcmasterFactory;
		$this->storeManagerInterface = $storeManagerInterface;
	}

	public function render(DataObject $row)
	{
		$currentStore = $this->storeManagerInterface->getStore();
		//$mediaUrl = $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		//$csvPath = $mediaUrl.'rfcfiles/'.$row->getRfcManualPath();
		$rowMasterId = $row->getRfcMasterId();
		$redirectUrl = "";
		$rfcMastermodel = $this->_rfcmasterFactory->create();
		$rfcMastermodel->load($rowMasterId,'rfc_master_id');
		if($rfcMastermodel->getRfcActionUrl() != ''){
			$redirectUrl = $currentStore->getBaseUrl().'rfc/index/'.$rfcMastermodel->getRfcActionUrl();
		}
		
		return '<a target="_blank" href="' . $redirectUrl . '">' . "Run Manually" . '</a>';
	}
}