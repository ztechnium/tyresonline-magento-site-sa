<?php

namespace Hdweb\Rfc\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;

class Rnrinvoice
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
						->addAttributeToFilter('erp_order_status', ['eq'=>1])
						->addAttributeToFilter('erp_invoice_status', ['eq'=>0]);
		//echo '<pre>';print_r($orderCollection->getData());die;
		$companyId   = trim($this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/companyid'));
		$apiUsername = trim($this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/username'));
		$apiPassword = trim($this->objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/pwd'));
		
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/rnr-invoice.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		
		foreach($orderCollection as $order){
			$rnrOrderResponse = unserialize($order->getRnrOrderResponse());
			if(!empty($rnrOrderResponse['OrderNo']) && $companyId != '' && $apiUsername != '' && $apiPassword != ''){
				$invoiceResponseData = $this->objectManager->create('Hdweb\Rfc\Helper\Data')->getRnrSalesInvoiceData($rnrOrderResponse['OrderNo'], $companyId, $apiUsername, $apiPassword);
				$rnrInvoiceResponse  = unserialize($invoiceResponseData);
				//echo '<pre>';print_r($rnrInvoiceResponse);die;
				if (isset($rnrInvoiceResponse['SaleInvoiceDetails'][0]['TXNID'])) {
					$order->setRnrInvoiceResponse($invoiceResponseData);
					$order->setErpInvoiceStatus(1);
					$order->save();
					$logger->info('RNR Invoice Generated for order :- '.$order->getIncrementId());
				}else{
					$logger->info('RNR Invoice Not Generated for order :- '.$order->getIncrementId());
				}
			}
			
		}
	}

    public function getStore()
    {
        return $this->_storeManager->getStore();
    }   
}