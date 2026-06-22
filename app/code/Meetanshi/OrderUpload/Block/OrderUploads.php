<?php

namespace Meetanshi\OrderUpload\Block;

use Meetanshi\OrderUpload\Model\OrderUpload;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Meetanshi\OrderUpload\Helper\Data;
use Magento\Sales\Model\OrderFactory;
use Magento\Customer\Model\SessionFactory;

/**
 * Class OrderUploads
 * @package Meetanshi\OrderUpload\Block
 */
class OrderUploads extends Template
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var OrderFactory
     */
    protected $orderFactory;
    /**
     * @var
     */
    protected $customerSession;
    /**
     * @var OrderUpload
     */
    private $orderUpload;
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var bool
     */
    private $isScopePrivate;
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * OrderUploads constructor.
     * @param Context $context
     * @param Registry $registry
     * @param OrderUpload $orderUpload
     * @param SessionFactory $customerSession
     * @param Data $helper
     * @param OrderFactory $orderFactory
     * @param array $data
     */
    public function __construct(Context $context, Registry $registry, OrderUpload $orderUpload, SessionFactory $customerSession, Data $helper, OrderFactory $orderFactory, array $data = [])
    {
        $this->helper = $helper;
        $this->isScopePrivate = true;
        $this->storeManager = $context->getStoreManager();
        $this->registry = $registry;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->orderUpload = $orderUpload;
        $this->filesystem = $context->getFilesystem();
        $this->orderFactory = $orderFactory;
        $this->customerSession = $customerSession->create();
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->helper->isValidCustomerGroup();
    }

    /**
     * @param $orderId
     * @return string
     */
    public function getOrderId($orderId)
    {
        $order = $this->orderFactory->create()->load($orderId);
        return $order->getIncrementId();
    }

    /**
     * @return string
     */
    public function pubMediaPath()
    {
        return $this->helper->pubMediaPath();
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK);
    }

    /**
     * @return $this|Template
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->pageConfig->getTitle()->set(__('My Order History'));

        if ($this->getCollection()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'reward.history.pager'
            )->setAvailableLimit([10 => 10, 20 => 20, 30 => 30, 40 => 40, 50 => 50, 100 => 100, 200 => 200])
                ->setShowPerPage(true)->setCollection(
                    $this->getCollection()
                );
            $this->setChild('pager', $pager);
            $this->getCollection()->load();
        }
        return $this;
    }

    /**
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection|\Meetanshi\OrderUpload\Model\ResourceModel\OrderUpload\Collection
     */
    public function getCollection()
    {
        $page = ($this->getRequest()->getParam('p')) ? $this->getRequest()->getParam('p') : 1;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->create("Magento\Customer\Model\Session");

        $pageSize = ($this->getRequest()->getParam('limit')) ? $this->getRequest()->getParam('limit') : 10;

        $collection = $this->orderUpload->getCollection()->addFieldToFilter('customer_id', $customerSession->getCustomerId())->setOrder('id', 'DESC');
        $collection->setPageSize($pageSize);
        $collection->setCurPage($page);

        return $collection;
    }
}
