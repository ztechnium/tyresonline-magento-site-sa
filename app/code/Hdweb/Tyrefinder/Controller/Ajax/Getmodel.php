<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;

class Getmodel extends \Magento\Framework\App\Action\Action
{	
	protected $_resource;
	protected $resultJsonFactory;
	protected $finderhelper;
	
    public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Framework\App\ResourceConnection $resource,
		\Hdweb\Tyrefinder\Helper\Data $finderhelper
	) {
		$this->resultJsonFactory = $resultJsonFactory;
		$this->_resource = $resource;
		$this->finderhelper = $finderhelper;
    	parent::__construct($context);
    }
    
    public function execute()
    {
		$options  = '';
		$response = array();
		$make = $this->getRequest()->getParam('make');
		$wheelApiKey = $this->finderhelper::WHEEL_SEARCH_APIKEY;
		$VehicleModel_url = "https://api.wheel-size.com/v2/models/?user_key=".$wheelApiKey."&make=".$make. "&region=medm";
		$WheelVehicleModel = file_get_contents( $VehicleModel_url);
        $WheelVehicleModel =json_decode($WheelVehicleModel);
    
        $selectHtml='';
        if ($WheelVehicleModel && !empty($WheelVehicleModel->data) && count($WheelVehicleModel->data) > 0) {
			foreach ($WheelVehicleModel->data as $modelOption) {
				$var = "getyear('$modelOption->slug' , '$modelOption->name')";
				$selectHtml .= '<li class="li-search"><a href="javascript:void(0)" class="button button-primary-grey custom-size button-block button-rounded opacity" title="'.$modelOption->name.'" onclick="'.$var.'" ><span>'.$modelOption->name.'</span></a></li>';
			}
		}
    	
		$response['response'] = $selectHtml;
		$resultJson = $this->resultJsonFactory->create();
		return $resultJson->setData($response);
    }	
}