<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;
use Magento\Framework\Controller\ResultFactory;
class Addvehicle extends \Magento\Framework\App\Action\Action
{	
	protected $resultPageFactory;
	protected $_objectManager;
	protected $_oilserviceHelper;
	
    public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Hdweb\Oilservice\Helper\Data $oilserviceHelper
	) {
		$this->resultPageFactory = $resultPageFactory;
		$this->_objectManager 		= $objectManager;
		$this->_oilserviceHelper 	= $oilserviceHelper;
    	parent::__construct($context);
    }
    
    public function execute()
    {
		$postData = $this->getRequest()->getParams();
		$vehicle_make = $this->getRequest()->getParam('add_vehicle_make');
		$vehicle_make_label = $this->getRequest()->getParam('add_vehicle_make_label');
		$vehicle_model = $this->getRequest()->getParam('add_vehicle_model');
		$vehicle_model_label = $this->getRequest()->getParam('add_vehicle_model_label');
		$vehicle_engine  = $this->getRequest()->getParam('add_vehicle_engine');
		$vehicle_engine_label = $this->getRequest()->getParam('add_vehicle_engine_label');
		$mappingId = '';
		$is_ajax = $this->getRequest()->getParam('is_ajax');
		if(!empty($vehicle_make) && !empty($vehicle_model) && !empty($vehicle_engine)){
			$oilgradeCollection = $this->_objectManager->get('Hdweb\Oilservice\Model\OilgradeFactory')->create()->getCollection()
								  ->addFieldToSelect('oil_litre')
								  ->addFieldToSelect('mapping_id')
								  ->addFieldToFilter('make_id', ['eq' => $vehicle_make])
								  ->addFieldToFilter('model_id', ['eq' => $vehicle_model])
								  ->addFieldToFilter('engine_id', ['eq' => $vehicle_engine])
								  ->getFirstItem();				  
			$oilPerLitre = '';					  
			if(count($oilgradeCollection->getData()) > 0){
				$oilPerLitre = $oilgradeCollection->getOilLitre();
				$mappingId = $oilgradeCollection->getMappingId();
				
			}
			
			$vehicleArray = array('vehicle_make_id' => $vehicle_make, 'vehicle_make_label' => $vehicle_make_label, 'vehicle_model_id' => $vehicle_model, 'vehicle_model_label' => $vehicle_model_label, 'vehicle_engine_id' => $vehicle_engine, 'vehicle_engine_label' => $vehicle_engine_label, 'oil_per_litre' => $oilPerLitre, 'mapping_id' => $mappingId);
			$userVehicleKey = $this->_oilserviceHelper->createUserVehicleKey($vehicleArray);
			
			/* setcookie ("storedVehicleData[vehicle_make_id]", $vehicle_make, time() + (86400 * 30), "/");
			setcookie ("storedVehicleData[vehicle_make_label]", $vehicle_make_label, time() + (86400 * 30), "/");
			setcookie ("storedVehicleData[vehicle_model_id]", $vehicle_model, time() + (86400 * 30), "/");
			setcookie ("storedVehicleData[vehicle_model_label]", $vehicle_model_label, time() + (86400 * 30), "/");
			setcookie ("storedVehicleData[vehicle_engine_id]", $vehicle_engine, time() + (86400 * 30), "/");
			setcookie ("storedVehicleData[vehicle_engine_label]", $vehicle_engine_label, time() + (86400 * 30), "/");
			setcookie ("storedVehicleData[oil_per_litre]", $oilPerLitre, time() + (86400 * 30), "/");
			setcookie ("storedVehicleData[mapping_id]", $mappingId, time() + (86400 * 30), "/"); */
			
			//$this->messageManager->addSuccess(__('Vehicle added successfully'));
		}else{
			$this->messageManager->addError(__('Something went wrong.'));
		}
		$resultRedirect = $this->resultRedirectFactory->create();
		//$url = $this->_objectManager->get('Magento\Framework\UrlInterface');
		$redirect = $this->_objectManager->get('Magento\Framework\App\Response\RedirectInterface');
		if($is_ajax){
			return;
		}else{
			$result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
			$result->setUrl($redirect->getRefererUrl());
			return $result;
		}
		
    }	
}

