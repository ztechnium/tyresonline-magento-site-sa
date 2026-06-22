<?php

namespace Meetanshi\WorldpayHp\Helper;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    const CONFIG_WORLDPAYHP_ACTIVE = 'payment/worldpay_hp/active';
    const CONFIG_WORLDPAYHP_MODE = 'payment/worldpay_hp/mode';

    const CONFIG_WORLDPAYHP_LOGO = 'payment/worldpay_hp/show_logo';

    const CONFIG_WORLDPAYHP_INSTRUCTIONS = 'payment/worldpay_hp/instructions';

    const CONFIG_WORLDPAYHP_SANDBOX_SECRET_KEY = 'payment/worldpay_hp/sandbox_secret_key';
    const CONFIG_WORLDPAYHP_LIVE_SECRET_KEY = 'payment/worldpay_hp/live_secret_key';

    const CONFIG_WORLDPAYHP_SANDBOX_ACCESS_KEY = 'payment/worldpay_hp/sandbox_access_key';
    const CONFIG_WORLDPAYHP_LIVE_ACCESS_KEY = 'payment/worldpay_hp/live_access_key';

    const CONFIG_WORLDPAYHP_SANDBOX_PROFILE_ID = 'payment/worldpay_hp/sandbox_profile_id';
    const CONFIG_WORLDPAYHP_LIVE_PROFILE_ID = 'payment/worldpay_hp/live_profile_id';

    const CONFIG_WORLDPAYHP_SANDBOX_GATEWAY_URL = 'payment/worldpay_hp/sandbox_gateway_url';
    const CONFIG_WORLDPAYHP_LIVE_GATEWAY_URL = 'payment/worldpay_hp/live_gateway_url';

    const CONFIG_WORLDPAYHP_INVOICE = 'payment/worldpay_hp/allow_invoice';
    const CONFIG_WORLDPAYHP_MERCHANT_CODE = 'payment/worldpay_hp/merchant_code';
    const CONFIG_WORLDPAYHP_DEBUG = 'payment/worldpay_hp/debug';
    const CONFIG_WORLDPAYHP_ACTION = 'payment/worldpay_hp/paction';

    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Http
     */
    protected $request;
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;
    /**
     * @var SessionManager
     */
    protected $sessionManager;
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * Data constructor.
     * @param Context $context
     * @param EncryptorInterface $encryptor
     * @param DirectoryList $directoryList
     * @param StoreManagerInterface $storeManager
     * @param Http $request
     * @param SessionManager $sessionManager
     * @param Repository $repository
     * @param CheckoutSession $checkoutSession
     * @param RegionFactory $regionFactory
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        DirectoryList $directoryList,
        StoreManagerInterface $storeManager,
        Http $request,
        SessionManager $sessionManager,
        Repository $repository,
        CheckoutSession $checkoutSession,
        RegionFactory $regionFactory
    )
    {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->directoryList = $directoryList;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->sessionManager = $sessionManager;
        $this->repository = $repository;
        $this->checkoutSession = $checkoutSession;
        $this->regionFactory = $regionFactory;
    }

    /**
     * @return mixed
     */
    public function isDebug()
    {
        return $this->scopeConfig->getValue(self::CONFIG_WORLDPAYHP_DEBUG, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function isAutoInvoice()
    {
        return $this->scopeConfig->getValue(self::CONFIG_WORLDPAYHP_INVOICE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function isActive()
    {
        return $this->scopeConfig->getValue(self::CONFIG_WORLDPAYHP_ACTIVE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getPaymentAction()
    {
        return $this->scopeConfig->getValue(self::CONFIG_WORLDPAYHP_ACTION, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getPaymentInstructions()
    {
        return $this->scopeConfig->getValue(self::CONFIG_WORLDPAYHP_INSTRUCTIONS, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        if ($this->getMode()) {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(
                self::CONFIG_WORLDPAYHP_SANDBOX_SECRET_KEY,
                ScopeInterface::SCOPE_STORE
            ));
        } else {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(
                self::CONFIG_WORLDPAYHP_LIVE_SECRET_KEY,
                ScopeInterface::SCOPE_STORE
            ));
        }
    }

    /**
     * @return string
     */
    public function getAccessKey()
    {
        if ($this->getMode()) {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(
                self::CONFIG_WORLDPAYHP_SANDBOX_ACCESS_KEY,
                ScopeInterface::SCOPE_STORE
            ));
        } else {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(
                self::CONFIG_WORLDPAYHP_LIVE_ACCESS_KEY,
                ScopeInterface::SCOPE_STORE
            ));
        }
    }


    /**
     * @return string
     */
    public function getProfileId()
    {
        if ($this->getMode()) {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(
                self::CONFIG_WORLDPAYHP_SANDBOX_PROFILE_ID,
                ScopeInterface::SCOPE_STORE
            ));
        } else {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(
                self::CONFIG_WORLDPAYHP_LIVE_PROFILE_ID,
                ScopeInterface::SCOPE_STORE
            ));
        }
    }

    /**
     * @return mixed
     */
    public function getMode()
    {
        return $this->scopeConfig->getValue(self::CONFIG_WORLDPAYHP_MODE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $order
     * @return string
     */
    public function getPaymentForm($order)
    {
        $billingAddress = $order->getBillingAddress();
        $region = $this->regionFactory->create()->load($billingAddress['region_id']);

        $params = [
            'access_key' => $this->getAccessKey(),
            'profile_id' => $this->getProfileId(),
            'transaction_uuid' => $order->getIncrementId() . '-' . rand(100, 999),
            'signed_date_time' => gmdate("Y-m-d\TH:i:s\Z"),
            'signed_field_names' => 'access_key,profile_id,transaction_uuid,signed_field_names,unsigned_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency,override_custom_cancel_page,override_custom_receipt_page',
            'unsigned_field_names' => 'signature,bill_to_forename,bill_to_surname,bill_to_email,bill_to_address_line1,bill_to_address_city,bill_to_address_country,bill_to_address_postal_code',
            'transaction_type' => $this->getPaymentAction(),
            'reference_number' => $order->getIncrementId(),
            'amount' => round($order->getGrandTotal(), 2),
            'currency' => $order->getOrderCurrencyCode(),
            'locale' => 'en-us',
            'bill_to_surname' => $billingAddress->getFirstname(),
            'bill_to_forename' => $billingAddress->getLastname(),
            'bill_to_address_line1' => $billingAddress->getStreet()[0],
            'bill_to_address_postal_code' => $billingAddress->getPostcode(),
            'bill_to_address_country' => $billingAddress->getCountryId(),
            'bill_to_email' => $billingAddress->getEmail(),
            'bill_to_company_name' => $billingAddress->getCompany(),
            'bill_to_address_city' => $billingAddress->getCity(),
            'override_custom_cancel_page' => $this->getCancelUrl(),
            'override_custom_receipt_page' => $this->getReturnUrl()
        ];

        if ($region->getId()) {
            $params['bill_to_address_state'] = $region->getCode();
            $params['unsigned_field_names'] = 'signature,bill_to_forename,bill_to_surname,bill_to_email,bill_to_address_line1,bill_to_address_city,bill_to_address_state,bill_to_address_country,bill_to_address_postal_code';
        }

        $html = "<form id='WorldpayHpForm' name='worldpayhostedsubmit' action='" . $this->getGatewayUrl() . "' method='POST'>";
        foreach ($params as $name => $value) {
            $html .= "<input type='hidden' name='" . $name . "' value='" . $value . "' />";
        }
        $html .= "<input type='hidden' name='signature' value='" . $this->sign($params) . "' />";
        $html .= "</form>";

        return $html;
    }

    /**
     * @param $params
     * @return string
     */
    public function sign($params)
    {
        return $this->signData($this->buildDataToSign($params), $this->getSecretKey());
    }

    /**
     * @param $data
     * @param $secretKey
     * @return string
     */
    public function signData($data, $secretKey)
    {
        return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
    }

    /**
     * @param $params
     * @return string
     */
    public function buildDataToSign($params)
    {
        $signedFieldNames = explode(",", $params["signed_field_names"]);
        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field . "=" . $params[$field];
        }
        return $this->commaSeparate($dataToSign);
    }

    /**
     * @param $dataToSign
     * @return string
     */
    public function commaSeparate($dataToSign)
    {
        return implode(",", $dataToSign);
    }

    /**
     * @return mixed
     */
    public function getGatewayUrl()
    {
        if ($this->getMode()) {
            return $this->scopeConfig->getValue(self::CONFIG_WORLDPAYHP_SANDBOX_GATEWAY_URL, ScopeInterface::SCOPE_STORE);
        } else {
            return $this->scopeConfig->getValue(self::CONFIG_WORLDPAYHP_LIVE_GATEWAY_URL, ScopeInterface::SCOPE_STORE);
        }
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getReturnUrl()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        return $baseUrl . "worldpay_hp/payment/success";
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCancelUrl()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        return $baseUrl . "worldpay_hp/payment/cancel";
    }

    /**
     * @return mixed
     */
    public function showLogo()
    {
        return $this->scopeConfig->getValue(self::CONFIG_WORLDPAYHP_LOGO, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getPaymentLogo()
    {
        $params = ['_secure' => $this->request->isSecure()];
        return $this->repository->getUrlWithParams('Meetanshi_WorldpayHp::images/cybersource.webp', $params);
    }

    /**
     * @param $message
     * @param $data
     * @throws \Zend_Log_Exception
     */
    public function logger($message, $data)
    {
        if ($this->isDebug()) {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/cybersource.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            if (!is_array($data)) {
                $data = (array)$data;
            }
            $logger->info($message);
            $logger->info(print_r($data, true));
        }
    }
}
