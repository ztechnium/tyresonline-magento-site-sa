<?php
namespace Hdweb\Tyrefinder\Block;

class Finderpopup extends \Magento\Framework\View\Element\Template
{
    protected $_urlBuilder;
    protected $storeManager;
    protected $_resource;
    protected $collectionFactory;
    protected $_storeManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $collectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->_urlBuilder       = $context->getUrlBuilder();
        $this->storeManager      = $context->getStoreManager();
        $this->_resource         = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->_storeManager     = $storeManager;
        parent::__construct($context, $data);
    }

    public function getActiveCatrgory()
    {
        $categories = $this->collectionFactory->create()
            ->addAttributeToSelect('*')->addFieldToFilter('is_active', 1)->addFieldToFilter('level', array('gt' => 1));
        return $categories;
    }

    public function getCatalogSearchUrl()
    {
        $catalogSearchUrl = $this->_storeManager->getStore()->getBaseUrl() . 'car-tyres.html?';
        return $catalogSearchUrl;
    }
	
	public function getTyreCatalogSearchUrl()
    {
        $catalogSearchUrl = $this->_storeManager->getStore()->getBaseUrl() . 'all-tyres/car-tyres.html?';
        return $catalogSearchUrl;
    }
	
	public function getAutopartsSearchUrl()
    {
        $catalogSearchUrl = $this->_storeManager->getStore()->getBaseUrl() . 'all-auto-parts/';
        return $catalogSearchUrl;
    }

}
