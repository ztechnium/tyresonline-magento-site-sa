<?php

namespace Hdweb\Rfc\Block\Adminhtml\Rfcimport\Edit\Tab;

/**
 * Rfc edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Hdweb\Rfc\Model\Status
     */
    protected $_status;
	
	protected $_rfcmasterFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Hdweb\Rfc\Model\Status $status,
		 \Hdweb\Rfc\Model\RfcmasterFactory $rfcmasterFactory,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_status = $status;
		$this->_rfcmasterFactory = $rfcmasterFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /* @var $model \Hdweb\Rfc\Model\BlogPosts */
        $model = $this->_coreRegistry->registry('rfc');

        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form','enctype'=>'multipart/form-data', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Item Information')]);
		
        /* if (!$model->getId()) {
            $model->setData('is_active', $isElementDisabled ? '0' : '1');
        } */
		
		$fieldset->addField(
            'supplier_code',
            'select',
            [
                'name'        => 'supplier_code',
                'label'    => __('Select Supplier'),
                'required'     => true,
				'values' => $this->toSupplierOptionArray()
            ]
        );
		
		$fieldset->addType(
            'samplefile',
            '\Hdweb\Rfc\Block\Adminhtml\Rfcimport\Edit\Renderer\Samplefile'
        ); 
		
		$fieldset->addField(
            'sample_csv',
            'samplefile',
            [
                'name'  => 'sample_csv',
                'label' => __('Sample CSV'),
                'title' => __('Sample CSV'),
                'style' => 'display:none',    
            ]
        );
		
		
		$fieldset->addField(
			'csv_upload',
			'file',
			[
				'label' => __('Browse Import CSV'),
				'title' => __('Browse Import CSV'),
				'name' => 'csv_upload',
				'required' => true,
			   // 'disabled' => $isElementDisabled,
				//'value' =>'abc'               
			]
		);
		
        $form->setValues($model->getData());
        $this->setForm($form);
		
        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Rfc Import');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Rfc Import');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
    
    public function getTargetOptionArray(){
    	return array(
    				'_self' => "Self",
					'_blank' => "New Page",
    				);
    }
	
	public function toSupplierOptionArray()
    {
		$collection = $this->_rfcmasterFactory->create()->getCollection();
		$collection->addFieldToSelect('supplier_code');
		$collection->addFieldToSelect('attribute_code');
		//echo '<pre>';print_r($collection->getData());die;
		$supplierArray = array();
		$supplierArray[] = array('value' => '', 'label' => 'Select Supplier');
		foreach($collection->getData() as $supplierCode){
			if($supplierCode['supplier_code'] != '' && $supplierCode['attribute_code'] != ''){
				$supplierArray[] = array('value' => $supplierCode['supplier_code'].'-code-'.$supplierCode['attribute_code'], 'label' => $supplierCode['supplier_code']);
			}
			
		}
		$supplierArray = array_unique($supplierArray, SORT_REGULAR);
		//echo '<pre>';print_r($array);die;
		return $supplierArray;
        /* return [
            ['value' => 'Gulfcost', 'label' => 'Gulfcost'],
            ['value' => 'Lookin', 'label' => 'Lookin']
        ]; */
    }
}
