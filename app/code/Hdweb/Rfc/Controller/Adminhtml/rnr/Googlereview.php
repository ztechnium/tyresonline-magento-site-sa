<?php
namespace Hdweb\Rfc\Controller\Adminhtml\rnr;

class Googlereview extends \Magento\Backend\App\Action
{
    protected $resultPageFactory = false;
    protected $_scopeConfig;
    protected $_order;
    protected $_product;
    protected $_storeManager;
    protected $_dir;
    protected $userFactory;
    protected $orderRepository;
    protected $customerRepositoryInterface;
    protected $_countryFactory;
    protected $_messageManager;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order $order,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory           = $resultPageFactory;
        $this->_scopeConfig                = $scopeConfig;
        $this->_order                      = $order;
        $this->_product                    = $product;
        $this->_storeManager               = $storeManager;
        $this->_dir                        = $dir;
        $this->userFactory                 = $userFactory;
        $this->orderRepository             = $orderRepository;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->_countryFactory             = $countryFactory;
        $this->_messageManager             = $messageManager;
    }
    public function execute()
    {
        $data           = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        $order_id       = $this->getRequest()->getParam('order_id');
		$order          = $this->_order->load($order_id);
		if($order->getId()){
			$orderNew = $this->orderRepository->get($order_id);
			$orderResourceModel = $this->_objectManager->get('Magento\Sales\Model\ResourceModel\Order');
			$customerEmail = $order->getCustomerEmail();
			$customerName = $order->getCustomerName();
			date_default_timezone_set('Asia/Dubai');
			$curentDate	= date('Y-m-d H:i:s');
			$this->_objectManager->create('Hdweb\Rfc\Helper\Data')->sendGoogleReviewEmailNotification($customerEmail, $customerName);
			$emailStatus = array();
			$emailStatus['email_status'] = 'Yes';
			$emailStatus['review_date'] = $curentDate;
			$orderNew->setGoogleReviewStatus(serialize($emailStatus));
			$orderResourceModel->save($orderNew);
			//$order->addStatusHistoryComment('Google review sent to customer successfully');
			//$order->save();
			$this->_messageManager->addSuccess(__('Google review request sent successfully.'));
		}else{
			$this->_messageManager->addError(__('Something went wrong with the google review submission.'));
		}
		$this->_redirect('sales/order/view', array('order_id' => $order_id));
    }

}
