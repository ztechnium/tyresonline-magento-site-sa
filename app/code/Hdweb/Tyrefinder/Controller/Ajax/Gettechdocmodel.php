<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;

class Gettechdocmodel extends \Magento\Framework\App\Action\Action
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
        $manuId   = $this->getRequest()->getParam('manuId');
        $isDbVehicleList   = $this->getRequest()->getParam('is_db_vehiclelist');
		$cateId	= $this->getRequest()->getParam('cat_id');
		$oilChangeCatId = 9; //$this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('productsearch/general/oilchange_cat_id');
		if(isset($isDbVehicleList) && $isDbVehicleList == 1){
			$response    = array();
			$modelOptions  = "<option value=''>" . __('Model') . "</option>";
			$oilgradeVehicleModel   = $this->_oilserviceHelper->getOilgradeVehicleModel($manuId);
			if(count($oilgradeVehicleModel) > 0){
				foreach ($oilgradeVehicleModel as $modelData) {
					$modelOptions .= '<option value="' . $modelData['modelId'] . '" data-model="' . $modelData['modelname'] . '">' . $modelData['modelname'] . '</option>';
				}
			}
			$response['modelresponse'] = $modelOptions;
		}
		else if(isset($cateId) && ($cateId == $oilChangeCatId)){
			$options     = '';
			$response    = array();
			$options     = "<option value=''>" . __('Model') . "</option>";
			$listoptions = "";
			$oilgradeVehicleModel   = $this->_oilserviceHelper->getOilgradeVehicleModel($manuId);
			if(count($oilgradeVehicleModel) > 0){
				foreach ($oilgradeVehicleModel as $modelData) {
					$options .= '<option value="' . $modelData['modelId'] . '" data-model="' . $modelData['modelname'] . '">' . $modelData['modelname'] . '</option>';
					$listoptions .= sprintf("<li class='li-search'><a href=\"javascript:void(0)\" class='button button-primary-grey custom-size button-block button-rounded opacity button-text-overflow' title='".$modelData['modelname']."' onclick=\"getTechdocEngine('%s','%s','%s')\"><span>%s</span></a></li>", $modelData['modelId'], $modelData['modelname'], $modelData['modelname'], $modelData['modelname'], $modelData['modelname']);
				}
			}
			$response['response']     = $options;
			$response['listresponse'] = $listoptions;
		}
		else{
			/* TechDoc List */
			$function = 'getModelSeries';
			$params   = array(
				'country'           => 'AE',
				'lang'              => 'en',
				'linkingTargetType' => 'P',
				'manuId'            => $manuId,
				'provider'          => $this->_oilserviceHelper::TECDOC_MANDATOR,
			);
			
			$apiResponse   = $this->_oilserviceHelper->getTechdocApiConnection($function, $params);

			$options     = '';
			$response    = array();
			$options     = "<option value=''>" . __('Model') . "</option>";
			$listoptions = "";
			
			foreach ($apiResponse->data->array as $item) {
				//echo '<pre>';print_r($item->manuId);
				//$apiResponse[] = array('modelId' => $item->modelId, 'modelname' => $item->modelname, 'yearOfConstrFrom' => $item->yearOfConstrFrom);
				$now = 'Now';
				if (isset($item->yearOfConstrFrom)) {

					if (isset($item->yearOfConstrTo)) {
						$fullDate       = str_split($item->yearOfConstrTo, 4);
						$month          = $fullDate[1];
						$year           = $fullDate[0];
						$yearOfConstrTo = $month . '.' . $year;
						$now            = $yearOfConstrTo;
					}
					//    if(isset($item->yearOfConstrFrom)){
					$fullDate         = str_split($item->yearOfConstrFrom, 4);
					$month            = $fullDate[1];
					$year             = $fullDate[0];
					$yearOfConstrFrom = $month . '.' . $year;
					//    }
					$year      = $yearOfConstrFrom . ' - ' . $now;
					$modelName = $item->modelname . ' - (' . $year . ')';
				} else {
					$modelName = $item->modelname;
				}

				$options .= '<option value="' . $item->modelId . '" data-model="' . $item->modelname . '">' . $modelName . '</option>';
				//$listoptions .= '<li><a href="javascript:void(0)"
				$listoptions .= sprintf("<li class='li-search'><a href=\"javascript:void(0)\" class='button button-primary-grey custom-size button-block button-rounded opacity button-text-overflow' title='".$modelName."' onclick=\"getTechdocEngine('%s','%s','%s')\"><span>%s</span></a></li>", $item->modelId, $modelName, $item->modelname, $modelName, $modelName);
			}
			
			$response['response']     = $options;
			$response['listresponse'] = $listoptions;
		}
        
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}