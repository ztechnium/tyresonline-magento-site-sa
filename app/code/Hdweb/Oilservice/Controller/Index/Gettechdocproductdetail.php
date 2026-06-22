<?php
namespace Hdweb\Oilservice\Controller\Index;

class Gettechdocproductdetail extends \Magento\Framework\App\Action\Action
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
    	$partNumber = $postData['partNumber'];

		$apiResponse = $this->getTechdocPartDetails($partNumber);
		
		$result = $this->resultJsonFactory->create();
        $resultPage = $this->_resultPageFactory->create();
		$block = $resultPage->getLayout()
			->createBlock('Hdweb\Oilservice\Block\Oilservice')
			->setTemplate('Hdweb_Oilservice::techdoc_product_details.phtml')
			->setData('data',$apiResponse)
			->toHtml();
		
        $result->setData(['output' => $block]);
        return $result;
        
    }
	
	public function getTechdocPartDetails($partNumber)
    {
			/* Start Techdoc API */
			$function = 'getArticleDirectSearchAllNumbersWithState';
			$params   = array(
				'articleCountry'       => 'AE',
				'lang'                 => 'en',
				'articleNumber'   	   => $partNumber,
				'provider'             => $this->_oilserviceHelper::TECDOC_MANDATOR,
			);
			$apiResponse   = $this->_oilserviceHelper->getTechdocApiConnection($function, $params);
			$articleIdArray  = array();
			foreach ($apiResponse->data->array as $item) {
				$articleIdArray[] = $item->articleId;
			}
			
			$articleId = $articleIdArray[0];
			$function = 'getArticles';
			$params = array();
			if(isset($_COOKIE['linkageTargetId']) && $_COOKIE['linkageTargetId'] != ''){
				$linkageTargetId = $_COOKIE['linkageTargetId'];
				$params = array(
					'articleCountry'      		=> 'AE',
					'lang'                 		=> 'en',
					'legacyArticleIds'	   		=> $articleId,
					'perPage'              		=> 1000,
					'linkageTargetId'           => $linkageTargetId,
					'linkageTargetType'         => 'P',
					//'includeArticleCriteria' 	=> true,
					'includeAll' 				=> true,
					//'includePDFs' 				=> true,
					//'includeImages' 			=> true,
					'provider'             		=> $this->_oilserviceHelper::TECDOC_MANDATOR,
				);
			}else{
				$params = array(
					'articleCountry'      		=> 'AE',
					'lang'                 		=> 'en',
					'legacyArticleIds'	   		=> $articleId,
					'perPage'              		=> 1000,
					//'linkageTargetId'           => $linkageTargetId,
					//'linkageTargetType'         => 'P',
					//'includeArticleCriteria' 	=> true,
					'includeAll' 				=> true,
					//'includePDFs' 				=> true,
					//'includeImages' 			=> true,
					'provider'             		=> $this->_oilserviceHelper::TECDOC_MANDATOR,
				);
			}
			//echo '<pre>';print_r($params);die;
            $apiResponse   = $this->_oilserviceHelper->getTechdocApiConnection($function, $params);
            $articleArray  = array();
			if(count($apiResponse->articles) < 1){
				$params = array(
					'articleCountry'      		=> 'AE',
					'lang'                 		=> 'en',
					'legacyArticleIds'	   		=> $articleId,
					'perPage'              		=> 1000,
					//'linkageTargetId'           => $linkageTargetId,
					//'linkageTargetType'         => 'P',
					//'includeArticleCriteria' 	=> true,
					'includeAll' 				=> true,
					//'includePDFs' 				=> true,
					//'includeImages' 			=> true,
					'provider'             		=> $this->_oilserviceHelper::TECDOC_MANDATOR,
				);
				
			 $apiResponse   = $this->_oilserviceHelper->getTechdocApiConnection($function, $params);	
			}

            foreach ($apiResponse->articles as $item) {
				$articleCriteria = $item->articleCriteria;
				
				foreach($articleCriteria as $articleInfo){
					$articleArray['criteriaInfo'][] = array('criteriaDescription' => $articleInfo->criteriaDescription, 'formattedValue' => $articleInfo->formattedValue);
				}
				if(isset($item->linkages)){
					//$linkageCriteria = $item->linkageCriteria;
					foreach($item->linkages as $linkageCriteria){
						$linkageInfo = $linkageCriteria->linkageCriteria;
						foreach($linkageInfo as $linkageInfoData){
							$articleArray['linkageInfo'][] = array('criteriaDescription' => $linkageInfoData->criteriaDescription, 'formattedValue' => $linkageInfoData->formattedValue);
						}
					}
				}
				if(isset($item->pdfs)){
					$articlePdf = $item->pdfs;
					foreach($articlePdf as $pdfInfo){
						//$pdfUrl = $pdfInfo->url;
						$articleArray['pdf'][] = $pdfInfo->url;
					}
				}
				
				if(isset($item->oemNumbers)){
					$oemNumbersArray = array();
					$articleOemNumbers = $item->oemNumbers;
					foreach($articleOemNumbers as $oemNumbersInfo){
						$oemNumbersArray['oemNumbers'][] = array('mfrName' => $oemNumbersInfo->mfrName, 'articleNumber' => $oemNumbersInfo->articleNumber);
					}
					$result = array();
					if(count($oemNumbersArray) > 0){
						foreach($oemNumbersArray['oemNumbers'] as $val) {
							if(array_key_exists('mfrName', $val)){
								$result[$val['mfrName']][] = $val;
							}else{
								$result[""][] = $val;
							}
						}
						$articleArray['oemNumbers'][] =  $result;
					}
					//echo '<pre>';print_r($result);die;
				}
				if(isset($item->images)){
					$articleImages = $item->images;
					foreach($articleImages as $images){
						//$imagesUrl = $images->imageURL800;
						$articleArray['images'][] = $images->imageURL800;
					}
				}
				if(isset($item->genericArticles)){
					$genericArticles = $item->genericArticles;
					foreach($genericArticles as $articleDescription){
						$articleArray['productGroup'][] = $articleDescription->genericArticleDescription;
					}
				}
				if(isset($item->misc->articleStatusDescription)){
					$status = $item->misc->articleStatusDescription;
					$articleArray['status'][] = $status;
				}
				if(isset($item->gtins)){
					$gtins = $item->gtins;
					$articleArray['gtins'] = $gtins;
				}
				if(isset($item->misc->quantityPerPackage)){
					$quantityPerPackage = $item->misc->quantityPerPackage;
					$articleArray['quantityPerPackage'][] = $quantityPerPackage;
				}
				if(isset($item->misc->quantityPerPartPerPackage)){
					$quantityPerPartPerPackage = $item->misc->quantityPerPartPerPackage;
					$articleArray['quantityPerPartPerPackage'][] = $quantityPerPartPerPackage;
				}
				
            }
		//echo '<pre>';print_r($articleArray);
		return $articleArray;
		/* End Techdoc API */
    }
	
		
}