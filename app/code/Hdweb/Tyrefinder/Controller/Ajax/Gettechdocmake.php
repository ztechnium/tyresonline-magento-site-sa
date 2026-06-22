<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;

class Gettechdocmake extends \Magento\Framework\App\Action\Action
{
    protected $_resource;
    protected $resultJsonFactory;
	protected $_objectManager;
	protected $_oilserviceHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\ResourceConnection $resource,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Hdweb\Oilservice\Helper\Data $oilserviceHelper
    ) {
        $this->resultJsonFactory 	= $resultJsonFactory;
        $this->_resource        	= $resource;
		$this->_objectManager 		= $objectManager;
		$this->_oilserviceHelper 	= $oilserviceHelper;
        parent::__construct($context);
    }

    public function execute()
    {
		$cateId	= $this->getRequest()->getParam('search_cat_id');
		$storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$mediaUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        
        $selectHtml = "";
		$options    = "";
		$options    = "<option disabled='' selected='' value=''>" . __('Vehicle') . "</option>";
		if(isset($cateId) && $cateId != ''){
			$oilgradeCollection = $this->_objectManager->get('Hdweb\Oilservice\Model\OilgradeFactory')->create()->getCollection()
					->distinct(true)
					->addFieldToSelect('make_id')
					->addFieldToSelect('make')
					->addFieldToFilter('oil_litre',  array('neq' => ''));
			$vehicleMake = array();				
			foreach($oilgradeCollection as $makeData){
				$makeId = $makeData->getMakeId();
				$make = $makeData->getMake();
				$slugName = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '',preg_replace('/\s+/', '-', $make) ));
				$vehicleMake[] = array('menuId' => $makeId, 'manuName' => $make, 'slug' => $slugName);
				$var = "getTechdocModel('".$makeId."','".$make."','".$slugName."')";
                $selectHtml .= '<li class="col vehicle_make li-search">
								<div class="box hover-transition">
								<a href="javascript:void(0)" class="d-block" title="'.$make.'" onclick="' . $var . '" id ="make-'.$slugName.'">
								  <div class="image car-logo-first-sprite mx-auto"><img src="'.$mediaUrl.'cars-logo/'.$slugName.'.png" alt="'.$make.'" /></div>
								  <span class="title">' . $make. '</span>
								</a>
							  </div>
							</li>';	
			    $options .= '<option value="' . $makeId . '" data-slug="' . $slugName . '">' . $make . '</option>';
			}
		}else{
			$techdocWheelVehicle = $this->_oilserviceHelper->getTechdocVehicleOptions();
			foreach($techdocWheelVehicle as $make){
			$var = "getTechdocModel('".$make['menuId']."','".$make['manuName']."','".$make['slug']."')";
			$selectHtml .= '<li class="col vehicle_make">
							<div class="box hover-transition">
							<a href="javascript:void(0)" class="d-block" title="'.$make['manuName'].'" onclick="' . $var . '" id ="make-'.$make['slug'].'">
							  <div class="image car-logo-first-sprite mx-auto"><img src="'.$mediaUrl.'cars-logo/'.$make['slug'].'.png" alt="'.$make['manuName'].'" /></div>
							  <span class="title">' . $make['manuName']. '</span>
							</a>
						  </div>
						</li>';
			$options .= '<option value="' . $make['menuId'] . '" data-slug="' . $make['slug'] . '">' . $make['manuName'] . '</option>';				
			}
		}
        $response['response'] = $selectHtml;
        $response['optionresponse'] = $options;
        $resultJson           = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}