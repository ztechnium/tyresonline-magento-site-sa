<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;

class Gettechdocengine extends \Magento\Framework\App\Action\Action
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
        $this->_resource         	= $resource;
		$this->_objectManager 		= $objectManager;
		$this->_oilserviceHelper 	= $oilserviceHelper;
        parent::__construct($context);
    }

    public function execute()
    {

        $manuId   = $this->getRequest()->getParam('manuId');
        $modelId  = $this->getRequest()->getParam('modelId');
		$isDbVehicleList   = $this->getRequest()->getParam('is_db_vehiclelist');
		$cateId	= $this->getRequest()->getParam('cat_id');
		$oilChangeCatId = 9; //$this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('productsearch/general/oilchange_cat_id');
		if(isset($isDbVehicleList) && $isDbVehicleList == 1){
			$response    = array();
			$engineOptions  = "<option value=''>" . __('Engine') . "</option>";
			$oilgradeVehicleEngine   = $this->_oilserviceHelper->getOilgradeVehicleEngine($manuId, $modelId);
			if(count($oilgradeVehicleEngine) > 0){
				foreach ($oilgradeVehicleEngine as $engineData) {
					$engineOptions .= '<option value="' . $engineData['carId'] . '">' . $engineData['carName'] . '</option>';
				}
			}
			$response['engineresponse'] = $engineOptions;
		}
		else if(isset($cateId) && ($cateId == $oilChangeCatId)){
			$options     = '';
			$response    = array();
			$listoptions = '';
			$options     = "<option value=''>" . __('Engine') . "</option>";
			$oilgradeVehicleEngine   = $this->_oilserviceHelper->getOilgradeVehicleEngine($manuId, $modelId);
			if(count($oilgradeVehicleEngine) > 0){
				foreach ($oilgradeVehicleEngine as $engineData) {
					$options .= '<option value="' . $engineData['carId'] . '">' . $engineData['carName'] . '</option>';
					$listoptions .= sprintf("<li class='li-search'><a href=\"javascript:void(0)\" class='button button-primary-grey custom-size button-block button-rounded opacity button-text-overflow' title='".$engineData['carName']."' onclick=\"getTechdocEngineSelectValue('%s','%s')\"><span>%s</span></a></li>", $engineData['carId'], $engineData['carName'], $engineData['carName']);
				}
			}
			$response['response']     = $options;
			$response['listresponse'] = $listoptions;
		}else{
			$function = 'getVehicleIdsByCriteria';
			$params   = array(
				'countriesCarSelection' => 'AE',
				'lang'                  => 'en',
				'carType'               => 'P',
				'manuId'                => $manuId,
				'modId'                 => $modelId,
				'powerHpType'           => true,
				'provider'              => $this->_oilserviceHelper::TECDOC_MANDATOR,
			);
			
			$apiResponse   = $this->_oilserviceHelper->getTechdocApiConnection($function, $params);

			$options     = '';
			$response    = array();
			$listoptions = '';
			$options     = "<option value=''>" . __('Engine') . "</option>";
			foreach ($apiResponse->data->array as $item) {
				$options .= '<option value="' . $item->carId . '">' . $item->carName . '</option>';
				$listoptions .= sprintf("<li class='li-search'><a href=\"javascript:void(0)\" class='button button-primary-grey custom-size button-block button-rounded opacity button-text-overflow' title='".$item->carName."' onclick=\"getTechdocEngineSelectValue('%s','%s')\"><span>%s</span></a></li>", $item->carId, $item->carName, $item->carName);
			}
			$response['response']     = $options;
			$response['listresponse'] = $listoptions;
		}
        $resultJson               = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
