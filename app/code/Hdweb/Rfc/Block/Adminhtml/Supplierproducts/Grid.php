<?php
namespace Hdweb\Rfc\Block\Adminhtml\Supplierproducts;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Hdweb\Rfc\Model\supplierproductsFactory
     */
    protected $_supplierproductsFactory;

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
        \Hdweb\Rfc\Model\SupplierproductsFactory $SupplierproductsFactory,
        \Hdweb\Rfc\Model\Status $status,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        $this->_supplierproductsFactory = $SupplierproductsFactory;
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
        $this->setDefaultSort('supplierproducts_id');
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
        $collection = $this->_supplierproductsFactory->create()->getCollection();
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
            'supplierproducts_id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'supplierproducts_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
		
		$this->addColumn(
			'supplier_code',
			[
				'header' => __('Supplier Code'),
				'index' => 'supplier_code',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'item_code',
			[
				'header' => __('Item Code'),
				'index' => 'item_code',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'item_desc',
			[
				'header' => __('Item Desc'),
				'index' => 'item_desc',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'item_brand',
			[
				'header' => __('Item Brand'),
				'index' => 'item_brand',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'item_size',
			[
				'header' => __('Item Size'),
				'index' => 'item_size',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'item_runflat',
			[
				'header' => __('Item Runflat'),
				'index' => 'item_runflat',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'item_year',
			[
				'header' => __('Item Year'),
				'index' => 'item_year',
                'type' => 'number',
			]
		);
		
		$this->addColumn(
			'item_qty',
			[
				'header' => __('item_qty'),
				'index' => 'item_qty',
                'type' => 'number',
			]
		);
		
		$this->addColumn(
			'item_price',
			[
				'header' => __('Item Price'),
				'index' => 'item_price',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'item_price2',
			[
				'header' => __('Item Price 2'),
				'index' => 'item_price2',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'item_sell_price',
			[
				'header' => __('Item Sell Price'),
				'index' => 'item_sell_price',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'item_offer',
			[
				'header' => __('Item Offer'),
				'index' => 'item_offer',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'item_origin',
			[
				'header' => __('Item Origin'),
				'index' => 'item_origin',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'item_load',
			[
				'header' => __('Item Load'),
				'index' => 'item_load',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'type',
			[
				'header' => __('Type'),
				'index' => 'type',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'web_product_id',
			[
				'header' => __('Web Product Id'),
				'index' => 'web_product_id',
                'type' => 'number',
			]
		);
		
		$this->addColumn(
			'web_product_sku',
			[
				'header' => __('Web Product SKU'),
				'index' => 'web_product_sku',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'web_product_qty',
			[
				'header' => __('Web Product QTY'),
				'index' => 'web_product_qty',
                'type' => 'number',
			]
		);
		
		$this->addColumn(
			'web_product_price',
			[
				'header' => __('Web Product Price'),
				'index' => 'web_product_price',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'web_product_offer',
			[
				'header' => __('Web Product Offer'),
				'index' => 'web_product_offer',
                'type' => 'text',
			]
		);
		
		$this->addColumn(
			'web_product_status',
			[
				'header' => __('Web Product Status'),
				'index' => 'web_product_status',
                'type' => 'number',
			]
		);
		
		$this->addColumn(
			'item_updated_date',
			[
				'header' => __('Item Updated Date'),
				'index' => 'item_updated_date',
				'type'      => 'datetime',
                'renderer'  => 'Hdweb\Rfc\Block\Adminhtml\Supplierproducts\Edit\Tab\Getdatetime'
			]
		);
		
		$this->addColumn(
			'item_write_date',
			[
				'header' => __('Item Write Date'),
				'index' => 'item_write_date',
				'type'      => 'text',
                //'renderer'  => 'Hdweb\Rfc\Block\Adminhtml\Rfc\Edit\Tab\Getdatetime'
			]
		);
		
		$this->addColumn(
			'item_executed_date',
			[
				'header' => __('Item Executed Date'),
				'index' => 'item_executed_date',
				'type'      => 'datetime',
                //'renderer'  => 'Hdweb\Rfc\Block\Adminhtml\Rfc\Edit\Tab\Getdatetime'
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