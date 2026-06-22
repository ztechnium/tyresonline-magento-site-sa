<?php
namespace Hdweb\Oilservice\Controller\Index;

class Gettechdocrelatedproducts extends \Magento\Framework\App\Action\Action
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
    	$viewMode = $postData['view_mode'];
    	$assemblyGroupNodeIds = $postData['autoparts_catid'];
		if($assemblyGroupNodeIds == 101994){
			$apiResponse   = $this->getOilAutopartsRelatedCollection($carId, $assemblyGroupNodeIds);
		}else{
			$apiResponse   = $this->getAutopartsRelatedCollection($carId, $assemblyGroupNodeIds);
		}
		$result = $this->resultJsonFactory->create();
        $resultPage = $this->_resultPageFactory->create();
		$block = $resultPage->getLayout()
			->createBlock('Hdweb\Oilservice\Block\Oilservice')
			->setTemplate('Hdweb_Oilservice::autoparts_related_list.phtml')
			->setData('collection',$apiResponse)
			->setData('viewmode',$viewMode)
			->toHtml();
		
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
		$collection = $this->productCollectionFactory->create();
		$collection->addAttributeToSelect('*');
		$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
		$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
		$collection->addAttributeToFilter('article_number', ['in' => $articleArray]);
		
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
			foreach ($item->articleCriteria as $oilData) {
				$criteriaId = $oilData->criteriaId;
				if ($criteriaId != 2467) {
					continue;
				}
				$oilGradeArray[] = $oilData->formattedValue;
			}

		}
		if (count($oilGradeArray) > 0) {
			$oilGradeArray = array_unique($oilGradeArray);
			//$oilGradeOptionIds = array_unique($oilGradeOptionIds);
		}
		$collection = $this->productCollectionFactory->create();
		$collection->addAttributeToSelect('*');
		$collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
		$collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
		$collection->addAttributeToFilter('article_number', ['in' => $oilGradeArray]);
		//$collection->addAttributeToFilter('oil_grade', ['in' => $oilGradeOptionIds]);
		
		return $collection;
        /* End Techdoc API */
    }
}