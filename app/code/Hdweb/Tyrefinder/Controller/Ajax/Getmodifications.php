<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;

class Getmodifications extends \Magento\Framework\App\Action\Action
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
		$make  = $this->getRequest()->getParam('make');
		$model = $this->getRequest()->getParam('model');
		$year  = $this->getRequest()->getParam('year');
		$wheelApiKey = $this->finderhelper::WHEEL_SEARCH_APIKEY;
		$modifications_url = "https://api.wheel-size.com/v2/modifications/?user_key=".$wheelApiKey."&make=".$make."&model=".$model."&year=".$year."&region=medm";
        $modifications = file_get_contents($modifications_url);
        $modifications =json_decode($modifications);
		$selectHtml='';
		$Engines    = array();
        $engineHtml = "";
		//echo '<pre>';print_r($modifications->data);die;
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
			$engineHtml .= "<li class='engine-li1'>" . $key . "</li>";
            foreach ($country as $slugkey => $slugvalue) {
                $engineHtml .= '<li class="li-search engine-li2"><a href="javascript:void(0)" class="button button-primary-grey custom-size button-block button-rounded opacity button-text-overflow" onclick="getTyreSizes(\'' . $slugkey . '\',\'' . $slugvalue['name'] . '\')" >' . $slugvalue['name'] . '<sup class="lightsup" title="248hp | 185kW | 252PS">'.$slugvalue['power'].'hp</sup></a><span id="autosearch-span" style="display:none">'.$slugvalue['name'].' '. $slugvalue['power'].'hp</span></li>';
            }
			$engineHtml .= "";
        }
		
		$response['response'] = $engineHtml;
		$resultJson = $this->resultJsonFactory->create();
		return $resultJson->setData($response);
    }	
}