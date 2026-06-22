<?php
namespace Hdweb\Oilservice\Controller\Index;

class Getproductthumbnail extends \Magento\Framework\App\Action\Action
{	
	protected $resultJsonFactory;
	protected $productCollectionFactory;
	protected $productFactory;
	protected $scopeConfigInterface;
	protected $catalogImgHelper;
	
    public function __construct(\Magento\Framework\App\Action\Context $context,
    	\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Catalog\Model\ProductFactory $productFactory,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
		\Magento\Catalog\Helper\Image $catalogImgHelper
	) {
    	$this->resultJsonFactory = $resultJsonFactory;
		$this->productCollectionFactory = $productCollectionFactory;
		$this->productFactory = $productFactory;
		$this->scopeConfigInterface = $scopeConfigInterface;
		$this->catalogImgHelper = $catalogImgHelper;
        parent::__construct($context);
    }
    
    public function execute()
    {
    	$postData = $this->getRequest()->getParams();
    	$productId = $postData['product_id'];
		$vatvalue = $this->scopeConfigInterface->getValue('hdweb/general/vat_percentage');
		$product =  $this->productFactory->create()->load($productId);
        $imageUrl = $this->catalogImgHelper->init($product, 'category_page_grid')->setImageFile($product->getSmallImage()) 
                ->resize(200)
                ->getUrl();
        $baseimageUrl = $this->catalogImgHelper->init($product, 'product_page_main_image')->setImageFile($product->getImage())
                ->getUrl();
		$finalPrice = $product->getFinalPrice();
		$finalPricetax=($finalPrice * $vatvalue ) / 100;
		$finalPricewithtax=$finalPrice + $finalPricetax;
		$finalPricewithtax_four_Qty=$finalPricewithtax * 1;		
		$price= number_format($finalPricewithtax, 2);	
		$response['status'] = 'SUCCESS';
        $response['img_link'] = $imageUrl;
        $response['base_img_link'] = $baseimageUrl;
        $response['price'] = $price;
		$resultJson = $this->resultJsonFactory->create();
		return $resultJson->setData($response);
    }	
	
	
}

