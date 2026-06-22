<?php

namespace Hdweb\Rfc\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;

class Convertcustomer
{
	protected $_logger;
	protected $scopeConfig;
	protected $_timezoneInterface;
	protected $objectManager;
    protected $_filesystem;
    protected $_storeManager;
    protected $orderCollectionFactory;
    protected $orderRepository;
    protected $customerRepository;

	public function __construct(
		\Psr\Log\LoggerInterface $logger,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Filesystem $_filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager
	){
		$this->_logger = $logger;
		$this->objectManager = $objectManager;
		$this->scopeConfig = $scopeConfig;
		$this->_timezoneInterface = $timezoneInterface;
		$this->orderCollectionFactory = $orderCollectionFactory;
		$this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->_filesystem = $_filesystem;
        $this->_storeManager = $storeManager;
	}

	public function execute(){
		$orderCollection = $this->orderCollectionFactory->create()
						->addFieldToSelect('*')
						->addAttributeToFilter('customer_is_guest', ['eq'=>1]);
						//->setPageSize(15)
						//->addAttributeToFilter('customer_email', 'devendra.it@live.com');
		//echo '<pre>';print_r($orderCollection->getData());die;
		$websiteID = 2;
		
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/convert-customer.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		
		foreach($orderCollection as $order){
			if ($order->getId() && !$order->getCustomerId()) {
				$customerEmail = $order->getCustomerEmail();
				$customerId = $this->getCustomer($customerEmail);
				if($customerId){
					$order->setCustomerId($customerId);
					$order->setCustomerIsGuest(0);
					$this->orderRepository->save($order);
					$logger->info('Guest to Customer :- '.$order->getIncrementId().' Customer Email :- '.$order->getCustomerEmail());
				}
			}
		}
	}
	
	public function getCustomer($customerEmail)
    {
		$customerId = '';
		$website_id = 2;
        $customerFactory = $this->objectManager->get('\Magento\Customer\Model\CustomerFactory');
		$customer_data = $customerFactory->create();
		$customer_data->setWebsiteId($website_id);
		$customer_data->loadByEmail($customerEmail);
		$customer = $customer_data->getData();
		if($customer){
			$customerId = $customer['entity_id'];
		}
        return $customerId;
    }

    public function getStore()
    {
        return $this->_storeManager->getStore();
    }   
}