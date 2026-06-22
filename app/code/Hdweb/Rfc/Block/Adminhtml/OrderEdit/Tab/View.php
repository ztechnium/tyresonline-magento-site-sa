<?php
namespace Hdweb\Rfc\Block\Adminhtml\OrderEdit\Tab;

/**
 * Order custom tab
 *
 */
class View extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_template = 'tab/view/rnr_order_info.phtml';

    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }
    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }

    /**
     * Retrieve order Rnr response
     *
     * @return string
     */
    public function getOrderRnrOrderResponse()
    {
        return $this->getOrder()->getRnrOrderResponse();
    }
	
	/**
     * Retrieve invoice Rnr response
     *
     * @return string
     */
    public function getOrderRnrInvoiceResponse()
    {
        return $this->getOrder()->getRnrInvoiceResponse();
    }
	
	/**
     * Retrieve order increment id
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }
	
	/**
     * Retrieve purchase order vendor code
     *
     * @return string
     */
    public function getPoVendorCode($vendor_id)
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$povendorObj = $objectManager->get('Hdweb\Purchaseorder\Model\Povendor');
		$povendorCollection = $povendorObj->getCollection()->addFieldToFilter('id',$vendor_id)->getFirstItem();
		//echo '<pre>';print_r($povendorCollection->getData());
        return $povendorCollection->getCode();
    }
	
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('RNR ERP Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('RNR ERP Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        //return true;
		$state = $this->getOrder()->getStatus();
		$allstatus = array('tyres-delivered', 'po-generated', 'ordervalidated', 'installation_completed', 'complete');
		if (!in_array($state, $allstatus)){
			return false;
		}else{
			return true;
		}
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}