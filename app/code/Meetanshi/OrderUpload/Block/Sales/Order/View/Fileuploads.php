<?php

namespace Meetanshi\OrderUpload\Block\Sales\Order\View;

use Meetanshi\OrderUpload\Helper\Data;
use Meetanshi\OrderUpload\Model\OrderUploadFactory;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\ProductAlert\Model\StockFactory;
use Magento\Framework\UrlInterface;

/**
 * Class Fileuploads
 * @package Meetanshi\OrderUpload\Block\Sales\Order\View
 */
class Fileuploads extends Template
{
    /**
     * @var Session
     */
    protected $customerSession;
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var StockFactory
     */
    private $stockFactory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var OrderUploadFactory
     */
    private $orderUploadFactory;
    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * Fileuploads constructor.
     * @param Context $context
     * @param Session $customerSession
     * @param Data $helper
     * @param StockFactory $stockFactory
     * @param Registry $registry
     * @param CurrentCustomer $currentCustomer
     * @param OrderUploadFactory $orderUploadFactory
     * @param array $data
     */
    public function __construct(Context $context, Session $customerSession, Data $helper, StockFactory $stockFactory, Registry $registry, CurrentCustomer $currentCustomer, OrderUploadFactory $orderUploadFactory, array $data = [])
    {
        $this->currentCustomer = $currentCustomer;
        $this->stockFactory = $stockFactory;
        $this->helper = $helper;
        $this->storeManager = $context->getStoreManager();
        $this->registry = $registry;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->customerSession = $customerSession;
        $this->orderUploadFactory = $orderUploadFactory;
        $this->filesystem = $context->getFilesystem();
        parent::__construct($context, $data);
        $collection = $this->stockFactory->create()->getCollection();
        $collection->addFieldToFilter('customer_id', $this->currentCustomer->getCustomerId());
        $this->setCollection($collection);
    }

    /**
     * @return mixed
     */
    public function isCustomerAllowed()
    {
        return $this->helper->isCustomerCanAdd();
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->helper->isValidCustomerGroup();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK);
    }

    /**
     * @return mixed|string
     */
    public function allowedDocSizes()
    {
        return $this->helper->getFileExtensions();
    }

    /**
     * @return int|mixed
     */
    public function fileSize()
    {
        return $this->helper->getMaxFileSize();
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * @return string
     */
    public function getFileUploadUrl()
    {
        return $this->getUrl('orderupload/upload/upload');
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->getOrder()->getId();
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->getOrder()->getCustomerId();
    }

    /**
     * @return string
     */
    public function pubMediaPath()
    {
        return $this->helper->pubMediaPath();
    }

    /**
     * @return mixed|string
     */
    public function getFileExtension()
    {
        return $this->helper->getFileExtensions();
    }

    /**
     * @return mixed
     */
    public function getAttachedFiles()
    {
        $orderId = $this->getOrder()->getId();
        $collection = $this->orderUploadFactory->create()->getCollection();
        $collection->addFieldToFilter('order_id', $orderId);
        return $collection->getData();
    }

    /**
     * @return mixed
     */
    public function canDelete()
    {
        return $this->helper->canDelete();
    }

    /**
     * @return $this|Template
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getCollection()):
            $pager = $this->getLayout()->createBlock('Magento\Theme\Block\Html\Pager', 'meetanshi.orderupload.record.pager')->setCollection($this->getCollection());
            $this->setChild('pager', $pager);
        endif;
        return $this;
    }
}
