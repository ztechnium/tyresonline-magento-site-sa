<?php
namespace Hdweb\Rfc\Cron;
use Magento\Framework\App\Filesystem\DirectoryList;

class Getmargin
{
	protected $_logger;
	protected $scopeConfig;
	protected $_timezoneInterface;
	protected $objectManager;
	protected $_resouceConnection;
	protected $orderCollectionFactory;
	protected $purchaseorderitemFactory;
	protected $_filesystem;
	protected $_storeManager;
	protected $_transportBuilder;
	protected $inlineTranslation;
	protected $_timezone;
	const SALES_MARGIN_EMAIL_TEMPLATE  = 'hdwebcore/general/sales_margin_notify_email_template';
	
	public function __construct(
		\Psr\Log\LoggerInterface $logger,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
		\Magento\Framework\App\ResourceConnection $resouceConnection,
		\Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
		\Hdweb\Purchaseorder\Model\PurchaseorderitemFactory $purchaseorderitemFactory,
		\Magento\Framework\Filesystem $_filesystem,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Hdweb\Core\Model\Mail\Template\TransportBuilder $_transportBuilder,
		\Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
		\Magento\Framework\Stdlib\DateTime\DateTime $dateTime
	){
		$this->_logger = $logger;
		$this->objectManager = $objectManager;
		$this->scopeConfig = $scopeConfig;
		$this->_timezoneInterface = $timezoneInterface;
		$this->_resouceConnection = $resouceConnection;
		$this->orderCollectionFactory = $orderCollectionFactory;
		$this->purchaseorderitemFactory = $purchaseorderitemFactory;
		$this->_filesystem = $_filesystem;
		$this->_storeManager = $storeManager;
		$this->_transportBuilder = $_transportBuilder;
		$this->inlineTranslation = $inlineTranslation;
		$this->dateTime = $dateTime;
	}

	public function execute(){

		$from = $this->_timezoneInterface->date()->format('Y-m-d H:i:s');
		$now = new \DateTime();		

		$currentDate =  strtotime(date('Y-m-d'));
		
		$pastDate = strtotime("-1 day", $currentDate);
		$from_current_date = date('Y-m-d', $pastDate);
		$to_current_date = date('Y-m-d', $currentDate);
		
		$orderCollection = $this->orderCollectionFactory->create()
						->addAttributeToFilter('status', array('nin' => array('pending','canceled','pending_payment','voided','closed')))
						->addFieldToFilter('created_at', ['gteq' => $now->format($from_current_date.' 21:00:00')])
						->addFieldToFilter('created_at', ['lteq' => $now->format($to_current_date.' 23:59:59')]);

		//echo "<pre>"; print_r($orderCollection->getData());die;   
		
		$responseRowData   = array();
		$responseRowData[] = array('Order Number', 'Order Date', 'Order Status', 'Bill Name', 'Installer', 'Payment Method', 'Product Description', 'PO Quantity', 'SO Quantity', 'SO Item Total', 'PO Item Total', 'Margin Amount', 'Margin Percentage', 'Supplier', 'Vehicle', 'Model', 'Year');
		$supplierName = '';          
		
		$povendorObj = $this->objectManager->get('Hdweb\Purchaseorder\Model\Povendor');
		
		foreach($orderCollection as $orderData){
			$orderNumber = $orderData->getIncrementId();
			$orderDate = $orderData->getCreatedAt();

			$date = new \DateTime($orderDate.' +00'); 
			$date->setTimezone(new \DateTimeZone('Asia/Dubai'));

			$orderDate = $date->format('Y-m-d h:i A');

			$orderStatus = $orderData->getStatus();
			$orderFirstName = $orderData->getCustomerFirstname();
			$orderLastName = $orderData->getCustomerLastname();
			$orderBillName = $orderFirstName.' '.$orderLastName;
			$installer_id = $orderData->getPickupStore();
			$hdwebPOHelper = $this->objectManager->create('Hdweb\Purchaseorder\Helper\Data');
			$installer_details   = $hdwebPOHelper->getIntallerDetails($installer_id);
			$orderInstaller = $installer_details['name'];
			$payment = $orderData->getPayment();
			$method = $payment->getMethodInstance();
			$orderPaymentMethod = preg_replace( '/[^[:print:]\r\n]/', '',$method->getTitle());
			$orderGrandTotal = $orderData->getGrandTotal();
			$orderPoTotal = $orderData->getPoGrandtotal();
			$orderMargin = $orderData->getPoMargin();
			$orderMarginPercentage = $orderData->getPoMarginperc();
			$vehicle_make = $orderData->getMake();	
			$vehicle_model = $orderData->getModel();	
			$vehicle_year = $orderData->getYear();
			
			//Loop through each item and fetch data
			foreach ($orderData->getAllItems() as $item)
			{
			  // echo '<pre>';print_r($item->getData());
				$i = 1;
				$poTotalQty = 0;
				$totalQty = $item->getQtyOrdered();
				$purchaseorderitemcollection = $this->purchaseorderitemFactory->create()->getCollection()
											->addFieldToFilter('order_id', $orderData->getIncrementId())
											->addFieldToFilter('sku', $item->getSku());
				//echo '<pre>';print_r($purchaseorderitemcollection->getData());
				$poorderGrandTotal = $item->getRowTotal() - $item->getDiscountAmount();
				$poOrderGrandTotal = $poorderGrandTotal + $item->getTaxAmount();
				$perItemPrice = $poOrderGrandTotal / $totalQty;
				$productDesc = $item->getName();
				$soQty = $totalQty;
				if(count($purchaseorderitemcollection) > 0){
					foreach($purchaseorderitemcollection as $purchaseorderitemData){
						$poTotalQty += $purchaseorderitemData['qty'];
						$poQty = $purchaseorderitemData['qty'];
						$supplierName = $purchaseorderitemData['vendor_name'];
						$vendor_id = $purchaseorderitemData['vendor_id'];
						$povendorVat = $povendorObj->getCollection()->addFieldToFilter('id',$vendor_id)->getFirstItem()->getVatApplicable();
						$poTotal = $purchaseorderitemData['rowtotal'];
						if(!empty($povendorVat) && $povendorVat == 1){
							$poTotal =  $purchaseorderitemData['rowtotal'] + ($poTotal * 0.05);
						}
						$rowItemTotal = $perItemPrice * $purchaseorderitemData['qty'];
						$itemMargin = $rowItemTotal - $poTotal;
						
						$itemMarginPercentage = ($itemMargin / $rowItemTotal) * 100;
						$itemMarginPercentage = number_format($itemMarginPercentage, 2, '.', '');
						$responseRowData[] = array($orderNumber, $orderDate, $orderStatus, $orderBillName, $orderInstaller, $orderPaymentMethod, $productDesc, $poQty, $poQty, $rowItemTotal, $poTotal, $itemMargin, $itemMarginPercentage, $supplierName, $vehicle_make, $vehicle_model, $vehicle_year);
					}
				}
				
				$totalRow = $totalQty - $poTotalQty;
				if($totalRow > 0 ){
					$perItemPrice = $poOrderGrandTotal / $totalRow;
					$rowItemTotal = $perItemPrice * $totalRow;
					$itemMargin = $rowItemTotal - $rowItemTotal;
					$itemMarginPercentage = $itemMargin / $rowItemTotal;
					$responseRowData[] = array($orderNumber, $orderDate, $orderStatus, $orderBillName, $orderInstaller, $orderPaymentMethod, $productDesc, '', $totalRow, $rowItemTotal, '', '', '', '', $vehicle_make, $vehicle_model, $vehicle_year);
				}
			}
		}   
			
		$csvMediapath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'po_margin_reports/';
		if (!is_dir($csvMediapath)) {
			$csvMediapath = mkdir($csvMediapath, 0777, true);
			
		}
		$fileName = "TyresOnline-Sales-Margin-Report-".date('Y-m-d-h:i:s').".csv";
		$outputFile = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'po_margin_reports/' . $fileName;
		$handle = fopen($outputFile, 'w');
		foreach ($responseRowData as $response) {
			fputcsv($handle, $response);
		}
		$baseUrl  = $this->scopeConfig->getValue('web/unsecure/base_url');
		$filePath = $baseUrl . 'media/po_margin_reports/' . $fileName;
		/* Send Email Notification */ 
		$storeScope        = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$email = $this->scopeConfig->getValue('trans_email/ident_support/email', $storeScope);
		$name  = $this->scopeConfig->getValue('trans_email/ident_support/name', $storeScope);
		$from = array('email' => $email, 'name' => $name);
		$emailTo    = $this->scopeConfig->getValue('rnrtabsection/general/rnr_email_receipant', $storeScope);
		if($emailTo != ''){
			$to    = $this->scopeConfig->getValue('rnrtabsection/general/rnr_email_receipant', $storeScope);
		}
		$to = 'devendra@tyresonline.com';
		$copy_to = array('bipin@tyresonline.com');
		$emailTemplateId  = $this->scopeConfig->getValue(self::SALES_MARGIN_EMAIL_TEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->_storeManager->getStore()->getId());
		//$to_current_date = '2021-01-19';
		$templateVars = array('subject' => 'Sales Margin Report : '.$to_current_date, 'message' => 'Kindly please find the attached TyresOnline Sales Margin Report of the Day.');   
		$this->_transportBuilder->setTemplateIdentifier($emailTemplateId)
				->setTemplateOptions(
				[
					'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
					'store' => $this->_storeManager->getStore()->getId()
				])
				->setTemplateVars($templateVars)
				->setFrom($from)
				->addTo($to)
				->addBcc($copy_to)
				->addAttachment(file_get_contents($filePath), $fileName, 'application/csv');  
		$transport = $this->_transportBuilder->getTransport();
		$transport->sendMessage();
		$this->inlineTranslation->resume();
	}
}