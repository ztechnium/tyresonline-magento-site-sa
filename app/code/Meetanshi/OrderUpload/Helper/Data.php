<?php

namespace Meetanshi\OrderUpload\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Meetanshi\OrderUpload\Helper\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meetanshi\OrderUpload\Model\OrderUpload as AttachmentList;
use Magento\Customer\Model\Session;
use Magento\Framework\UrlInterface;

/**
 * Class Data
 * @package Meetanshi\OrderUpload\Helper
 */
class Data extends AbstractHelper
{
    const XML_PATH_ENABLED = 'orderupload/general/enabled';
    const XML_PATH_CUSTOMERGROUPS = 'orderupload/customer/customer_groups';
    const XML_XML_EMAIL_ENABLED = 'orderupload/email/active';
    const XML_PATH_CUSTOMER_ADD = 'orderupload/customer/can_customer_orderupload';
    const XML_PATH_ATTACHMENT_DIR = 'orderupload/general/upload_dir';
    const XML_PATH_SEND_EMAIL_ATTACHMENT_CUSTOMER = 'orderupload/customer/send_attachment_to_customer';
    const XML_PATH_SEND_EMAIL_ATTACHMENT_ADMIN = 'orderupload/customer/send_attachment_to_admin';
    const XML_PATH_MAX_FILE_SIZE_ATTACHMENT = 'orderupload/general/max_file_size';
    const XML_PATH_CUSTOMER_DELETE = 'orderupload/customer/can_delete_orderupload';
    const XML_PATH_CUSTOMER_COMMENT = 'orderupload/customer/allow_comment';
    const XML_PATH_ALLOW_UPLOAD = 'orderupload/customer/allow_checkout';
    const XML_PATH_ALLOWED_EXTENSIONS = 'orderupload/general/allowed_extensions';
    const XML_EMAIL_ENABLED = 'orderupload/email/active';
    const SEND_EMAIL_SEPERATELY = 'orderupload/email/send_separately';
    const XML_ADMIN_EMAIL = 'orderupload/email/admin_email';
    const XML_ADMIN_NAME = 'orderupload/email/admin_name';
    const XML_ADD_ATTACHMENT = 'orderupload/email/attachment';

    const XML_SIZE_1KB = 1024;
    const XML_SIZE_2KB = 2048;
    const XML_SIZE_1MB = 1048576;
    const XML_SIZE_2MB = 2097152;

    const XML_SIZE_BYTES = 'b';
    const XML_SIZE_KBYTES = 'kb';
    const XML_SIZE_MBYTES = 'mb';

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param Session $customerSession
     */
    public function __construct(Context $context, StoreManagerInterface $storeManager, TransportBuilder $transportBuilder, StateInterface $inlineTranslation, Session $customerSession)
    {
        $this->scopeConfig = $context->getScopeConfig();
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function isSendEmail()
    {
        return $this->scopeConfig->getValue(self::XML_XML_EMAIL_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function isCustomerCanAdd()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CUSTOMER_ADD, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function isValidCustomerGroup()
    {
        $customerGroups = explode(",", $this->getStoreConfig(self::XML_PATH_CUSTOMERGROUPS));
        $customerSession = $this->customerSession;
        $customerGroupId = $customerSession->getCustomerGroupId();

        if (in_array($customerGroupId, $customerGroups)) {
            return true;
        }
        return false;
    }

    /**
     * @return int|mixed
     */
    public function getMaxFileSize()
    {
        $maxFileSize = $this->getStoreConfig(self::XML_PATH_MAX_FILE_SIZE_ATTACHMENT);
        if (!empty($maxFileSize)) {
            return $maxFileSize;
        } else {
            return 50;
        }
    }

    /**
     * @return mixed
     */
    public function canDelete()
    {
        return $this->getStoreConfig(self::XML_PATH_CUSTOMER_DELETE);
    }

    /**
     * @param null $store
     * @return mixed|string
     */
    public function attachFilePath($store = null)
    {
        $path = $this->scopeConfig->getValue(self::XML_PATH_ATTACHMENT_DIR, ScopeInterface::SCOPE_STORE, $store);
        if ($path) {
            return $path;
        }

        $path = 'orderupload';
        return $path;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function tempMediaPath()
    {
        $storeManager = $this->storeManager;
        $currentStore = $storeManager->getStore();
       //$pubMediaUrl = $currentStore->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . AttachmentList::ORDERUPLOAD_TMP_PATH;
        $pubMediaUrl = $currentStore->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return $pubMediaUrl;
    }

    /**
     * @param null $store
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function pubMediaPath($store = null)
    {
        $path = $this->scopeConfig->getValue(self::XML_PATH_ATTACHMENT_DIR, ScopeInterface::SCOPE_STORE, $store);
        $storeManager = $this->storeManager;
        $currentStore = $storeManager->getStore();
        if ($path) {
            $pubMediaUrl = $currentStore->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $path;
            return $pubMediaUrl;
        }
        $pubMediaUrl = $currentStore->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'orderupload';
        return $pubMediaUrl;
    }

    /**
     * @param $storePath
     * @return mixed
     */
    public function getStoreConfig($storePath)
    {
        return $this->scopeConfig->getValue($storePath, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed|string
     */
    public function getFileExtensions()
    {
        $fileExtensions = $this->getStoreConfig(self::XML_PATH_ALLOWED_EXTENSIONS);
        if (!empty($fileExtensions)) {
            return $fileExtensions;
        } else {
            return 'jpg,jpeg,gif,bmp,png';
        }
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function sendMailToCustomer($store = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SEND_EMAIL_ATTACHMENT_CUSTOMER, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function sendMailToAdmin($store = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SEND_EMAIL_ATTACHMENT_ADMIN, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @param $size
     * @return string
     */
    public function getDOCFileSize($size)
    {
        $result = $size;
        if ($size <= self::XML_SIZE_2KB) {
            $result .= self::XML_SIZE_BYTES;
        } elseif ($size <= self::XML_SIZE_2MB) {
            $result = round($size / self::XML_SIZE_1KB, 2) . self::XML_SIZE_KBYTES;
        } else {
            $result = round($size / self::XML_SIZE_1MB, 2) . self::XML_SIZE_MBYTES;
        }
        return $result;
    }

    /**
     * @return mixed
     */
    public function sendEmailSeperately()
    {
        return $this->scopeConfig->getValue(self::SEND_EMAIL_SEPERATELY, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getAdminEmail()
    {
        return $this->scopeConfig->getValue(self::XML_ADMIN_EMAIL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getAdminName()
    {
        return $this->scopeConfig->getValue(self::XML_ADMIN_NAME, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function addAttachmentInEmail()
    {
        return $this->scopeConfig->getValue(self::XML_ADD_ATTACHMENT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function addCommentFromCheckout()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CUSTOMER_COMMENT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function allowOnCheckout()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ALLOW_UPLOAD, ScopeInterface::SCOPE_STORE);
    }
}
