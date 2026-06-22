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
namespace Lof\AdvancedReports\Block\Adminhtml\Advancedreport\Sales\Installerreport;

class Grid extends \Lof\AdvancedReports\Block\Adminhtml\Grid\AbstractGrid
{

    protected $_columnDate = 'main_table.created_at';
    protected $_columnGroupBy = '';
    protected $_defaultSort = 'period';
    protected $_defaultDir = 'ASC';
    protected $_resource_grid_collection = null;
    protected $_scopeconfig;
    public function _construct()
    {  
        parent::_construct(); 
        $this->setCountTotals(true);
        $this->setFilterVisibility(true);
        $this->setPagerVisibility(true);
        $this->setId('productsreportGrid');
        $this->setUseAjax(false);  
    }
 /**
     * {@inheritdoc}
     */
    public function getResourceCollectionName()
    {
        return 'Lof\AdvancedReports\Model\ResourceModel\Sales\Collection';
    }
    protected function _prepareColumns()
    {      
        $filterData = $this->getFilterData();

        $this->addColumn('installername', array(
            'header'        =>  __('Installer name'),
            'index'         => 'installername',
            'width'         => 100,
            'filter_data'   => $this->getFilterData(),
           // 'period_type'   => $this->getPeriodType(),
            //'renderer'      => 'Lof\AdvancedReports\Block\Adminhtml\Grid\Column\Renderer\Dateperiod',
            'totals_label'  => __('Total'),
            'html_decorators' => array('nobr'),
            'filter'    => false,
        ));

        $this->addColumn('uniqu_orders_count', array(
            'header'    => __('Number Of Orders'),
            'index'     => 'uniqu_orders_count',
            'type'      => 'number',
            'total'     => 'sum',
            'filter'    => false,
        ));

         $this->addColumn('orders_count', array(
            'header'    => __('Number Of Invoices'),
            'index'     => 'orders_count',
            'type'      => 'number',
            'total'     => 'sum',
            'filter'    => false,
        ));



        $this->addColumn('total_qtyty', array(
            'header'    => __('Items Ordered'), 
            'index'     => 'total_qtyty',   //   total_qty_ordered
            'type'      => 'number',
            'total'     => 'sum',
            'filter'    => false,
        ));

        // if ($this->getFilterData()->getStoreIds()) {
        //     $this->setStoreIds(explode(',', $this->getFilterData()->getStoreIds()));
        // }
        $currencyCode = $this->getCurrentCurrencyCode();
        $rate = $this->getRate($currencyCode);

        

        $this->addColumn('inv_grand_total', array(
            'header'            => __('Grand Total'),
            'type'              => 'currency',
            'currency_code'     => $currencyCode,
            'index'             => 'inv_grand_total',   // total_grandtotal_amount
            'total'             => 'sum',
           'rate'              => $rate,
            'filter'    => false,
        ));

        $this->addColumn('total_pograndtotal_amount', array(
            'header'            => __('PO Grand Total'),
            'type'              => 'currency',
            'currency_code'     => $currencyCode,
            'index'             => 'total_pograndtotal_amount',   // total_pograndtotal_amount
            'total'             => 'sum',
           'rate'              => $rate,
            'filter'    => false,
        ));

   $this->addColumn('margin', array(
            'header'            => __('Margin'),
            'type'              => 'currency',
            'currency_code'     => $currencyCode,
            'index'             => 'margin',   // total_pograndtotal_amount
            'total'             => 'sum',
           'rate'              => $rate,
            'filter'    => false,
        ));

          $this->addColumn('po_marginperc', array(
            'header'            => __('Margin(%)'),
            'type'              => 'number',
            'index'             => 'po_marginperc',   // total_pograndtotal_amount
            'total'             => 'sum',
            'filter'    => false,
        ));



        $this->addExportType('*/*/exportInstallerCsv', __('CSV'));
   

        return parent::_prepareColumns();
    }

    protected function _prepareCollection()
    {

        $filterData = $this->getFilterData();

        $report_type = $this->getReportType(); 
        $limit = $filterData->getData("limit", null);
        if(!$limit) {
            $limit = $this->_defaultLimit;
        } 
        // $report_field = $filterData->getData("report_field", null);
        // $report_field = $report_field?$report_field: "main_table.created_at";
        // $this->setCulumnDate($report_field);
        // $this->setDefaultSort("orders_count");
        // $this->setDefaultDir("DESC");
         
        $sales_person_id = $filterData->getData("sales_person_id", null);
        $fromdate = $filterData->getData("from", null);
        $todate = $filterData->getData("to", null);
        
        $storeIds = $this->_getStoreIds();  
        $resourceCollection = $this->_objectManager->create($this->getResourceCollectionName())
            ->prepareInstallerCollection() ;
            // ->setPeriodType($this->getPeriodType())
            // ->setDateColumnFilter($this->_columnDate)
            // ->addDateFromFilter($filterData->getData('filter_from', null))
            // ->addDateToFilter($filterData->getData('filter_to', null)) 
            // ->addStoreFilter($storeIds)
            // ->setAggregatedColumns($this->_getAggregatedColumns());
          
         if(isset($sales_person_id) && !empty($sales_person_id)){

            $resourceCollection->addFieldToFilter('sales_person_id',$sales_person_id);
         }   



         if(isset($fromdate) && !empty($fromdate)){

            $resourceCollection->addFieldToFilter('main_table.created_at', ['gteq' => $fromdate]);
                             //  ->addFieldToFilter('created_at', ['gteq' => $now->format('2018-05-01 H:i:s')]);
         }  

         if(isset($todate) && !empty($todate)){
            $todate = date('Y-m-d', strtotime("+1 day", strtotime($todate)));

            $resourceCollection->addFieldToFilter('main_table.created_at', ['lteq' => $todate]);
                              
         }   

        $this->setCollection($resourceCollection); 
       

         $this->_prepareTotals('po_marginperc,orders_count,inv_grand_total,total_pograndtotal_amount,uniqu_orders_count,total_qtyty,total_qty_ordered,total_qty_invoiced,total_income_amount,total_revenue_amount,total_profit_amount,total_invoiced_amount,total_paid_amount,total_refunded_amount,total_tax_amount,total_tax_amount_actual,total_shipping_amount,total_shipping_amount_actual,total_discount_amount,total_discount_amount_actual,total_canceled_amount,total_subtotal_amount,total_grandtotal_amount','total_qty','total_qtyty'); 
        return parent::_prepareCollection();
    } 

  
}