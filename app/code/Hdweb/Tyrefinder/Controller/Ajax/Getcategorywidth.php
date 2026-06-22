<?php
namespace Hdweb\Tyrefinder\Controller\Ajax;

class Getcategorywidth extends \Magento\Framework\App\Action\Action
{   
    protected $resultJsonFactory;
    protected $productCollectionFactory;
    protected $productFactory;
    protected $_categoryFactory;
    
    public function __construct(\Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productFactory = $productFactory;
        $this->_categoryFactory          = $categoryFactory;
        parent::__construct($context);
    }
    
    public function execute()
    {   
        $postData = $this->getRequest()->getParams();
        $attributeCode = 'width';
        $categoryid = $postData['categoryid'];

        if(isset($categoryid) && !empty($categoryid)) {

                $selectHtml = '';
                $rearselectHtml = '';
                $response = array();        
                $category            = $this->_categoryFactory->create()->load($categoryid);
                $collection          = $this->productCollectionFactory->create()
                    ->addAttributeToSelect('width')
                    ->addCategoryFilter($category);

                $collection->setOrder('width', 'ASC');
                $collection->getSelect()->group('width');

                
                $attr = $this->productFactory->create()->getResource()->getAttribute('width');
                
                foreach ($collection as $productData) {
                    if ($attr->usesSource()) {
                           $optionText = $attr->getSource()->getOptionText($productData['width']);
                     }
                
                     $optionText=ucfirst($optionText);
                     $selectHtml .= '<li><a href="javascript:void(0)" onclick="getheight('.$productData['width'].',\''.$optionText.'\',\'front\')" id="front-width-'.$productData['width'].'">'.$optionText.'</a></li>';

                     $rearselectHtml .= '<li><a href="javascript:void(0)" onclick="getRearheight('.$productData['width'].',\''.$optionText.'\',\'rear\')" id="rear-width-'.$productData['width'].'">'.$optionText.'</a></li>';

                }

               // $selectHtml .= '</select>';
                $response['status'] = 'SUCCESS';
                $response['response'] = $selectHtml;
                $response['rearresponse'] = $rearselectHtml;
                $resultJson = $this->resultJsonFactory->create();
                return $resultJson->setData($response);
         }       
    }
}

