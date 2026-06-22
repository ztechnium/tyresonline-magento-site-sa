<?php
namespace Hdweb\Vehicles\Controller\Ajax;

class Getmodificationsfromyear extends \Magento\Framework\App\Action\Action
{	
	protected $_resource;
	protected $resultJsonFactory;
	protected $vehiclesHelper;
	
    public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Framework\App\ResourceConnection $resource,
		\Hdweb\Vehicles\Helper\Data $vehiclesHelper
	) {
		$this->resultJsonFactory = $resultJsonFactory;
		$this->_resource = $resource;
		$this->vehiclesHelper = $vehiclesHelper;
    	parent::__construct($context);
    }
    
    public function execute()
    {
		$response = array();
		$make  = $this->getRequest()->getParam('make');
		$model = $this->getRequest()->getParam('model');
		$year  = $this->getRequest()->getParam('year');
		$wheelApiKey = $this->vehiclesHelper::WHEEL_SEARCH_APIKEY;
		$modifications_url = "https://api.wheel-size.com/v2/modifications/?user_key=".$wheelApiKey."&make=".$make."&model=".$model."&year=".$year."&region=medm";
        $modifications = file_get_contents($modifications_url);
        $modifications =json_decode($modifications);
		$selectHtml='';
		$Engines    = array();
        $engineHtml = "";
		if (count($modifications->data) > 0) {
			foreach ($modifications->data as $key => $modificationsvalue) {
				$trim=array();
				$name                  = $modificationsvalue->engine->fuel;
				$slug                  = $modificationsvalue->slug;
				$trim['name']          = $modificationsvalue->trim;
				$trim['power']         = $modificationsvalue->engine->power->hp;
				$Engines[$name][$slug] = $trim;
			}
		}
		foreach ($Engines as $key => $country) {
			$engineHtml .= "<li class='col-12 d-none'><span class='block-title'>" . $key . "<span></li>";
            foreach ($country as $slugkey => $slugvalue) {
                $engineHtml .= '<li class="col"><a href="javascript:void(0)" class="button button-block button-primary-100 button-rounded filled-slide-right button-text-overflow" onclick="getVehicleTyreSizes(\'' . $slugkey . '\',\'' . $slugvalue['name'] . '\')" ><span>' . $slugvalue['name'] . '<sup title="248hp | 185kW | 252PS">'.$slugvalue['power'].'hp</sup></span></a><span id="autosearch-span" style="display:none">'.$slugvalue['name'].' '. $slugvalue['power'].'hp</span></li>';
            }
			$engineHtml .= "";
        }
		
		$response['response'] = $engineHtml;
		$resultJson = $this->resultJsonFactory->create();
		return $resultJson->setData($response);
    }	
}