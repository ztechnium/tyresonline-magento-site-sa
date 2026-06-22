<?php

namespace Meetanshi\OrderUpload\Block\Adminhtml\Order\View\Tab;

use Meetanshi\OrderUpload\Helper\Data;
use Meetanshi\OrderUpload\Model\OrderUploadFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;
use Magento\Sales\Helper\Admin;

/**
 * Class OrderUpload
 * @package Meetanshi\OrderUpload\Block\Adminhtml\Order\View\Tab
 */
class OrderUpload extends Template implements TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'order/view/tab/uploads.phtml';
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var \Magento\Framework\View\FileSystem
     */
    private $filesystem;
    /**
     * @var OrderUploadFactory
     */
    private $orderUploadFactory;
    /**
     * @var Admin
     */
    private $adminHelper;
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * OrderUpload constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Data $helper
     * @param Admin $adminHelper
     * @param OrderUploadFactory $orderUploadFactory
     * @param array $data
     */
    public function __construct(Context $context, Registry $registry, Data $helper, Admin $adminHelper, OrderUploadFactory $orderUploadFactory, array $data = [])
    {
        $this->registry = $registry;
        $this->helper = $helper;
        $this->filesystem = $context->getViewFileSystem();
        $this->orderUploadFactory = $orderUploadFactory;
        $this->adminHelper = $adminHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->getOrder();
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabLabel()
    {
        return __('Order Attachments');
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabTitle()
    {
        return __('Order Attachments');
    }

    /**
     * @return string
     */
    public function getFileUploadUrl()
    {
        return $this->getUrl('orderupload/orderupload/upload');
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->getOrder()->getId();
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
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
     * @return mixed
     */
    public function getOrder()
    {
        return $this->registry->registry('current_order');
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
     * @return mixed
     */
    public function canDelete()
    {
        return $this->helper->canDelete();
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
}
