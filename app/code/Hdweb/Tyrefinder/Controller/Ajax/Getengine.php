<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;

class Getengine extends \Magento\Framework\App\Action\Action
{
    protected $_resource;
    protected $resultJsonFactory;
    protected $finderhelper;
    protected $config;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Hdweb\Tyrefinder\Helper\Data $finderhelper,
        \Magento\Eav\Model\Config $config
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_resource         = $resource;
        $this->finderhelper      = $finderhelper;
        $this->config            = $config;
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
        $make            = $this->getRequest()->getParam('make');
        $model           = $this->getRequest()->getParam('model');
        $year            = $this->getRequest()->getParam('year');
        $wheelApiKey     = $this->finderhelper::WHEEL_SEARCH_APIKEY;
        $modelengine_url = "https://api.wheel-size.com/v1/search/by_model/?user_key=" . $wheelApiKey . "&make=" . $make . "&model=" . $model . "&year=" . $year;
        $modelengine     = file_get_contents($modelengine_url);
        $modelengine     = json_decode($modelengine);

        $Engines    = array();
        $engineHtml = "";

        foreach ($modelengine as $key => $enginevalue) {
            $trim=array();
            $name                  = $enginevalue->market->name_en;
            $slug                  = $enginevalue->slug;
            $trim['name']                  = $enginevalue->trim;
            $trim['power']                  = $enginevalue->power->hp;
            $Engines[$name][$slug] = $trim;
        }

        foreach ($Engines as $key => $country) {
            $engineHtml .= "<div class='search_block'> <div class='products_holder'><ul> <li>" . $key . "</li>";
            foreach ($country as $slugkey => $slugvalue) {
                $engineHtml .= '<li class="li-search"><a href="javascript:void(0)" class="button button-primary-grey custom-size button-block button-rounded opacity button-text-overflow" onclick="getenginetyre(\'' . $slugkey . '\',\'' . $slugvalue['name'] . '\')" ><span>' . $slugvalue['name'] . '<sup class="lightsup" title="248hp | 185kW | 252PS">'.$slugvalue['power'].'hp</sup><span></a></li>';
            }
            $engineHtml .= "</ul></div></div>";
        }


        $enginesTyre = array();
		$enginesTyreArray = array();
      
        foreach ($modelengine as $key => $enginevalue) {
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
                /* if ($tyrevalue->rear->tire_width) {
                    if (isset($enginesTyre[$slug])) {
                        $enginesTyre[$slug] .= '<li class="'.$slug.'" ><a href="javascript:void(0)" onclick="showproduct(\'' . $frontwidth . '\',\'' . $frontheight . '\',\'' . $frontrim . '\',\'' . $rearwidth . '\',\'' . $rearheight . '\',\'' . $rearrim . '\')" >' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</a></li>';
                    } else {
                        $enginesTyre[$slug] = '<li class="'.$slug.'"><a href="javascript:void(0)" onclick="showproduct(\'' . $frontwidth . '\',\'' . $frontheight . '\',\'' . $frontrim . '\',\'' . $rearwidth . '\',\'' . $rearheight . '\',\'' . $rearrim . '\')" >' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</a></li>';
                    }

                } else {
                    if (isset($enginesTyre[$slug])) {
                        $enginesTyre[$slug] .= '<li class="'.$slug.'" ><a href="javascript:void(0)" onclick="showproduct(\'' . $frontwidth . '\',\'' . $frontheight . '\',\'' . $frontrim . '\')" >' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</a></li>';
                    } else {

                        $enginesTyre[$slug] = '<li class="'.$slug.'" ><a href="javascript:void(0)" onclick="showproduct(\'' . $frontwidth . '\',\'' . $frontheight . '\',\'' . $frontrim . '\')" >' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</a></li>';
                    }
                } */
				if ($tyrevalue->rear->tire_width) {
                    if (isset($enginesTyre[$slug])) {
                        $enginesTyre[$tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire].= '<li class="'.$slug.' li-search" ><a href="javascript:void(0)" class="button button-primary-grey custom-size button-block button-rounded opacity button-text-overflow front-rear-size" onclick="showproduct(\'' . $tyrevalue->front->tire_width . '\',\'' . $tyrevalue->front->tire_aspect_ratio . '\',\'' . $tyrevalue->front->rim_diameter . '\',\'' . $tyrevalue->rear->tire_width . '\',\'' . $tyrevalue->rear->tire_aspect_ratio . '\',\'' . $tyrevalue->rear->rim_diameter . '\')" ><span>' . $tyrevalue->front->tire . '<br> ' . $tyrevalue->rear->tire . '</span></a></li>';
                    } else {
                        $enginesTyre[$tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire]= '<li class="'.$slug.' li-search"><a href="javascript:void(0)" class="button button-primary-grey custom-size button-block button-rounded opacity button-text-overflow front-rear-size" onclick="showproduct(\'' . $tyrevalue->front->tire_width . '\',\'' . $tyrevalue->front->tire_aspect_ratio . '\',\'' . $tyrevalue->front->rim_diameter . '\',\'' . $tyrevalue->rear->tire_width . '\',\'' . $tyrevalue->rear->tire_aspect_ratio . '\',\'' . $tyrevalue->rear->rim_diameter . '\')" ><span>' . $tyrevalue->front->tire . '<br> ' . $tyrevalue->rear->tire . '</span></a></li>';
                    }

                } else {
                    if (isset($enginesTyre[$slug])) {
                        $enginesTyre[$tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire].= '<li class="'.$slug.' li-search" ><a href="javascript:void(0)" class="button button-primary-grey custom-size button-block button-rounded opacity button-text-overflow" onclick="showproduct(\'' . $tyrevalue->front->tire_width . '\',\'' . $tyrevalue->front->tire_aspect_ratio . '\',\'' . $tyrevalue->front->rim_diameter . '\')" ><span>' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</span></a></li>';
                    } else {

                        $enginesTyre[$tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire]= '<li class="'.$slug.' li-search" ><a href="javascript:void(0)" class="button button-primary-grey custom-size button-block button-rounded opacity button-text-overflow" onclick="showproduct(\'' . $tyrevalue->front->tire_width . '\',\'' . $tyrevalue->front->tire_aspect_ratio . '\',\'' . $tyrevalue->front->rim_diameter . '\')" ><span>' . $tyrevalue->front->tire . ' ' . $tyrevalue->rear->tire . '</span></a></li>';
                    }
                }
            }
        }
		foreach($enginesTyre as $key => $enginesTyreValues){
			$enginesTyreArray[] = $enginesTyreValues;
		}
        $response['engineHtml']  = $engineHtml;
        $response['enginesTyre'] = $enginesTyreArray;
        $resultJson              = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
