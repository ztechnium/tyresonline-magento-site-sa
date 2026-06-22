<?php
namespace Hdweb\Rfc\Block\Adminhtml\OrderEdit\Tab;

/**
 * Order custom tab
 *
 */
class Review extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_template = 'tab/view/google_review.phtml';

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
	
	public function getGoogleReviewStatus()
    {
        return $this->getOrder()->getGoogleReviewStatus();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Google Review');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Google Review');
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