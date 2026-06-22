<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;

class Getyear extends \Magento\Framework\App\Action\Action
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
		$wheelApiKey = $this->finderhelper::WHEEL_SEARCH_APIKEY;
		$modelyear_url = "https://api.wheel-size.com/v2/years/?user_key=".$wheelApiKey."&make=".$make."&model=".$model. "&region=medm";
        $modelyear = file_get_contents($modelyear_url);
        $modelyear =json_decode($modelyear);
	
		$selectHtml='';
		if (count($modelyear->data) > 0) {
			foreach ($modelyear->data as $yearOption) {
				//$var = "getengine($yearOption->slug , $yearOption->name)";
				$var = "getmodifications($yearOption->slug , $yearOption->name)";
				$selectHtml .= '<li class="li-search"><a href="javascript:void(0)" class="button button-primary-grey custom-size button-block button-rounded opacity button-text-overflow" title="'.$yearOption->name.'" onclick="'.$var.'" ><span>'.$yearOption->name.'</span></a></li>';
			}
		}

		$response['response'] = $selectHtml;
		$resultJson = $this->resultJsonFactory->create();
		return $resultJson->setData($response);
    }	
}