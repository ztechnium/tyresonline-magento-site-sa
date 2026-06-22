<?php

namespace Meetanshi\OrderUpload\Controller\Upload;

use Meetanshi\OrderUpload\Model\OrderUpload\FileUploaderFactory;
use Meetanshi\OrderUpload\Model\OrderUpload;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Class Checkout
 * @package Meetanshi\OrderUpload\Controller\Upload
 */
class Checkout extends Action
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
     * Checkout constructor.
     * @param Context $context
     * @param Cart $cart
     * @param CartRepositoryInterface $quoteRepository
     * @param JsonFactory $resultJsonFactory
     * @param Filesystem $filesystem
     * @param FileUploaderFactory $fileUploaderFactory
     */
    public function __construct(Context $context, Cart $cart, CartRepositoryInterface $quoteRepository, JsonFactory $resultJsonFactory, Filesystem $filesystem, FileUploaderFactory $fileUploaderFactory)
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystem = $filesystem;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->cart = $cart;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $uploadFiles = $this->getRequest()->getFiles('file');
        $result = [];
        try {
            $cartQuote = $this->cart->getQuote();
            $fileData = json_decode($cartQuote->getFileData());

            if ($fileData != null && sizeof($fileData) > 0) {
                foreach ($fileData as $item) {
                    array_push($result, $item);
                }
            }
            $i = 0;
            foreach ($uploadFiles as $file) {
                $fileUploader = $this->fileUploaderFactory->create(['fileId' => 'file[' . $i . ']'])->setAllowRenameFiles(true);
                $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
                array_push($result, $fileUploader->save($mediaDirectory->getAbsolutePath(OrderUpload::ORDERUPLOAD_TMP_PATH)));
                $i++;
            }
            $cartQuote->setFileData(json_encode($result));
            $this->quoteRepository->save($cartQuote);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($result);
    }
}
