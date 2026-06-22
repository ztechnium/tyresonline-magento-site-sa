<?php

namespace Hdweb\Rfc\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $messageManager;
    protected $_objectManager;
    protected $_resource;
    protected $_urlBuilder;
    protected $storeManager;
	protected $authSession;
	protected $date;
	protected $rfc;
    const RNR_LOGIN_URL = 'http://157.175.109.168/ABInternational_FZC/Integration/Api/Login/Login';
	const RNR_NOTIFY_EMAIL_TEMPLATE  = 'hdwebcore/general/rnr_notify_email_template';
	const VOID_ORDER_NOTIFY_EMAIL_TEMPLATE  = 'hdwebcore/general/void_order_notify_email_template';
	const INSTALLATION_COMPLETE_NOTIFY_EMAIL_TEMPLATE  = 'hdwebcore/general/installation_complete_notify_email_template';
	const ADMIN_INVOICE_NOTIFY_EMAIL_TEMPLATE  = 'hdwebcore/general/admin_invoice_notify_email_template';
	const SOCIAL_LOGIN_REGISTER_NOTIFY_EMAIL_TEMPLATE  = 'hdwebcore/general/social_login_register_notify_email_template';
	const GOOGLE_REVIEW_NOTIFY_EMAIL_TEMPLATE  = 'hdwebcore/general/google_review_notify_email_template';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\ResourceConnection $resource,
		\Magento\Backend\Model\Auth\Session $authSession,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
		\Hdweb\Rfc\Model\Rfc $rfc
    ) {
        $this->_urlBuilder           = $context->getUrlBuilder();
        $this->storeManager          = $storeManager;
        $this->_objectManager        = \Magento\Framework\App\ObjectManager::getInstance();
        $this->messageManager        = $messageManager;
        $this->_resource             = $resource;
        $this->_scopeConfig          = $context->getScopeConfig();
		$this->authSession 			 = $authSession;
		$this->date        			 = $date;
		$this->rfc        			 = $rfc;
    }

    public function getRnrLoginSessionKey($companyId, $apiUsername, $apiPassword)
    {
        $hosturl    = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/hosturl');
        $loginUrl   = $hosturl . 'Api/Login/Login';
        $sessionKey = '';
        if ($loginUrl != '') {
            $ch              = curl_init($loginUrl);
            $postdata        = array('LoginName' => $apiUsername, 'Password' => $apiPassword, 'Company' => $companyId, 'Branch' => '');
            $postdata_string = json_encode($postdata);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
            ));
            $result   = curl_exec($ch);
            $response = json_decode($result, true);
            curl_close($ch);
            /* $sessionKey = '';
            foreach ($response as $responseData) {
            $sessionKey = $responseData['SessionKey'];
            } */
            $sessionKey = $response;
        }
        return $sessionKey;
    }

    public function getRnrSalesInvoiceData($rnr_order_id, $companyId, $apiUsername, $apiPassword)
    {
        $sessionKey = $this->getRnrLoginSessionKey($companyId, $apiUsername, $apiPassword);
        if ($sessionKey != '') {
            // API URL to send data
            $hosturl = "http://157.175.109.168/ABInternational_FZC/Integration/";
			$rfchosturl    = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/hosturl');
			if($rfchosturl != ''){
				$hosturl = $rfchosturl;
			}
            $rfcUrl  = $hosturl . 'Api/TransactionInt/GetSaleInvoiceData?CompanyCode=' . $companyId . '&OrderNo=' . $rnr_order_id . '&IsPrint=false';

            $rfc_name     = "Get RNR Sales Invoice";
            $rfc_url      = $rfcUrl;
            $requestparam = 'CompanyCode=' . $companyId . '&OrderNo=' . $rnr_order_id . '&IsPrint=false';
            $rfcid        = $this->creaetrfc($rfc_name, $rfc_url, $requestparam);

            $ch      = curl_init();
            $headers = array('Content-Type: application/json', 'SessionKey:' . $sessionKey . '');
            $apiUrl  = str_replace(" ", '%20', $rfcUrl);
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // Execute curl and assign returned data
            $result = curl_exec($ch);

            $this->updaterfc($rfcid, $result);

            $response = json_decode($result, true);
            // Close curl
            curl_close($ch);
            $invoiceResponse = serialize($response);
            return $invoiceResponse;
        }
    }

    public function getRnrCreateSaleOrder($orderRequest, $companyId, $apiUsername, $apiPassword)
    {
        $sessionKey = $this->getRnrLoginSessionKey($companyId, $apiUsername, $apiPassword);
        if ($sessionKey != '') {
            // API URL to send data
            $hosturl = "http://157.175.109.168/ABInternational_FZC/Integration/";
			$rfchosturl    = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/hosturl');
			if($rfchosturl != ''){
				$hosturl = $rfchosturl;
			}
            $rfcUrl  = $hosturl . 'Api/TransactionInt/CreateSaleOrder';
            // curl initiate
            $ch = curl_init($rfcUrl);

            $rfc_name     = "Generate RNR ERP Order";
            $rfc_url      = $rfcUrl;
            $requestparam = $orderRequest;
            $rfcid        = $this->creaetrfc($rfc_name, $rfc_url, $requestparam);

            //$postdata        = array('LoginName' => $apiUsername, 'Password' => $apiPassword, 'Company' => $companyId, 'Branch' => '');
            //$postdata_string = json_encode($postdata);
            $headers = array('Content-Type: application/json', 'SessionKey:' . $sessionKey . '');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $orderRequest);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);

            $this->updaterfc($rfcid, $result);

            $response = json_decode($result, true);
            curl_close($ch);
            $orderCreateResponse = serialize($response);
            return $orderCreateResponse;
        }
	}	
	public function getRnrCreatePurchaseOrder($orderRequest, $companyId, $apiUsername, $apiPassword)
    {
        $sessionKey = $this->getRnrLoginSessionKey($companyId, $apiUsername, $apiPassword);
		
        if ($sessionKey != '') {
            // API URL to send data
            $hosturl = "http://157.175.109.168/ABInternational_FZC/Integration/";
			$rfchosturl    = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('rnrtabsection/general/hosturl');
			if($rfchosturl != ''){
				$hosturl = $rfchosturl;
			}
            $rfcUrl  = $hosturl . 'Api/TransactionInt/CreatePurchaseOrder';
            // curl initiate
            $ch = curl_init($rfcUrl);

            $rfc_name     = "Generate RNR ERP Purchase Order";
            $rfc_url      = $rfcUrl;
            $requestparam = $orderRequest;
            $rfcid        = $this->creaetrfc($rfc_name, $rfc_url, $requestparam);

            //$postdata        = array('LoginName' => $apiUsername, 'Password' => $apiPassword, 'Company' => $companyId, 'Branch' => '');
            //$postdata_string = json_encode($postdata);
            $headers = array('Content-Type: application/json', 'SessionKey:' . $sessionKey . '');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $orderRequest);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);

            $this->updaterfc($rfcid, $result);

            $response = json_decode($result, true);
            curl_close($ch);
            $orderCreateResponse = serialize($response);
            return $orderCreateResponse;
        }
    }
	
	public function sendRnrEmailNotification($subject, $rnrOrderResponse)
	{
		//$subject = 'RNR Success Email Notification';
		$storeScope        = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$_transportBuilder = $this->_objectManager->create('Hdweb\Purchaseorder\Model\Mail\TransportBuilder');
        $inlineTranslation = $this->_objectManager->create('Magento\Framework\Translate\Inline\StateInterface');
		$email = $this->_scopeConfig->getValue('trans_email/ident_support/email', $storeScope);
        $name  = $this->_scopeConfig->getValue('trans_email/ident_support/name', $storeScope);
		$from = array('email' => $email, 'name' => $name);
		$to = 'devendra.it@live.com';
		$emailTo    = $this->_scopeConfig->getValue('rnrtabsection/general/rnr_email_receipant', $storeScope);
		if($emailTo != ''){
			$to    = $this->_scopeConfig->getValue('rnrtabsection/general/rnr_email_receipant', $storeScope);
		}
		$emailTemplateId  = $this->_scopeConfig->getValue(self::RNR_NOTIFY_EMAIL_TEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
		$templateVars = array('subject' => $subject, 'rnr_success_response' => '<pre>'.print_r($rnrOrderResponse, true).'</pre>');
		$transport = $_transportBuilder->setTemplateIdentifier($emailTemplateId)
			->setTemplateOptions($templateOptions)
			->setTemplateVars($templateVars)
			->setFrom($from)
			->addTo($to) // $vendor_email
			->getTransport();
		$transport->sendMessage();
		$inlineTranslation->resume();
	}
	
	public function sendVoidEmailNotification($orderIncrementId, $customerEmail, $order_amount)
	{
		$storeScope        = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$_transportBuilder = $this->_objectManager->create('Hdweb\Purchaseorder\Model\Mail\TransportBuilder');
        $inlineTranslation = $this->_objectManager->create('Magento\Framework\Translate\Inline\StateInterface');
		$email = $this->_scopeConfig->getValue('trans_email/ident_support/email', $storeScope);
        $name  = $this->_scopeConfig->getValue('trans_email/ident_support/name', $storeScope);
		$from = array('email' => $email, 'name' => $name);
		$to = $customerEmail;
		$emailTemplateId  = $this->_scopeConfig->getValue(self::VOID_ORDER_NOTIFY_EMAIL_TEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
		$templateVars = array('order_id' => $orderIncrementId, 'order_amount' => $order_amount);
		$transport = $_transportBuilder->setTemplateIdentifier($emailTemplateId)
			->setTemplateOptions($templateOptions)
			->setTemplateVars($templateVars)
			->setFrom($from)
			->addTo($to) // $vendor_email
			->getTransport();
		$transport->sendMessage();
		$inlineTranslation->resume();
	}
	
	public function sendInstallationEmailNotification($orderIncrementId, $customerEmail)
	{
		$storeScope        = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$_transportBuilder = $this->_objectManager->create('Hdweb\Purchaseorder\Model\Mail\TransportBuilder');
        $inlineTranslation = $this->_objectManager->create('Magento\Framework\Translate\Inline\StateInterface');
		$email = $this->_scopeConfig->getValue('trans_email/ident_support/email', $storeScope);
        $name  = $this->_scopeConfig->getValue('trans_email/ident_support/name', $storeScope);
		$from = array('email' => $email, 'name' => $name);
		//$to = $email;
		$to = $customerEmail;
		$emailTemplateId  = $this->_scopeConfig->getValue(self::INSTALLATION_COMPLETE_NOTIFY_EMAIL_TEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
		$templateVars = array('order_id' => $orderIncrementId);
		$transport = $_transportBuilder->setTemplateIdentifier($emailTemplateId)
			->setTemplateOptions($templateOptions)
			->setTemplateVars($templateVars)
			->setFrom($from)
			->addTo($to) // $vendor_email
			->getTransport();
		$transport->sendMessage();
		$inlineTranslation->resume();
	}	
	
	public function updateSTGproduct($productRequest)
    {
		$authUser = 'test';
		$authPassword = '!test123!';
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://www.stopandgouae.com/rest/V1/products/updateProduct?user_name=tyoapi&password=Tyoapiint@2022',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => $productRequest,
		  CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
		  ),
		 // CURLOPT_USERPWD => $authUser.":".$authPassword,
		  CURLOPT_HTTPAUTH => CURLAUTH_ANY
		));

		$response = curl_exec($curl);

		curl_close($curl);
        
	}
	public function updateStaffproduct($productRequest)
    {
		$authUser = 'test';
		$authPassword = '!test123!';
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://staff.stopandgouae.com/rest/V1/products/updateProduct?user_name=tyoapi&password=Tyoapiint@2022',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => $productRequest,
		  CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
		  ),
		 // CURLOPT_USERPWD => $authUser.":".$authPassword,
		  CURLOPT_HTTPAUTH => CURLAUTH_ANY
		));

		$response = curl_exec($curl);
		
		curl_close($curl);
        
	}
	
	public function sendAdminInvoiceEmailNotification($order)
	{
		$storeScope        = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$_transportBuilder = $this->_objectManager->create('Hdweb\Purchaseorder\Model\Mail\TransportBuilder');
        $inlineTranslation = $this->_objectManager->create('Magento\Framework\Translate\Inline\StateInterface');
		$email = $this->_scopeConfig->getValue('trans_email/ident_support/email', $storeScope);
        $name  = $this->_scopeConfig->getValue('trans_email/ident_support/name', $storeScope);
		$from = array('email' => $email, 'name' => $name);
		$receiveemails    = $this->_scopeConfig->getValue('hdwebinvoice/general/custom_invoice_email_receipant', $storeScope);
		$receiveemailsTo = explode(',',preg_replace('/\s+/', '', $receiveemails));
		
		$emailTemplateId  = $this->_scopeConfig->getValue(self::ADMIN_INVOICE_NOTIFY_EMAIL_TEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
		$billingAddress  = $order->getBillingAddress();
		$order_amount     = $order->getGrandTotal();
		$order_amount = number_format($order_amount, 2, '.', '');
		$transaction = $this->_objectManager->create('Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory')->create()->addOrderIdFilter($order->getId())->getFirstItem();
		$transactionId = '';
		if(count($transaction->getData()) > 0){
			$transactionId = $transaction->getData('txn_id');
		}
		$payment            = $order->getPayment();
		$method             = $payment->getMethodInstance();
		$paymentmethodTitle = $method->getTitle();
		$templateVars = array(
							'order_id'		 	=> $order->getIncrementId(),
							'customer_name' 	=> $order->getCustomerName(),
							'customer_email' 	=> $order->getCustomerEmail(),
							'customer_contact' 	=> $billingAddress->getTelephone(),
						    'payment_id' 	  	=> $transactionId,
						    'payment_mode'    	=> $paymentmethodTitle,
							'grand_total' 		=> $order_amount,
		);
		$transport = $_transportBuilder->setTemplateIdentifier($emailTemplateId)
			->setTemplateOptions($templateOptions)
			->setTemplateVars($templateVars)
			->setFrom($from)
			->addTo($receiveemailsTo)
			->getTransport();
		$transport->sendMessage();
		$inlineTranslation->resume();
	}
	
	public function sendLoginDiscountEmailNotification($customerEmail, $couponCode)
	{
		$storeScope        = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$_transportBuilder = $this->_objectManager->create('Hdweb\Purchaseorder\Model\Mail\TransportBuilder');
        $inlineTranslation = $this->_objectManager->create('Magento\Framework\Translate\Inline\StateInterface');
		$email = $this->_scopeConfig->getValue('trans_email/ident_support/email', $storeScope);
        $name  = $this->_scopeConfig->getValue('trans_email/ident_support/name', $storeScope);
		$from = array('email' => $email, 'name' => $name);
		$to = $customerEmail;
		$emailTemplateId  = $this->_scopeConfig->getValue(self::SOCIAL_LOGIN_REGISTER_NOTIFY_EMAIL_TEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
		$templateVars = array('coupon_code' => $couponCode);
		$transport = $_transportBuilder->setTemplateIdentifier($emailTemplateId)
			->setTemplateOptions($templateOptions)
			->setTemplateVars($templateVars)
			->setFrom($from)
			->addTo($to) // $vendor_email
			->getTransport();
		$transport->sendMessage();
		$inlineTranslation->resume();
	}
	
	public function sendGoogleReviewEmailNotification($customerEmail, $customerName)
	{
		$storeScope        = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$_transportBuilder = $this->_objectManager->create('Hdweb\Purchaseorder\Model\Mail\TransportBuilder');
        $inlineTranslation = $this->_objectManager->create('Magento\Framework\Translate\Inline\StateInterface');
		$email = $this->_scopeConfig->getValue('trans_email/ident_support/email', $storeScope);
        $name  = $this->_scopeConfig->getValue('trans_email/ident_support/name', $storeScope);
		$from = array('email' => $email, 'name' => $name);
		$to = $customerEmail;
		$emailTemplateId  = $this->_scopeConfig->getValue(self::GOOGLE_REVIEW_NOTIFY_EMAIL_TEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		$templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
		$templateVars = array('customer_name' => $customerName);
		$transport = $_transportBuilder->setTemplateIdentifier($emailTemplateId)
			->setTemplateOptions($templateOptions)
			->setTemplateVars($templateVars)
			->setFrom($from)
			->addTo($to)
			->getTransport();
		$transport->sendMessage();
		$inlineTranslation->resume();
	}
	
	public function creaetrfc($rfc_name, $rfc_url, $requestparam)
    {
        $adminuser    = $this->authSession->getUser();
        $rfc_username = $adminuser->getFirstname() . ' ' . $adminuser->getLastname();
        $rfc_datetime = $this->date->date()->format('Y-m-d H:i:s');

        $rfc = $this->rfc;
        $rfc->setData('rfc_name', $rfc_name);
        $rfc->setData('rfc_url', $rfc_url);
        $rfc->setData('rfc_username', $rfc_username);
        $rfc->setData('rfc_datetime', $rfc_datetime);
        $rfc->setData('requestparam', $requestparam);
        $rfc->save();
        $rfcid = $rfc->getRfcId();
        return $rfcid;
    }

    public function updaterfc($rfcid, $responseparam)
    {
        $rfc_response_datetime = $this->date->date()->format('Y-m-d H:i:s');
        $rfc                   = $this->rfc->load($rfcid);
        $rfc->setData('rfc_response_datetime', $rfc_response_datetime);
        $rfc->setData('responseparam', $responseparam);
        $rfc->save();
    }
}
