<?php

namespace Meetanshi\OrderUpload\Controller\Adminhtml\OrderUpload;

use Meetanshi\OrderUpload\Model\Upload as UplaodModel;
use Magento\Framework\App\Action\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Meetanshi\OrderUpload\Model\OrderUploadFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Backend\Model\Auth\Session;
use Meetanshi\OrderUpload\Helper\Data;
use Meetanshi\OrderUpload\Helper\Email;

/**
 * Class Save
 * @package Meetanshi\OrderUpload\Controller\Adminhtml\OrderUpload
 */
class Save extends Action
{
    /**
     * @var UplaodModel
     */
    protected $uploadModel;
    /**
     * @var OrderUploadFactory
     */
    protected $orderUploadFactory;
    /**
     * @var OrderFactory
     */
    protected $orderFactory;
    /**
     * @var CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var Session
     */
    protected $authSession;
    /**
     * @var Email
     */
    protected $emailHelper;

    /**
     * Save constructor.
     * @param Context $context
     * @param UplaodModel $uploadModel
     * @param OrderFactory $orderFactory
     * @param CustomerFactory $customerFactory
     * @param Session $authSession
     * @param Data $helper
     * @param Email $emailHelper
     * @param OrderUploadFactory $orderUploadFactory
     */
    public function __construct(Context $context, UplaodModel $uploadModel, OrderFactory $orderFactory, CustomerFactory $customerFactory, Session $authSession, Data $helper, Email $emailHelper, OrderUploadFactory $orderUploadFactory)
    {
        $this->uploadModel = $uploadModel;
        $this->orderFactory = $orderFactory;
        $this->customerFactory = $customerFactory;
        $this->helper = $helper;
        $this->authSession = $authSession;
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
                $sendMailToCustomer = $this->helper->sendMailToCustomer();
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
                        if (isset($data['visible_customer_account'])) {
                            $model->setVisibleCustomerAccount(1);
                        } else {
                            $model->setVisibleCustomerAccount(0);
                        }
                        $model->setOrderId($orderId);
                        $model->setCustomerId($customerId);

                        $model->setFileName($data['file_name']);
                        $model->setFilePath($data['file_path']);
                        $model->save();
                        $filePath = $pubMediaUrl . $data['file_path'];
                        $sendFile[] = $data['file_name'] . "=>" . $filePath;
                    }
                }

                if ($this->helper->isSendEmail() && $sendMailToCustomer) {
                    $order = $this->orderFactory->create()->load($orderId);
                    $customerName = $order->getCustomerName();
                    $customerEmail = $order->getCustomerEmail();
                    $adminEmail = $this->helper->getAdminEmail();
                    $adminName = $this->helper->getAdminName();
                    $receiver = ['name' => $customerName, 'email' => $customerEmail,];
                    $sender = ['name' => $adminName, 'email' => $adminEmail,];
                    $emailTempVariables = [];
                    $emailTempVariables['order_id'] = $order->getIncrementId();
                    $emailTempVariables['update'] = "admin";
                    $emailTempVariables['name'] = $adminName;
                    $emailTempVariables['email'] = $adminEmail;

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
