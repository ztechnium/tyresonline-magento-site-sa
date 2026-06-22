<?php
namespace Hdweb\Oilservice\Controller\Index;

class Getoileditproducts extends \Magento\Framework\App\Action\Action
{	
	protected $resultJsonFactory;
	protected $_resultPageFactory;
	protected $productCollectionFactory;
	protected $productFactory;
	protected $_objectManager;
	protected $_oilserviceHelper;
	
    public function __construct(\Magento\Framework\App\Action\Context $context,
    	\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Catalog\Model\ProductFactory $productFactory,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Hdweb\Oilservice\Helper\Data $oilserviceHelper
	) {
    	$this->resultJsonFactory = $resultJsonFactory;
		$this->_resultPageFactory = $resultPageFactory;
		$this->productCollectionFactory = $productCollectionFactory;
		$this->productFactory = $productFactory;
		$this->_objectManager = $objectManager;
		$this->_oilserviceHelper = $oilserviceHelper;
        parent::__construct($context);
    }
    
    public function execute()
    {
    	$postData = $this->getRequest()->getParams();
    	$carId = $postData['car_id'];
    	$assemblyGroupNodeIds = $postData['cateid'];
		$editBrandLabel = $postData['edit_brand_label'];
		$editFilterLabel = $postData['edit_filter_label'];
		$noteText = $postData['note_text'];
		$callFrom = $postData['call_from'];
		$filterLabel = array('edit_brand_label' => $editBrandLabel, 'edit_filter_label' => $editFilterLabel, 'note_text' => $noteText, 'call_from' => $callFrom);
    	$oil_litre = '';
		if(isset($postData['oil_litre'])){
			$oil_litre = $postData['oil_litre'];
		}
		$selected_brand_id = '';
		$selected_product_id = '';
		$selectedData = array();
		if(isset($postData['selected_brand_id'])){
			$selected_brand_id = $postData['selected_brand_id'];
			$selectedData['selected_brand_id'] = $postData['selected_brand_id'];
		}
		if(isset($postData['selected_product_id'])){
			$selected_product_id = $postData['selected_product_id'];
			$selectedData['selected_product_id'] = $postData['selected_product_id'];
		}

		if($assemblyGroupNodeIds == 101994){
			$apiResponse   = $this->getOilAutopartsRelatedCollection($carId, $assemblyGroupNodeIds);
		}else{
			$apiResponse   = $this->getAutopartsRelatedCollection($carId, $assemblyGroupNodeIds);
		}
		$result = $this->resultJsonFactory->create();
        $resultPage = $this->_resultPageFactory->create();
		if($callFrom == 'servicepack'){
			$block = $resultPage->getLayout()
                ->createBlock('Hdweb\Oilservice\Block\Oilservice')
                ->setTemplate('Hdweb_Oilservice::servicepack_oil_edit_filter.phtml')
                ->setData('collection',$apiResponse)
                ->setData('oil_litre',$oil_litre)
                ->setData('selected_data',$selectedData)
                ->setData('filter_label',$filterLabel)
                ->toHtml();
		}else{
			$block = $resultPage->getLayout()
                ->createBlock('Hdweb\Oilservice\Block\Oilservice')
                ->setTemplate('Hdweb_Oilservice::oil_edit_filter.phtml')
                ->setData('collection',$apiResponse)
                ->setData('oil_litre',$oil_litre)
                ->setData('selected_data',$selectedData)
                ->setData('filter_label',$filterLabel)
                ->toHtml();
		}
        $result->setData(['output' => $block]);
        return $result;
        
    }
	
	public function getAutopartsRelatedCollection($carId, $assemblyGroupNodeIds)
    {
        /* Start Techdoc API */
		$function = 'getArticles';
		$params   = array(
			'articleCountry'       => 'AE',
			'lang'                 => 'en',
			'linkageTargetType'    => 'P',
			'assemblyGroupNodeIds' => $assemblyGroupNodeIds,
			'linkageTargetId'      => $carId,
			'perPage'              => 1000,
			'provider'             => $this->_oilserviceHelper::TECDOC_MANDATOR,
		);
		$apiResponse   = $this->_oilserviceHelper->getTechdocApiConnection($function, $params);
		$articleArray  = array();
		foreach ($apiResponse->articles as $item) {
			$articleArray[] = $item->articleNumber;
		}
		$oemnumber = $this->_oilserviceHelper->getOEMNumbers($articleArray);
		//echo '<pre>';print_r($articleArray);
		//echo '<pre>';print_r($oemnumber);die;
		$articleNumbers = array_unique (array_merge ($articleArray, $oemnumber));
		
		$collection = $this->productCollectionFactory->create();
		$collection->addAttributeToSelect('*');
		$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
		$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
		//$collection->addAttributeToFilter('article_number', ['in' => $articleArray]);
		$collection->addAttributeToFilter('article_number', ['in' => $articleNumbers]);
		
		return $collection;
        /* End Techdoc API */
    }
	
	public function getOilAutopartsRelatedCollection($carId, $assemblyGroupNodeIds)
    {
        /* Start Techdoc API */
		$function = 'getArticles';
		$params   = array(
			'articleCountry'         => 'AE',
			'lang'                   => 'en',
			'linkageTargetType'      => 'P',
			'assemblyGroupNodeIds'   => $assemblyGroupNodeIds,
			'linkageTargetId'        => $carId,
			'perPage'                => 1000,
			'includeArticleCriteria' => true,
			'provider'               => $this->_oilserviceHelper::TECDOC_MANDATOR,
		);
		$apiResponse   = $this->_oilserviceHelper->getTechdocApiConnection($function, $params);
		$oilGradeArray = array();
		$oilGradeOptionIds = array();
		foreach ($apiResponse->articles as $item) {
			//echo '<pre>';print_r($item->articleCriteria);
			foreach ($item->articleCriteria as $oilData) {
				$criteriaId = $oilData->criteriaId;
				if ($criteriaId != 2467) {
					continue;
				}
				$oilGradeArray[] = $oilData->formattedValue;
				//$oilGradeOptionIds[] = $this->_oilserviceHelper->getAttributeDropdownvalueId($oilData->formattedValue, 'oil_grade');
			}

		}
		$collection = $this->productCollectionFactory->create();
		if (count($oilGradeArray) > 0) {
			$oilGradeArray = array_unique($oilGradeArray);
			//$oilGradeOptionIds = array_unique($oilGradeOptionIds);
			
			$collection->addAttributeToSelect('*');
			$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
			$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
			$collection->addAttributeToFilter('article_number', ['in' => $oilGradeArray]);
			//$collection->addAttributeToFilter('oil_grade', ['in' => $oilGradeOptionIds]);
		} /* else{
			$defaultPartsCategory = '18196'; // Lubricants
			$collection = $this->productCollectionFactory->create();
			$collection->addAttributeToSelect('*');
			$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
			$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
			$collection->addAttributeToFilter('parts_category', ['eq' => $defaultPartsCategory]);
		} */
		return $collection;
        /* End Techdoc API */
    }	
}