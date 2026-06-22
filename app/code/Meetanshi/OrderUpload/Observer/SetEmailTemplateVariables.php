<?php

namespace Meetanshi\OrderUpload\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Json\Helper\Data;
use Meetanshi\OrderUpload\Helper\Data as HelperData;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\QuoteFactory;

/**
 * Class SetEmailTemplateVariables
 * @package Meetanshi\OrderUpload\Observer
 */
class SetEmailTemplateVariables implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $jsonHelper;
    /**
     * @var HelperData
     */
    protected $helper;
    /**
     * @var
     */
    protected $scopeConfig;
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $storeInterface;

    /**
     * SetEmailTemplateVariables constructor.
     * @param HelperData $helper
     * @param Data $jsonHelper
     * @param StoreManagerInterface $storeInterface
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(HelperData $helper, Data $jsonHelper, StoreManagerInterface $storeInterface, QuoteFactory $quoteFactory)
    {
        $this->helper = $helper;
        $this->jsonHelper = $jsonHelper;
        $this->quoteFactory = $quoteFactory;
        $this->storeInterface = $storeInterface;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $quoteId = $observer->getEvent()->getTransport()->getOrder()->getQuoteId();
        $transport = $observer->getEvent()->getTransport();
        $quote = $this->quoteFactory->create()->load($quoteId);
        $fileData = $quote->getFileData();
        if (isset($fileData) && !empty($fileData) && $this->helper->sendEmailSeperately() == 2 && $this->helper->isSendEmail() && $this->helper->addAttachmentInEmail()) {
            $transport->setData('file_attach', 1);
            $fileData = $this->jsonHelper->jsonDecode($fileData);
            $i=0;
            foreach ($fileData as $file) {
                $fileName = $file['name'];
                $transport->setData('file_name'.$i, $fileName);
                $transport->setData('file_path'.$i, $file['file']);
                $i++;
            }
            $transport->setData('total_file', $i);
        } else {
            $transport->setData('file_attach', 0);
        }
    }
}
