<?php
namespace Hdweb\Vehicles\Controller\Ajax;

class Getsearchbymodel extends \Magento\Framework\App\Action\Action
{	
	protected $_resource;
	protected $resultJsonFactory;
	protected $vehiclesHelper;
	protected $config;
	
    public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Framework\App\ResourceConnection $resource,
	    \Hdweb\Vehicles\Helper\Data $vehiclesHelper,
		\Magento\Eav\Model\Config $config
	) {
		$this->resultJsonFactory 	= $resultJsonFactory;
		$this->_resource 			= $resource;
		$this->vehiclesHelper 		= $vehiclesHelper;
		$this->config            	= $config;
    	parent::__construct($context);
    }
    
    public function execute()
    {
		$widthattributeCode = 'width';
        $widthattribute     = $this->config->getAttribute('catalog_product', $widthattributeCode);
        $widhtOptions       = $widthattribute->getSource()->getAllOptions();

        $heightattributeCode = 'height';
        $heightattribute     = $this->config->getAttribute('catalog_product', $heightattributeCode);
        $heightOptions       = $heightattribute->getSource()->getAllOptions();

        $rimtattributeCode = 'rim';
        $rimattribute      = $this->config->getAttribute('catalog_product', $rimtattributeCode);
        $rimOptions        = $rimattribute->getSource()->getAllOptions();

        $options         = '';
        $response        = array();
		$make  = $this->getRequest()->getParam('make');
		$model = $this->getRequest()->getParam('model');
		$year  = $this->getRequest()->getParam('year');
		$modification  = $this->getRequest()->getParam('modification');
		$wheelApiKey = $this->vehiclesHelper::WHEEL_SEARCH_APIKEY;
		$searchby_model_url = "https://api.wheel-size.com/v2/search/by_model/?user_key=".$wheelApiKey."&make=".$make."&model=".$model."&year=".$year."&modification=".$modification."";
        $modelengine     = file_get_contents($searchby_model_url);
        $modelengine     = json_decode($modelengine);
		//echo '<pre>';print_r($modelengine);die;
        $Engines    = array();
        $engineHtml = "";
		
        foreach ($modelengine->data as $key => $enginevalue) {
            $trim=array();
            $name                  = $enginevalue->generation->name;
            $slug                  = $enginevalue->slug;
            $trim['name']          = $enginevalue->trim;
            $trim['power']         = $enginevalue->engine->power->hp;
            $Engines[$name][$slug] = $trim;
        }
		
        foreach ($Engines as $key => $country) {
            $engineHtml .= "<li class='col-12 d-none'><span class='block-title'>" . $key . "<span></li>";
            foreach ($country as $slugkey => $slugvalue) {
                $engineHtml .= '<li class="col"><a href="javascript:void(0)" class="button button-block button-primary-100 button-rounded filled-slide-right button-text-overflow" onclick="getTyreSizes(\'' . $slugkey . '\',\'' . $slugvalue['name'] . '\')" ><span>' . $slugvalue['name'] . '<sup class="lightsup" title="248hp | 185kW | 252PS">'.$slugvalue['power'].'hp</sup></span></a><span id="autosearch-span" style="display:none">'.$slugvalue['name'].' '. $slugvalue['power'].'hp</span></li>';
            }
            $engineHtml .= "</ul></div></div>";
        }


        $enginesTyre = array();
        $enginesTyreArray = array();
        //$enginesTyre = '';
      
        foreach ($modelengine->data as $key => $enginevalue) {
            $slug = $enginevalue->slug;
            $alltyresize = array();
            foreach ($enginevalue->wheels as $tyrekey => $tyrevalue) {

                $tyredata = $tyrevalue->front->tire_width . '/' . $tyrevalue->front->tire_aspect_ratio . 'R' . $tyrevalue->front->rim_diameter;

                $selectedWidthkey = array_search($tyrevalue->front->tire_width, array_column($widhtOptions, 'label'));
                $frontwidth       = $widhtOptions[$selectedWidthkey]['value'];

                $selectedHeightkey = array_search($tyrevalue->front->tire_aspect_ratio, array_column($heightOptions, 'label'));
                $frontheight       = $heightOptions[$selectedHeightkey]['value'];

                $selectedRimkey = array_search($tyrevalue->front->rim_diameter, array_column($rimOptions, 'label'));
                $frontrim       = $rimOptions[$selectedRimkey]['value'];

                $rearselectedWidthkey = array_search($tyrevalue->rear->tire_width, array_column($widhtOptions, 'label'));
                $rearwidth            = $widhtOptions[$rearselectedWidthkey]['value'];

                $rearselectedHeightkey = array_search($tyrevalue->rear->tire_aspect_ratio, array_column($heightOptions, 'label'));
                $rearheight            = $heightOptions[$rearselectedHeightkey]['value'];

                $rearselectedRimkey     = array_search($tyrevalue->rear->rim_diameter, array_column($rimOptions, 'label'));
                $rearrim                = $rimOptions[$rearselectedRimkey]['value'];
                $fronttire              = str_replace('Z', '', $tyrevalue->front->tire);
                $tyrevalue->front->tire = $fronttire;
                if (in_array($tyrevalue->front->tire, $alltyresize)) {
                    continue;
                }
                $alltyresize[] = $tyrevalue->front->tire;
                
				$oemClass='';
				
				if ($tyrevalue->rear->tire_width) {
                    if (isset($enginesTyre[$slug])) {
						if($tyrevalue->is_stock){
							$oemClass ='oem';
						}
                        $enginesTyre[$tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire].= '<li class="'.$oemClass.' '.$slug.' col" ><a href="javascript:void(0)" class="button button-block button-primary-100 button-rounded filled-slide-right button-text-overflow" onclick="vehiclesShowProduct(\'' . $tyrevalue->front->tire_width . '\',\'' . $tyrevalue->front->tire_aspect_ratio . '\',\'' . $tyrevalue->front->rim_diameter . '\',\'' . $tyrevalue->rear->tire_width . '\',\'' . $tyrevalue->rear->tire_aspect_ratio . '\',\'' . $tyrevalue->rear->rim_diameter . '\')" ><span>' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</span></a><span id="autosearch-span" style="display:none">' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</span></li>';
                    } else {
						if($tyrevalue->is_stock){
							$oemClass ='oem';
						}
						
                        $enginesTyre[$tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire]= '<li class="'.$oemClass.' '.$slug.' col" ><a href="javascript:void(0)" class="button button-block button-primary-100 button-rounded filled-slide-right button-text-overflow"  onclick="vehiclesShowProduct(\'' . $tyrevalue->front->tire_width . '\',\'' . $tyrevalue->front->tire_aspect_ratio . '\',\'' . $tyrevalue->front->rim_diameter . '\',\'' . $tyrevalue->rear->tire_width . '\',\'' . $tyrevalue->rear->tire_aspect_ratio . '\',\'' . $tyrevalue->rear->rim_diameter . '\')" ><span>' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</span></a><span id="autosearch-span" style="display:none">' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</span></li>';
                    }

                } else {
                    if (isset($enginesTyre[$slug])) {
						if($tyrevalue->is_stock){
							$oemClass ='oem';
						}
                        $enginesTyre[$tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire].= '<li class="'.$oemClass.' '.$slug.' col" ><a href="javascript:void(0)" class="button button-block button-primary-100 button-rounded filled-slide-right button-text-overflow" onclick="vehiclesShowProduct(\'' . $tyrevalue->front->tire_width . '\',\'' . $tyrevalue->front->tire_aspect_ratio . '\',\'' . $tyrevalue->front->rim_diameter . '\')" ><span>' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</span></a><span id="autosearch-span" style="display:none">' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</span></li>';
                    } else {
						if($tyrevalue->is_stock){
							$oemClass ='oem';
						}
                        $enginesTyre[$tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire]= '<li class="'.$oemClass.' '.$slug.' col" ><a href="javascript:void(0)" class="button button-block button-primary-100 button-rounded filled-slide-right button-text-overflow" onclick="vehiclesShowProduct(\'' . $tyrevalue->front->tire_width . '\',\'' . $tyrevalue->front->tire_aspect_ratio . '\',\'' . $tyrevalue->front->rim_diameter . '\')" ><span>' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</span></a><span id="autosearch-span" style="display:none">' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</span></li>';
                    }
                }
            }
        }
		
		//echo '<pre>';print_r($enginesTyre);die;
		foreach($enginesTyre as $key => $enginesTyreValues){
			$enginesTyreArray[] = $enginesTyreValues;
		}

        $response['engineHtml']  = $engineHtml;
        $response['enginesTyre'] = $enginesTyreArray;
        $resultJson              = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }	
}