<?php
namespace Hdweb\Rfc\Controller\Adminhtml\rnr;

class Createpurchaseorder extends \Magento\Backend\App\Action
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
	protected $purchaseorderFactory;
	protected $purchaseorder;
	protected $purchaseorderitem;

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
        \Magento\Framework\Message\ManagerInterface $messageManager,
		\Hdweb\Purchaseorder\Model\PurchaseorderFactory $purchaseorderFactory,
		\Hdweb\Purchaseorder\Model\Purchaseorder $purchaseorder,
		\Hdweb\Purchaseorder\Model\Purchaseorderitem $purchaseorderitem
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
		$this->purchaseorderFactory        = $purchaseorderFactory;
		$this->purchaseorder			   = $purchaseorder;
		$this->purchaseorderitem		   = $purchaseorderitem;
    }
    public function execute()
    {
        $data           = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        $order_id       = $this->getRequest()->getParam('order_id');
        $vendor_code   = $this->getRequest()->getParam('vendor_code');
        $po_id   = $this->getRequest()->getParam('po_id');
		$order_no   = $this->getRequest()->getParam('order_no');
        $order          = $this->_order->load($order_id);
		$poStatusCollection = $this->purchaseorder->getCollection()
						->addFieldToFilter('main_table.orderreference_no',$order_no)
						->addFieldToFilter('main_table.rnr_purchase_order_response',array('null' => true));
		$poStatusCollectioncount = count($poStatusCollection->getData());	
		
		//die();
        // echo "<pre>";
        // print_r($order->getData());exit;
		// Generate RNR ERP Order
		$companyId   = trim($this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/companyid'));
		$apiUsername = trim($this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/username'));
		$apiPassword = trim($this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/pwd'));
		$branchCode = trim($this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/branch_code'));
		
		$baseUrl = $this->_storeManager->getStore()->getBaseUrl();
		$taxName = '';
		if(strpos($baseUrl, 'www') !== false) {
			$taxName = 'VAT-EC';
		} else {
			$taxName = 'VAT5%';
		}
		
		$executiveCode = '';
		if($order->getErpExecutiveCode() != ''){
			$executiveCode = $order->getErpExecutiveCode();
		}elseif($order->getSalesPersonId() != ''){
			$user = $this->userFactory->create();
			$user->load($order->getSalesPersonId());
			$executiveCode = $user->getUserErpExecutiveCode();
			
		}else{
			$executiveCode = trim($this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/erp_executive_code'));
		}
		if ($companyId != '' && $apiUsername != '' && $apiPassword != '' && $branchCode != '' && $executiveCode != '') {

			//$purchaseorderObj = $this->_objectManager->get('Hdweb\Purchaseorder\Model\Purchaseorder');
			$poCollection = $this->purchaseorder->getCollection()->addFieldToFilter('main_table.id',$po_id)->getFirstItem();
			$poreferenceNo 		= $poCollection->getPoreferenceNo();
			$poDate 			= $poCollection->getCreatedAt();
			$txnDate 			= date("d-M-Y H:i:s", strtotime($poDate));
			$requirementDate 	= date("d-M-Y", strtotime($poDate));
			$validityDate 		= date("d-M-Y", strtotime("+1 month", strtotime($poDate)));
			$poRemarks 			= $poCollection->getComment();
			$poTotal 			= $poCollection->getGrandtotal();
			$poTaxamount 		= $poCollection->getVat();
			$vatGroup = '';
			if($poTaxamount == 0){
				$vatGroup = 'INTERCOMPANY';
			}else{
				$vatGroup = $taxName;
			}
			$customerCode  = '';
			$addressCode   = 'ADDR-HM';
			$requestedDate = date('Y-m-d');
			$CreateDate    = $order->getCreatedAt();
			$datetime      = strtotime($CreateDate);
			$date          = date('d-M-Y H:i:s'); //date('Y-m-d', $datetime); // d.m.YYYY
			$time          = date('H:i:s', $datetime); // HH:ss
			$SubTotal      = $order->getSubtotal();
			$taxAmount     = $order->getTaxAmount();
			$Company       = "Stop & Go Auto Accessories Trading LLC";
			$Branch        = "ECommStopnGo";
			$OrderType     = "B2C";
			$ValidityDate  = $date . 'T' . $time . '' . date('P', $datetime);

			$SaleExecutive = "";
			$SalesPersonId = $order->getSalesPersonId();
			if (isset($SalesPersonId) && !empty($SalesPersonId)) {
				$user = $this->userFactory->create();
				$user->load($SalesPersonId);
				$firstName     = $user->getFirstName();
				$lastname      = $user->getLastname();
				$SaleExecutive = $firstName . ' ' . $lastname;
			}
			echo "<pre>";
			$orderpayment       = $this->orderRepository->get($order_id);
			$payment            = $orderpayment->getPayment();
			$PaymentReferenceNo = '';
			if($payment->getTransactionId() != ''){
				$PaymentReferenceNo = $payment->getTransactionId();
			}
			$method             = $payment->getMethodInstance();
			$PaymentMethod      = $method->getTitle(); //
			$installer_id = $order->getPickupStore();
			$hdwebPOHelper = $this->_objectManager->create('Hdweb\Purchaseorder\Helper\Data');
            $installer_details   = $hdwebPOHelper->getIntallerDetails($installer_id);
			$InstallerAddress   = $installer_details['address'] . ' ' . $installer_details['city'];
			echo '<pre>';
			$billingAddress  = $order->getBillingAddress();

			$customerId   = $order->getCustomerId();
			$CustomerCode = "";
/*			if (isset($customerId) && $customerId > 0) {
				$customer              = $this->customerRepositoryInterface->getById($customerId);
				$customerAttributeData = $customer->__toArray();
				$CustomerCode          = $customerAttributeData['custom_attributes']['customercode']['value'];
			}*/

			$LineNumber = 1;
			$orderitems = array();
			
			//$purchaseorderitemObj = $this->_objectManager->get('Hdweb\Purchaseorder\Model\Purchaseorderitem');
			//$poitemCollection = $this->purchaseorderitem->getCollection()->addFieldToFilter('poid',$po_id)->getFirstItem();
			$poitemCollection = $this->purchaseorderitem->getCollection()->addFieldToFilter('poid',$po_id);
			foreach ($poitemCollection as $item) {
				$poSku 	= $item->getSku();
				$poQty 	= $item->getQty();
				$poPrice 	= $item->getPrice();
				
				$_product 	= $this->_product->loadByAttribute('sku', $poSku);
				$itemCode   = $_product->getSku();
				if ($_product->getStgCode() != '') {
					$itemCode = $_product->getStgCode();
				}
				
				$poOrderitem = array('LineNumber' => $LineNumber,
						'ItemCode'                      => $itemCode, //need to confirm
						'ItemDescription'               => $_product->getName(),
						'UOM'                           => 'Nos.',
						'Variation'                     => '', //blank
						'Variation2'                    => '', //blank
						'Variation3'                    => '', //blank
						'Quantity'                      => $poQty,
						'Rate'                          => $poPrice,
						'DiscountPercentage'            => 0,
						'DiscountAmount'                => 0,
						'Tax1_Name'                     => 'VAT',//$taxName,
						'Tax1_Amount'                   => $poTaxamount,
						'Tax2_Name'                     => '',
						'Tax2_Amount'                   => 0,
						'Tax3_Name'                     => '',
						'Tax3_Amount'                   => 0,
						'Tax4_Name'                     => '',
						'Tax4_Amount'                   => 0,
						'Tax5_Name'                     => '',
						'Tax5_Amount'                   => 0,
					);
					$orderitems[] = $poOrderitem;
					$LineNumber++;
				
			}
			$country            = $this->_countryFactory->create()->loadByCode($billingAddress->getCountryId());
			$countryName        = $country->getName();
			$zipCode            = '00000';
			$middleName         = $billingAddress->getMiddlename();
			$orderData          = array(
				'TXNName'            => $order->getIncrementId().'-'.'PO-'.$po_id,
				'TXNDate'            => $txnDate,
				'CompanyCode'        => $companyId,
				'BranchCode'         => $branchCode,
				'SupplierCode'       => $vendor_code, //Need to confirm 
				'ExecutiveCode'      => $executiveCode,
				'CurrencyName'       => 'Emirati Dirham', //$order->getBaseCurrencyCode(), //Need to confirm
				'RequirementDate'    => $requirementDate,
				'ValidityDate'    	 => $validityDate,
				'ReferenceNumber'    => $order->getIncrementId().'-'.$poreferenceNo,
				'Remarks'       	 => $poRemarks,
				'SOName'       	 	 => $order->getIncrementId(),
				'TaxGroupName'       => $vatGroup,
				'Charge1_Name'       => 'Shipping Charges',
				'Charge1_Amount'     => 0,
				'Charge2_Name'       => 'Discount Charges',
				'Charge2_Amount'     => 0,
				'Charge3_Name'       => '',
				'Charge3_Amount'     => 0,
				'Charge4_Name'       => '',
				'Charge4_Amount'     => 0,
				'Charge5_Name'       => '',
				'Charge5_Amount'     => 0,
				'POItemLineModels'   => $orderitems,
				'ID'                 => '',
				'Code'               => '',

			);

			$orderRequest = json_encode($orderData);
			//echo '<pre>';print_r($orderRequest);die;
			$createOrderResponseData = $this->_objectManager->create('Hdweb\Rfc\Helper\Data')->getRnrCreatePurchaseOrder($orderRequest, $companyId, $apiUsername, $apiPassword);
			$rnrOrderResponse        = unserialize($createOrderResponseData);
			//echo '<pre>';print_r($rnrOrderResponse);die;
			$subject = '';
			if(isset($rnrOrderResponse['PurchaseItemDetails'])){
				if(isset($rnrOrderResponse['PurchaseItemDetails'][0]['PONo'])){
				//$purchaseorderFactory = $this->_objectManager->get('Hdweb\Purchaseorder\Model\PurchaseorderFactory');
				$subject = 'Purchase order created in RNR for order #'.$order->getIncrementId().'-'.'PO-'.$po_id;
				$this->_objectManager->create('Hdweb\Rfc\Helper\Data')->sendRnrEmailNotification($subject, $rnrOrderResponse);
				$purchaseorder_model = $this->purchaseorderFactory->create();
				$purchaseorder_model->load($po_id, 'id');
				if($poStatusCollectioncount == 0 || $poStatusCollectioncount == 1){
					$order->setErpPoStatus(1);
					$order->save();
				}
				$purchaseorder_model->setRnrPurchaseOrderResponse($createOrderResponseData);
				$purchaseorder_model->save();
				$this->_messageManager->addSuccess(__('RNR ERP Purchase Order generated successfully.'));
				$this->_redirect('sales/order/view', array('order_id' => $order_id));
				}
			}else {
				$this->_messageManager->addError(__('RNR ERP Purchase Order not generated because ' . $rnrOrderResponse));
				$this->_redirect('sales/order/view', array('order_id' => $order_id));
			}
			
		} else {
			$this->_messageManager->addError('Something went wrong with the RNR Configuration ERP API!.');
			$this->_redirect('sales/order/view', array('order_id' => $order_id));
		}
    }

}
