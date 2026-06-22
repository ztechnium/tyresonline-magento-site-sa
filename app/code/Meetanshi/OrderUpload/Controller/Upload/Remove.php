<?php

namespace Meetanshi\OrderUpload\Controller\Upload;

use Meetanshi\OrderUpload\Model\OrderUpload\FileUploaderFactory;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Json\Helper\Data;

/**
 * Class Remove
 * @package Meetanshi\OrderUpload\Controller\Upload
 */
class Remove extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var FileUploaderFactory
     */
    private $fileUploaderFactory;
    /**
     * @var Cart
     */
    private $cart;
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * Remove constructor.
     * @param Context $context
     * @param Cart $cart
     * @param CartRepositoryInterface $quoteRepository
     * @param JsonFactory $resultJsonFactory
     * @param Filesystem $filesystem
     * @param FileUploaderFactory $fileUploaderFactory
     * @param Data $data
     */
    public function __construct(Context $context, Cart $cart, CartRepositoryInterface $quoteRepository, JsonFactory $resultJsonFactory, Filesystem $filesystem, FileUploaderFactory $fileUploaderFactory, Data $data)
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystem = $filesystem;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->cart = $cart;
        $this->quoteRepository = $quoteRepository;
        $this->jsonHelper = $data;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $fileName = $this->getRequest()->getParam('fileName');
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $result = [];
        $resultJson = $this->resultJsonFactory->create();
        try {
            $i = 0;
            $flag = 0;
            $cartQuote = $this->cart->getQuote();
            $fileData = $cartQuote->getData('file_data');
            foreach ($this->jsonHelper->jsonDecode($fileData) as $file) {
                if ($file['name'] == $fileName && $flag == 0) {
                    $flag = 1;
                } else {
                    $result[$i] = $file;
                    $i++;
                }
            }
            $cartQuote->setFileData(json_encode($result));
            $this->quoteRepository->save($cartQuote);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            return $resultJson->setData(['success' => false, 'msg' => $e->getMessage()]);
        }
        $this->messageManager->addSuccessMessage(__('Attachments details have been saved successfully.'));
        return $resultJson->setData(['success' => true, 'msg' => 'deleted']);
    }
}
