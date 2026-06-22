<?php

namespace Meetanshi\OrderUpload\Controller\Upload;

use Meetanshi\OrderUpload\Model\Upload as UploadModel;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Customer\Model\CustomerFactory;
use Meetanshi\OrderUpload\Helper\Data;
use Meetanshi\OrderUpload\Helper\Email;
use Meetanshi\OrderUpload\Model\OrderUploadFactory;

/**
 * Class Save
 * @package Meetanshi\OrderUpload\Controller\Upload
 */
class Save extends Action
{
    /**
     * @var UploadModel
     */
    protected $uploadModel;
    /**
     * @var OrderUploadFactory
     */
    protected $orderUploadFactory;
    /**
     * @var CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var Email
     */
    protected $emailHelper;
    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * Save constructor.
     * @param Context $context
     * @param UploadModel $uploadModel
     * @param OrderFactory $orderFactory
     * @param CustomerFactory $customerFactory
     * @param Data $helper
     * @param Email $emailHelper
     * @param OrderUploadFactory $orderUploadFactory
     */
    public function __construct(Context $context, UploadModel $uploadModel, OrderFactory $orderFactory, CustomerFactory $customerFactory, Data $helper, Email $emailHelper, OrderUploadFactory $orderUploadFactory)
    {
        $this->uploadModel = $uploadModel;
        $this->orderFactory = $orderFactory;
        $this->customerFactory = $customerFactory;
        $this->helper = $helper;
        $this->emailHelper = $emailHelper;
        $this->orderUploadFactory = $orderUploadFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $post = $this->getRequest()->getPostValue();
        if (isset($post['orderupload'])) {
            try {
                $postData = $post['orderupload'];
                $time = time();
                $orderId = $this->getRequest()->getPostValue('order_id');
                $customerId = $this->getRequest()->getPostValue('customer_id');
                $sendFile = [];
                $pubMediaUrl = $this->helper->pubMediaPath();
                $sendMailToAdmin = $this->helper->sendMailToAdmin();
                foreach ($postData as $data) {
                    $model = $this->orderUploadFactory->create();
                    if (isset($data['remove'])) {
                        if (isset($data['file_exist']) && !empty($data['file_exist'])) {
                            $model->load($data['file_exist']);
                            $model->delete();
                        }
                    } else {
                        if (isset($data['new_file']) && !empty($data['new_file'])) {
                            $model->setCreatedAt($time);
                            $model->setUpdatedAt($time);
                        }
                        if (isset($data['file_exist']) && !empty($data['file_exist'])) {
                            $model->load($data['file_exist']);
                            $model->setUpdatedAt($time);
                        }
                        $model->setComment(strip_tags($data['comment']));
                        $model->setVisibleCustomerAccount(1);
                        $model->setOrderId($orderId);
                        $model->setCustomerId($customerId);
                        $model->setFileName($data['file_name']);
                        $model->setFilePath($data['file_path']);
                        $model->save();

                        $filePath = $pubMediaUrl . $data['file_path'];
                        $sendFile[] = $data['file_name'] . "=>" . $filePath;
                    }
                }

                if ($this->helper->isSendEmail() && $sendMailToAdmin) {
                    $order = $this->orderFactory->create()->load($orderId);
                    $customer = $this->customerFactory->create()->load($customerId);
                    $customerName = $customer->getName();
                    $customerEmail = $customer->getEmail();
                    $adminEmail = $this->helper->getAdminEmail();
                    $adminName = $this->helper->getAdminName();

                    $sender = ['name' => $customerName, 'email' => $customerEmail,];
                    $receiver = ['name' => $adminName, 'email' => $adminEmail,];

                    $emailTempVariables = [];
                    $emailTempVariables['order_id'] = $order->getIncrementId();
                    $emailTempVariables['update'] = "customer";
                    $emailTempVariables['name'] = $customerName;
                    $emailTempVariables['email'] = $customerEmail;
                    $storeId = $order->getStore()->getId();
                    $this->emailHelper->customMailSendMethod($emailTempVariables, $sender, $receiver, $sendFile, $storeId);
                }

                $this->messageManager->addSuccessMessage(__('Attachments details have been saved successfully.'));
                $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                return $resultRedirect;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                return $resultRedirect;
            }
        } else {
            $this->messageManager->addWarningMessage(__('Please add file(s) and try again.'));
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }
    }
}
