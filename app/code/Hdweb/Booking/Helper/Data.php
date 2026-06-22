<?php
/**
 * Copyright © 2015 Brainvire . All rights reserved.
 */
namespace Hdweb\Booking\Helper;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $eavConfig;
    protected $_objectManager;

	/**
     * @param \Magento\Framework\App\Helper\Context $context
     */
	public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Catalog\Model\ResourceModel\ProductFactory   $attributeLoading,
         \Magento\Eav\Model\Config $eavConfig
	) {
		parent::__construct($context);
        $this->eavConfig = $eavConfig;
        $this->_resource = $resource;
        $this->_attributeLoading = $attributeLoading;
        $this->_objectManager = $objectmanager;
	}

	public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAttributeOptionId($attribute,$label)
    {
        $poductReource=$this->_attributeLoading->create();
        $attr = $poductReource->getAttribute($attribute);
         if ($attr->usesSource()) {
                return  $option_id = $attr->getSource()->getOptionId($label);
         }
    }

    public function getDates()
    {   
      $numberofdays  = 7;
      $dates=array();
      for ($i = 0; $i < $numberofdays; $i++) {
          // Set the timestamp
          // This starts in 2 days
          $timestamp = strtotime('+ ' . ($i) . ' days');
          // Set the date value
          $date = date('Y-m-d', $timestamp);
          // Set the formatted date value
          $date_formatted = date('d-m-Y', $timestamp);
          
          $nameOfDay      = strtolower(date('l', strtotime($date)));
          if($nameOfDay == 'sunday'){ // skipped friday
              continue;
          }
          // Place the date into the $dates array
          $dates[] = $date_formatted;
        }

      return $dates;
    }
}