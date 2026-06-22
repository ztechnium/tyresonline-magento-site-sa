<?php
namespace Hdweb\Rfc\Block\Adminhtml\Rfcmaster;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Hdweb\Rfc\Model\rfcmasterFactory
     */
    protected $_rfcmasterFactory;

    /**
     * @var \Hdweb\Rfc\Model\Status
     */
    protected $_status;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Hdweb\Rfc\Model\supplierproductsFactory $Supplierproducts
     * @param \Hdweb\Rfc\Model\Status $status
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Hdweb\Rfc\Model\RfcmasterFactory $rfcmasterFactory,
        \Hdweb\Rfc\Model\Status $status,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        $this->_rfcmasterFactory = $rfcmasterFactory;
        $this->_status = $status;
        $this->moduleManager = $moduleManager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('postGrid');
        $this->setDefaultSort('rfc_master_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);
        $this->setVarNameFilter('post_filter');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_rfcmasterFactory->create()->getCollection();
        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'rfc_master_id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'rfc_master_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
		
		$this->addColumn(
			'rfc_name',
			[
				'header' => __('RFC Name'),
				'index' => 'rfc_name',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'rfc_url',
			[
				'header' => __('RFC URL'),
				'index' => 'rfc_url',
                'type' => 'text',
			]
		);
		
		/* $this->addColumn(
			'rfc_username',
			[
				'header' => __('RFC Username'),
				'index' => 'rfc_username',
                'type' => 'text',
			]
		); */
		
		/* $this->addColumn(
			'rfc_password',
			[
				'header' => __('RFC Password'),
				'index' => 'rfc_password',
                'type' => 'text',
			]
		); */
		
		/* $this->addColumn(
			'rfc_database',
			[
				'header' => __('RFC Database'),
				'index' => 'rfc_database',
                'type' => 'text',
			]
		); */
		
		$this->addColumn(
			'rfc_datetime',
			[
				'header' => __('RFC Date & Time'),
				'index' => 'rfc_datetime',
				'type'      => 'datetime',
                'renderer'  => 'Hdweb\Rfc\Block\Adminhtml\Rfcmaster\Edit\Tab\Getdatetime'
			]
		);
		
		$this->addColumn(
			'rfc_enable',
			[
				'header' => __('Enable'),
				'index' => 'rfc_enable',
				'type' => 'text',
				//'options' => \Hdweb\Rfc\Block\Adminhtml\Rfc\Grid::getOptionArray3()
			]
		);
		
		$this->addColumn(
			'rfc_status',
			[
				'header' => __('Status'),
				'index' => 'rfc_status',
				'type' => 'options',
				'options' => \Hdweb\Rfc\Block\Adminhtml\Rfc\Grid::getOptionArray3()
			]
		);
		
		$this->addColumn(
			'rfc_action_url',
			[
				'header' => __('RFC Action URL'),
				'index' => 'rfc_action_url',
				'type' => 'text'
			]
		);
		
		$this->addColumn(
			'rfc_last_updated',
			[
				'header' => __('RFC Last Updated Datetime'),
				'index' => 'rfc_last_updated',
				'type'      => 'datetime',
                'renderer'  => 'Hdweb\Rfc\Block\Adminhtml\Rfcmaster\Edit\Tab\Getlastupdateddatetime'
			]
		);
		
        //$this->addExportType($this->getUrl('rfc/*/exportCsv', ['_current' => true]),__('CSV'));
		//$this->addExportType($this->getUrl('rfc/*/exportExcel', ['_current' => true]),__('Excel XML'));

        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }

        return parent::_prepareColumns();
    }

	
    /**
     * @return $this
     */
    // protected function _prepareMassaction()
    // {

    //     $this->setMassactionIdField('rfc_id');
    //     //$this->getMassactionBlock()->setTemplate('Hdweb_Rfc::rfc/grid/massaction_extended.phtml');
    //     $this->getMassactionBlock()->setFormFieldName('rfc');

    //     $this->getMassactionBlock()->addItem(
    //         'delete',
    //         [
    //             'label' => __('Delete'),
    //             'url' => $this->getUrl('rfc/*/massDelete'),
    //             'confirm' => __('Are you sure?')
    //         ]
    //     );

    //     $statuses = $this->_status->getOptionArray();

    //     $this->getMassactionBlock()->addItem(
    //         'status',
    //         [
    //             'label' => __('Change status'),
    //             'url' => $this->getUrl('rfc/*/massStatus', ['_current' => true]),
    //             'additional' => [
    //                 'visibility' => [
    //                     'name' => 'status',
    //                     'type' => 'select',
    //                     'class' => 'required-entry',
    //                     'label' => __('Status'),
    //                     'values' => $statuses
    //                 ]
    //             ]
    //         ]
    //     );


    //     return $this;
    // }
		

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('rfc/*/index', ['_current' => true]);
    }

    /**
     * @param \Hdweb\Rfc\Model\rfc|\Magento\Framework\Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
		return '#';
    }
	
}