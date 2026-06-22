<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;

class Getvehiclelist extends \Magento\Framework\App\Action\Action
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
        $this->_resource         = $resource;
		$this->finderhelper = $finderhelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $options           = '';
        $response          = array();
        $make              = $this->getRequest()->getParam('make');
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$mediaUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $selectHtml = "";
		$wheelApiKey = $this->finderhelper::WHEEL_SEARCH_APIKEY;
		$vehicleMakes_url = "https://api.wheel-size.com/v2/makes/?user_key=".$wheelApiKey."&region=medm";
		$vehicleMakes = file_get_contents($vehicleMakes_url);
        $vehicleMakes =json_decode($vehicleMakes);
		
		foreach ($vehicleMakes->data as $vehicle) {
			$var = "getmodel('$vehicle->slug' , '$vehicle->name')";
			$selectHtml .= '<li class="col vehicle_make li-search">
								<div class="box hover-transition">
								<a href="javascript:void(0)" class="d-block" title="'.$vehicle->name.'" onclick="' . $var . '" id ="make-'.$vehicle->slug.'">
								  <div class="image mx-auto"><img src="'.$vehicle->logo.'" width="240px" height="180px" alt="'.$vehicle->name.'" /></div>
								  <span class="title">' . $vehicle->name. '</span>
								</a>
							  </div>
							</li>';
		}
        $response['response'] = $selectHtml;
        $resultJson           = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
