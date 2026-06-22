<?php

namespace Meetanshi\OrderUpload\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Json\Helper\Data;
use Meetanshi\OrderUpload\Helper\Data as HelperData;
use Meetanshi\OrderUpload\Model\OrderUploadFactory;
use Meetanshi\OrderUpload\Helper\Email as EmailData;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class SalesOrderPlaceAfter
 * @package Meetanshi\OrderUpload\Observer
 */
class SalesOrderPlaceAfter implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $jsonHelper;
    /**
     * @var OrderUploadFactory
     */
    protected $orderUploadFactory;
    /**
     * @var EmailData
     */
    protected $emailData;
    /**
     * @var HelperData
     */
    protected $helper;
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * SalesOrderPlaceAfter constructor.
     * @param RequestInterface $request
     * @param HelperData $helper
     * @param Data $jsonHelper
     * @param OrderUploadFactory $orderUploadFactory
     * @param EmailData $emailData
     */
    public function __construct(RequestInterface $request, HelperData $helper, Data $jsonHelper, OrderUploadFactory $orderUploadFactory, EmailData $emailData)
    {
        $this->helper = $helper;
        $this->jsonHelper = $jsonHelper;
        $this->orderUploadFactory = $orderUploadFactory;
        $this->emailData = $emailData;
        $this->request = $request;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
        $comment = $quote->getData('order_comment');
        $fileData = $quote->getData('file_data');

        try {
            if ($fileData != null && sizeof($this->jsonHelper->jsonDecode($fileData)) > 0) {
                $pubMediaUrl = $this->helper->pubMediaPath();
                $dataArray = [];
                $i = 0;
                foreach ($this->jsonHelper->jsonDecode($fileData) as $file) {
                    $model = $this->orderUploadFactory->create();
                    $time = time();
                    $model->setCreatedAt($time);
                    $model->setUpdatedAt($time);
                    $model->setOrderId($order->getId());
                    $model->setCustomerId($order->getCustomerId());
                    $model->setComment($comment);
                    $model->setFileName($file['name']);
                    $model->setFilePath($file['file']);
                    $model->save();

                    $fileName = $file['name'];
                    $filePath = $pubMediaUrl . $file['file'];
                    $dataArray[$i] = $fileName . "=>" . $filePath;
                    $i++;
                }

                if ($this->helper->isSendEmail()) {
                    $adminEmail = $this->helper->getAdminEmail();
                    $adminName = $this->helper->getAdminName();

                    $firstName = $order->getBillingAddress()->getFirstName();
                    $lastName = $order->getBillingAddress()->getLastName();

                    $sender = ['name' => $firstName . ' ' . $lastName, 'email' => $order->getCustomerEmail(),];
                    $receiver = ['name' => $adminName, 'email' => $adminEmail,];

                    $emailTempVariables = [];
                    $emailTempVariables['order_id'] = $order->getIncrementId();
                    $emailTempVariables['update'] = "customer";
                    $emailTempVariables['name'] = $firstName . " " . $lastName;
                    $emailTempVariables['email'] = $order->getCustomerEmail();
                    $storeId = $order->getStore()->getId();
                    $this->emailData->customMailSendMethod($emailTempVariables, $sender, $receiver, $dataArray, $storeId);
                }
                return $this;
            } elseif ($comment != null) {
                $model = $this->orderUploadFactory->create();
                $time = time();
                $model->setCreatedAt($time);
                $model->setUpdatedAt($time);
                $model->setOrderId($order->getId());
                $model->setCustomerId($order->getCustomerId());
                $model->setComment(strip_tags($comment));
                $model->setFileName('');
                $model->setFilePath('');
                $model->save();
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}
