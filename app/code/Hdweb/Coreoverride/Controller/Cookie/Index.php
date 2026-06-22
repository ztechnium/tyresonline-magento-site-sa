<?php
namespace Hdweb\Coreoverride\Controller\Cookie;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
	protected $_storeManager;
    protected $_configWriter;
    protected $_scopeConfig;
    protected $_cacheTypeList;
    protected $_cacheFrontendPool;
    protected $storeManager;
    /**
    * @var \Magento\Framework\Stdlib\CookieManagerInterface
    */
    protected $_cookieManager;
    /**
    * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
    */
    protected $_cookieMetadataFactory;
    protected $customerSession;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        parent::__construct($context);
        
    }

    public function execute()
    {
        $this->customerSession->setOfflineMethod(true);
        $resultRedirect = $this->resultRedirectFactory->create();
        $route = 'customer/account'; // w/o leading '/'
        $store = $this->storeManager->getStore();
        $redirectUrl= $store->getBaseUrl();
        $this->messageManager->addSuccess(__('Offline payment method is available'));
       // $url = $store->getUrl($route); // second arg can be omitted 
        $resultRedirect->setUrl($redirectUrl);
        return $resultRedirect;
     }
        
	protected function _isAllowed()
	{
		return true;
	}
}