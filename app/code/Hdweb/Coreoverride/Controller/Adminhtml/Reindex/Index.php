<?php

namespace Hdweb\Coreoverride\Controller\Adminhtml\Reindex;

use Magento\Framework\App\Filesystem\DirectoryList;

class Index extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
	protected $_storeManager;
    protected $_configWriter;
    protected $_scopeConfig;
    protected $_cacheTypeList;
    protected $_cacheFrontendPool;
	protected $_filesystem;
    protected $_indexerFactory;
    protected $_indexerCollectionFactory;
    protected $_logger;
	
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
		\Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
		\Magento\Framework\Filesystem $_filesystem,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \Magento\Indexer\Model\Indexer\CollectionFactory $indexerCollectionFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
		$this->_configWriter = $configWriter;
		$this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
		$this->_cacheTypeList = $cacheTypeList;
		$this->_cacheFrontendPool = $cacheFrontendPool;
        $this->_filesystem               = $_filesystem;
        $this->_indexerFactory = $indexerFactory;
        $this->_indexerCollectionFactory = $indexerCollectionFactory;
        $this->_logger = $logger;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
		try {
            $indexerCollection = $this->_indexerCollectionFactory->create();
            $ids = $indexerCollection->getAllIds();
            foreach ($ids as $id) {
                $idx = $this->_indexerFactory->create()->load($id);
                $idx->reindexAll($id); // this reindexes all
            }
            $this->flushCache(); // flush cache
            $this->messageManager->addSuccess(__('Data re-index successfully'));
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            $this->messageManager->addError(__($e->getMessage()));
        }
		$RefererUrl = $this->_redirect->getRefererUrl();
		return $resultRedirect->setPath($RefererUrl);
    }
    
    protected function _isAllowed()
    {
        return true;
    }

    public function flushCache(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$_cacheTypeList = $objectManager->create('Magento\Framework\App\Cache\TypeListInterface');
		$_cacheFrontendPool = $objectManager->create('Magento\Framework\App\Cache\Frontend\Pool');

        /* get all types of cache in system */
        $allTypes = array_keys($_cacheTypeList->getTypes());

        foreach ($allTypes as $type) {
            $_cacheTypeList->cleanType($type);
        }

		foreach ($_cacheFrontendPool as $cacheFrontend) {
			$cacheFrontend->getBackend()->clean();
		}
	}
	
}