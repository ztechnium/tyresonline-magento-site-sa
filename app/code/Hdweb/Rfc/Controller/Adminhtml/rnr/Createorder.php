<?php
namespace Hdweb\Rfc\Controller\Adminhtml\rnr;

class Createorder extends \Magento\Backend\App\Action
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
        //require $this->_dir->getPath('pub') . '/gcc-connection.php';

        $data           = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        $order_id       = $this->getRequest()->getParam('order_id');
        $rnr_order_id   = $this->getRequest()->getParam('rnr_order_id');
        $companyId      = $this->getRequest()->getParam('Company');
        $apiUsername    = $this->getRequest()->getParam('Username');
        $apiPassword    = $this->getRequest()->getParam('Password');
        $order          = $this->_order->load($order_id);
		$baseUrl = $this->_storeManager->getStore()->getBaseUrl();
		$taxName = '';
		if(strpos($baseUrl, 'www') !== false) {
			$taxName = 'VAT';
		} else {
			$taxName = 'VAT5%';
		}
        // echo "<pre>";
        // print_r($order->getData());exit;

        if ($rnr_order_id != '') {
            if ($companyId != '' && $apiUsername != '' && $apiPassword != '') {
                $invoiceResponseData = $this->_objectManager->create('Hdweb\Rfc\Helper\Data')->getRnrSalesInvoiceData($rnr_order_id, $companyId, $apiUsername, $apiPassword);
                $rnrInvoiceResponse  = unserialize($invoiceResponseData);
                //echo '<pre>';print_r($rnrInvoiceResponse);die;
				$orderNew = $this->orderRepository->get($order_id);
				$orderResourceModel = $this->_objectManager->get('Magento\Sales\Model\ResourceModel\Order');
                if (isset($rnrInvoiceResponse['SaleInvoiceDetails'][0]['TXNID'])) {
                    /* $order->setRnrInvoiceResponse($invoiceResponseData);
					$order->setErpInvoiceStatus(1);
                    $order->save(); */
					$orderNew->setRnrInvoiceResponse($invoiceResponseData);
					$orderNew->setErpInvoiceStatus(1);
					$orderResourceModel->save($orderNew);
                    $this->_messageManager->addSuccess(__('RNR Invoice generated successfully.'));
                    $this->_redirect('sales/order/view', array('order_id' => $order_id));
                } else {
                    $this->_messageManager->addError(__('RNR Invoice not exist.'));
                    $this->_redirect('sales/order/view', array('order_id' => $order_id));
                }
            } else {
                $this->_messageManager->addError('Something went wrong with the RNR ERP API!.');
                $this->_redirect('sales/order/view', array('order_id' => $order_id));
            }

        } else {
            // Generate RNR ERP Order
            $companyId   = trim($this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/companyid'));
            $apiUsername = trim($this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/username'));
            $apiPassword = trim($this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/pwd'));
            $branchCode = trim($this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/branch_code'));
            
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

                $customerCode  = '';
                $addressCode   = 'ADDR-HM';
                $requestedDate = date('Y-m-d');
                $CreateDate    = $order->getCreatedAt();
                $datetime      = strtotime($CreateDate);
                $date          = date('d-M-Y H:i:s', $datetime); //date('d-M-Y H:i:s');  // d.m.YYYY
                $requestedDate = date('Y-m-d', $datetime);                
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
				if($payment->getLastTransId() != ''){
					$parentTranData = $payment->getAdditionalInformation();
					if(isset($parentTranData['checkout_id'])){
						$PaymentReferenceNo = $parentTranData['checkout_id'];	
					}else{
						$PaymentReferenceNo = $payment->getLastTransId();
					}
				}else{
					$PaymentReferenceNo = 'Cash on delivery';
				}
                $method             = $payment->getMethodInstance();
                $PaymentMethod      = $method->getTitle();
				$installer_id = $order->getPickupStore();
				$hdwebPOHelper = $this->_objectManager->create('Hdweb\Purchaseorder\Helper\Data');
                $installer_details   = $hdwebPOHelper->getIntallerDetails($installer_id);
				$country = $this->_objectManager->create('\Magento\Directory\Model\Country')->load($installer_details['country'])->getName();
                $InstallerAddress   = $installer_details['address'] . ' ' . $installer_details['city'];
                echo '<pre>';
				if($order->getPlate() != ''){
					$vehiclePlate = $order->getPlate();
				}else{
					$vehiclePlate = $order->getIncrementId();
				}
                $billingAddress  = $order->getBillingAddress();

                $customerId   = $order->getCustomerId();
                $CustomerCode = "";
/*                if (isset($customerId) && $customerId > 0) {
                    $customer              = $this->customerRepositoryInterface->getById($customerId);
                    $customerAttributeData = $customer->__toArray();
                    $CustomerCode          = $customerAttributeData['custom_attributes']['customercode']['value'];
                }
*/
                //$LineNumber = 1;
				if($order->getPickupLocation()){
					$pickup_detail = unserialize($order->getPickupLocation());
					if(isset($pickup_detail['pick_type'])) {
						$LineNumber = count($order->getAllVisibleItems()) + 2;	
					}
				}else{
					$LineNumber = count($order->getAllVisibleItems()) + 1;	
				}
                $orderitems = array();
				$itemCode   = '';
				$rim_diameter = array();
				$totalQty = 0;
                foreach ($order->getAllVisibleItems() as $item) {

                    $_product = $this->_product->load($item->getProductId());
                    // $productionYear = $_product->getAttributeText('production_year');
                    $gcCode     = $_product->getGcItemCode();
                    $qtyOrdered = $item->getQtyOrdered();
					$totalQty += $item->getQtyOrdered();
                    $price      = $item->getPrice();
                    $itemCode   = $_product->getSku();
                    if ($_product->getStgCode() != '') {
                        $itemCode = $_product->getStgCode();
                    }
					$rim_diameter[] = $_product->getResource()->getAttribute("rim")->getFrontend()->getValue($_product);
					$year = $_product->getResource()->getAttribute("year")->getFrontend()->getValue($_product);

                    // $tax            = $item->getTaxAmount();
                    // $totalRate      = $item->getPrice() * $item->getQtyOrdered();
                    $orderitem = array('LineNumber' => $LineNumber,
                        'ItemCode'                      => $itemCode, //need to confirm
                        'ItemDescription'               => $_product->getName(),
                        'ItemYear'                      => $year,
                        'UOM'                           => 'Nos.',
                        'Variation'                     => '', //blank
                        'Variation2'                    => '', //blank
                        'Variation3'                    => '', //blank
                        'Quantity'                      => $qtyOrdered,
                        'Rate'                          => $price,
                        'DiscountPercentage'            => $item->getDiscountPercent(),
                        'DiscountAmount'                => $item->getDiscountAmount(),
                        'Tax1_Name'                     => $taxName,
                        'Tax1_Amount'                   => $item->getTaxAmount(),
                        'Tax2_Name'                     => '',
                        'Tax2_Amount'                   => 0,
                        'Tax3_Name'                     => '',
                        'Tax3_Amount'                   => 0,
                        'Tax4_Name'                     => '',
                        'Tax4_Amount'                   => 0,
                        'Tax5_Name'                     => '',
                        'Tax5_Amount'                   => 0,
                    );
                    $orderitems[] = $orderitem;
                    $LineNumber--;
                }
				
				$maxRim = max($rim_diameter);
				
				$itemCharge = '';
				$itemDiscount = '';
				
				$installerId = $order->getPickupStore();
				
                $shippingcharges = $order->getShippingAmount();

                if($shippingcharges > 0){
                    $shippingcharges = $order->getShippingAmount();
                    $discountcharges = '0.000';
                }else{
                    $shippingcharges = '139.000';
                    $discountcharges = $shippingcharges;
                }

				if($installerId == 2){
					$installerVat = $shippingcharges * 5/100;
					$installerVatAmount = number_format($installerVat, 4, '.', '');
					$orderitems[] = array(
						'LineNumber' 					=> $LineNumber,
						'ItemCode'                      => 'SEV200200216', //need to confirm
						'ItemDescription'               => 'MOBILE VAN SERVICE FEE',
						'ItemYear'                      => '',
						'UOM'                           => 'Nos.',
						'Variation'                     => '', //blank
						'Variation2'                    => '', //blank
						'Variation3'                    => '', //blank
						'Quantity'                      => '1',
						'Rate'                          => $shippingcharges,
						'DiscountPercentage'            => '0.0000',
						'DiscountAmount'                => $discountcharges,
						'Tax1_Name'                     => $taxName,
						'Tax1_Amount'                   => $installerVatAmount,
						'Tax2_Name'                     => '',
						'Tax2_Amount'                   => 0,
						'Tax3_Name'                     => '',
						'Tax3_Amount'                   => 0,
						'Tax4_Name'                     => '',
						'Tax4_Amount'                   => 0,
						'Tax5_Name'                     => '',
						'Tax5_Amount'                   => 0,
					);
				}elseif($installerId != 2 || $installerId != 6){
					$rimSize = '';
					if($maxRim != ''){
						if($maxRim <= 17){
							$rimSize1217A = 'SEV200200231';	
							$rimSize = $rimSize1217A;	
							$itemCharge = '25.0000';
							//$itemDiscount = '25.0000';
                            $itemDiscount = $totalQty * $itemCharge;
						}
						if($maxRim >= 18 && $maxRim <= 20){
							$rimSize1820A = 'SEV200200232';	
							$rimSize = $rimSize1820A;
							$itemCharge = '35.0000';
							//$itemDiscount = '35.0000';						
                            $itemDiscount = $totalQty * $itemCharge;
						}
						if($maxRim >= 21){
							$rimSize2123A = 'SEV200200233';
							$rimSize = $rimSize2123A;
							$itemCharge = '45.0000';
							//$itemDiscount = '45.0000';	
                            $itemDiscount = $totalQty * $itemCharge;
						}
                        
						$orderitems[] = array(
							'LineNumber' 					=> $LineNumber,
							'ItemCode'                      => $rimSize,
							'ItemDescription'               => 'FITMENT CHARGES FOR RIM: '.$maxRim,
							'ItemYear'                      => '',
							'UOM'                           => 'Nos.',
							'Variation'                     => '', //blank
							'Variation2'                    => '', //blank
							'Variation3'                    => '', //blank
							'Quantity'                      => $totalQty,
							'Rate'                          => $itemCharge,
							'DiscountPercentage'            => '0.0000',
							'DiscountAmount'                => $itemDiscount,
							'Tax1_Name'                     => $taxName,
							'Tax1_Amount'                   => '0.0000',
							'Tax2_Name'                     => '',
							'Tax2_Amount'                   => 0,
							'Tax3_Name'                     => '',
							'Tax3_Amount'                   => 0,
							'Tax4_Name'                     => '',
							'Tax4_Amount'                   => 0,
							'Tax5_Name'                     => '',
							'Tax5_Amount'                   => 0,
						);
					}	
				}

				$pickupFee = $order->getFee();
				
                if($order->getPickupLocation() && $pickupFee > 0 ){
					$pickup_detail = unserialize($order->getPickupLocation());
					if(isset($pickup_detail['pick_type'])) {
						
						$pickupFeeVat = $pickupFee * 5/100;
						$pickupFeeVatAmount = number_format($pickupFeeVat, 4, '.', '');
						$orderitems[] = array(
						'LineNumber' 					=> $LineNumber - 1,
						'ItemCode'                      => 'ECSV000337', //need to confirm
						'ItemDescription'               => 'PICK-UP/DROP-OFF SERVICE FEE',
						'ItemYear'                      => '',
						'UOM'                           => 'Nos.',
						'Variation'                     => '', //blank
						'Variation2'                    => '', //blank
						'Variation3'                    => '', //blank
						'Quantity'                      => '1',
						'Rate'                          => $pickupFee - $pickupFeeVatAmount,
						'DiscountPercentage'            => '0.0000',
						'DiscountAmount'                => '0.000',
						'Tax1_Name'                     => $taxName,
						'Tax1_Amount'                   => $pickupFeeVatAmount,
						'Tax2_Name'                     => '',
						'Tax2_Amount'                   => 0,
						'Tax3_Name'                     => '',
						'Tax3_Amount'                   => 0,
						'Tax4_Name'                     => '',
						'Tax4_Amount'                   => 0,
						'Tax5_Name'                     => '',
						'Tax5_Amount'                   => 0,
					);
					}
				}

                $country            = $this->_countryFactory->create()->loadByCode($billingAddress->getCountryId());
                $countryName        = $country->getName();
                $zipCode            = '00000';
                $middleName         = $billingAddress->getMiddlename();
                $orderData          = array(
                    'OrderType'          => $OrderType,
                    'TXNName'            => $order->getIncrementId(),
                    'TXNDate'            => $date,
                    'CompanyCode'        => $companyId,
                    'BranchCode'         => $branchCode,
                    'CustomerCode'       => $customerCode,
                    'ExecutiveCode'      => $executiveCode,
                    'CurrencyName'       => 'Emirati Dirham', //$order->getBaseCurrencyCode(), //Need to confirm
                    'RequestedDate'      => $requestedDate,
                    'OrderRemarks'       => $order->getAwOrderNote().' '.$installer_details['name'],
                    'DiscountAmount'     => $order->getDiscountAmount(),
                    'Charge1_Name'       => 'Shipping Charges',
                    'Charge1_Amount'     => $order->getShippingAmount(),
                    'Charge2_Name'       => 'Discount Charges',
                    'Charge2_Amount'     => $order->getDiscountAmount(),
                    'Charge3_Name'       => '',
                    'Charge3_Amount'     => 0,
                    'Charge4_Name'       => '',
                    'Charge4_Amount'     => 0,
                    'Charge5_Name'       => '',
                    'Charge5_Amount'     => 0,
                    'PaymentMethod'      => 'Card', //$PaymentMethod, //Need to confirm
                    'PaymentReferenceNo' => $PaymentReferenceNo,
                    'InstallerName'      => '',//$installer_details['name'],
                    'InstallerAddress'   => $InstallerAddress,
                    'InstallerPhone'     => $installer_details['phone'],
                    'InstallerEmail'     => $installer_details['email'],
                    'LPONo'              => $order->getIncrementId(),
                    'SOCustomerModel'    => array('Salutation' => '',
                        'FirstName'                                => $billingAddress->getFirstname(),
                        'MiddleName'                               => '',
                        'LastName'                                 => $billingAddress->getLastname(),
                        'MobileNo'                                 => $billingAddress->getTelephone(),
                        'Email'                                    => $billingAddress->getEmail(),
                        'Gender'                                   => '',
                        'BirthDate'                                => '',
                        'Nationality'                              => '',
                        'IsSMSAllowed'                             => true,
                        'IsEMailAllowed'                           => true,
                        'Tax_VATOfState'                           => '',
                        'Tax_VATNumber'                            => '',
                        'Tax_VATEffectFrom'                        => '',
                        'AddressCode'                              => $addressCode,
                        'Address'                                  => $billingAddress->getStreet()[0], //need to confirm
                        'City'                                     => $billingAddress->getCity(),
                        'State'                                    => '', //need to confirm
                        'Country'                                  => $countryName, //need to confirm
                        'ZipCode'                                  => $zipCode,
                        'VINNumber'                                => $vehiclePlate, //need to confirm
                        'VehiclePlatNo'                            => $vehiclePlate,
                        'VehicleItemCode'                          => $vehiclePlate,
                        'Year'                                     => $order->getYear(),
                        'Make'                                     => $order->getMake() ,
                        'Model'                                    => $order->getModel(),
                        'RefCode'                                  => $order->getModel(),
                        'ID'                                       => '',
                        'Code'                                     => '',
                    ),
                    'SOItemLineModels'   => $orderitems,
                    'ID'                 => '',
                    'Code'               => '',

                );

                $orderRequest = json_encode($orderData);
				//echo '<pre>';print_r($orderRequest);die;
                $createOrderResponseData = $this->_objectManager->create('Hdweb\Rfc\Helper\Data')->getRnrCreateSaleOrder($orderRequest, $companyId, $apiUsername, $apiPassword);
                $rnrOrderResponse        = unserialize($createOrderResponseData);
				//echo '<pre>';print_r($rnrOrderResponse);die;
				$subject = '';
                if (isset($rnrOrderResponse['OrderNo'])) {
					$subject = 'Sales order created in RNR for order #'.$order->getIncrementId();
					$this->_objectManager->create('Hdweb\Rfc\Helper\Data')->sendRnrEmailNotification($subject, $rnrOrderResponse);
                    $order->setRnrOrderResponse($createOrderResponseData);
					$order->setErpOrderStatus(1);
                    $order->save();
                    $this->_messageManager->addSuccess(__('RNR ERP Order generated successfully.'));
                    $this->_redirect('sales/order/view', array('order_id' => $order_id));
                }elseif(!is_array($rnrOrderResponse)){
					$this->_messageManager->addError(__($rnrOrderResponse));
                    $this->_redirect('sales/order/view', array('order_id' => $order_id));
				} else {
                    $this->_messageManager->addError(__('RNR ERP Order not generated.'));
                    $this->_redirect('sales/order/view', array('order_id' => $order_id));
                }
            } else {
                $this->_messageManager->addError('Something went wrong with the RNR Configuration ERP API!.');
                $this->_redirect('sales/order/view', array('order_id' => $order_id));
            }

        }

    }

}
