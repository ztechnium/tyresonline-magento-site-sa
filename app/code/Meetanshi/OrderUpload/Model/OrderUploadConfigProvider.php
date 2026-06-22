<?php

namespace Meetanshi\OrderUpload\Model;

use Meetanshi\OrderUpload\Helper\Data;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;

/**
 * Class OrderUploadConfigProvider
 * @package Meetanshi\OrderUpload\Model
 */
class OrderUploadConfigProvider implements ConfigProviderInterface
{
    /**
     *
     */
    const SYSTEM_PATH_MODULE_ENABLE = 'orderupload/general/enabled';

    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * OrderUploadConfigProvider constructor.
     * @param UrlInterface $urlBuilder
     * @param Data $helper
     */
    public function __construct(UrlInterface $urlBuilder, Data $helper)
    {
        $this->urlBuilder = $urlBuilder;
        $this->helper = $helper;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        return ['orderupload' => ['uploadUrl' => $this->urlBuilder->getUrl('orderupload/upload/checkout'), 'removeUrl' => $this->urlBuilder->getUrl('orderupload/upload/remove'), 'enabledModule' => $this->check(), 'allowedSize' => $this->helper->getFileExtensions(), 'maxFileSize' => $this->helper->getMaxFileSize(), 'mediaPath' => $this->helper->pubMediaPath(), 'tempMediaPath'=> $this->helper->tempMediaPath(),'allowComment' => $this->checkComment()]];
    }

    /**
     * @return bool
     */
    public function check()
    {
        $moduleEnabled = $this->helper->getStoreConfig(self::SYSTEM_PATH_MODULE_ENABLE);
        $valid = $this->helper->isValidCustomerGroup();
        $allowOnCheckout = $this->helper->allowOnCheckout();
        if ($moduleEnabled && $valid && $allowOnCheckout) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function checkComment()
    {
        $moduleEnabled = $this->helper->getStoreConfig(self::SYSTEM_PATH_MODULE_ENABLE);
        $allowOnCheckout = $this->helper->allowOnCheckout();
        $comment = $this->helper->addCommentFromCheckout();
        if ($allowOnCheckout && $comment && $moduleEnabled) {
            return true;
        }
        return false;
    }
}
