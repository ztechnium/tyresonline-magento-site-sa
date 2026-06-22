<?php

namespace Meetanshi\OrderUpload\Helper;

use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Meetanshi\OrderUpload\Helper\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Email
 * @package Meetanshi\OrderUpload\Helper
 */
class Email extends AbstractHelper
{
    const XML_PATH_EMAIL_TEMPLATE_FIELD = 'orderupload/email/email_template';
    const XML_PATH_ADD_ATTACHMENT = 'orderupload/email/attachment';

    /**
     * @var Context
     */
    protected $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var StateInterface
     */
    protected $inlineTranslation;
    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var
     */
    protected $tempId;

    /**
     * Email constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param StateInterface $inlineTranslation
     * @param TransportBuilder $transportBuilder
     */
    public function __construct(Context $context, StoreManagerInterface $storeManager, StateInterface $inlineTranslation, TransportBuilder $transportBuilder)
    {
        $this->scopeConfig = $context;
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * @param $emailTemplateVariables
     * @param $senderInfo
     * @param $receiverInfo
     * @param $file
     * @param null $storeId
     * @throws LocalizedException
     */
    public function customMailSendMethod($emailTemplateVariables, $senderInfo, $receiverInfo, $file, $storeId = null)
    {
        try {
            $this->tempId = $this->getTemplateId(self::XML_PATH_EMAIL_TEMPLATE_FIELD, $storeId);
            $this->inlineTranslation->suspend();
            $this->generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo, $file);
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param $xmlPath
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTemplateId($xmlPath, $storeId = null)
    {
        if ($storeId == null) {
            $storeId = $this->getStore()->getStoreId();
        }
        return $this->getConfigValue($xmlPath, $storeId);
    }

    /**
     * @param $path
     * @param $storeId
     * @return mixed
     */
    protected function getConfigValue($path, $storeId)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * @param $emailTemplateVariables
     * @param $senderInfo
     * @param $receiverInfo
     * @param $file
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function generateTemplate($emailTemplateVariables, $senderInfo, $receiverInfo, $file)
    {
        $emailTemplate = $this->transportBuilder->setTemplateIdentifier($this->tempId)->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId(),])->setTemplateVars($emailTemplateVariables)->setFrom($senderInfo)->addTo($receiverInfo['email'], $receiverInfo['name']);
        if ($this->getAddAttachment() == 1) {
            foreach ($file as $fileData) {
                $fileData = explode("=>", $fileData);
                $emailTemplate->addAttachment($fileData[1], $fileData[0]);
            }
        }
        return $this;
    }

    /**
     * @param null $store
     * @return mixed
     */
    public function getAddAttachment($store = null)
    {
        $storeConfig = $this->scopeConfig->getValue(self::XML_PATH_ADD_ATTACHMENT, ScopeInterface::SCOPE_STORE, $store);
        return $storeConfig;
    }
}
