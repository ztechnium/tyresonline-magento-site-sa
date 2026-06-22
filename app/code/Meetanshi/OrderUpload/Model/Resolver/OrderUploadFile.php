<?php

namespace Meetanshi\OrderUpload\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Meetanshi\OrderUpload\Helper\Data as HelperData;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Filesystem;
use Meetanshi\OrderUpload\Model\OrderUpload\FileUploaderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Meetanshi\OrderUpload\Model\OrderUpload;

/**
 * Class OrderUploadFile
 * @package Meetanshi\OrderUpload\Model\Resolver
 */
class OrderUploadFile implements ResolverInterface
{
    /**
     * @var HelperData
     */
    protected $helper;
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var FileUploaderFactory
     */
    private $fileUploaderFactory;

    /**
     * OrderUploadFile constructor.
     * @param GetCartForUser $getCartForUser
     * @param HelperData $helper
     * @param Filesystem $filesystem
     * @param FileUploaderFactory $fileUploaderFactory
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        HelperData $helper,
        Filesystem $filesystem,
        FileUploaderFactory $fileUploaderFactory
    )
    {
        $this->getCartForUser = $getCartForUser;
        $this->helper = $helper;
        $this->filesystem = $filesystem;
        $this->fileUploaderFactory = $fileUploaderFactory;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws GraphQlNoSuchEntityException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        if(!$this->helper->isEnabled()){
            throw new GraphQlNoSuchEntityException(__("Order Upload Extension is disable."));
        }
        try {
            $returnData = ['success' => false];
            if (!isset($args['input']['cart_id'])) {
                throw new GraphQlInputException(__('Required parameter "cart_id" is missing.'));
            }
            if (!isset($args['input']['file'])) {
                throw new GraphQlInputException(__('Required parameter "file" is missing.'));
            }

            $maskedCartId = $args['input']['cart_id'];
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $cartQuote = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);

            $uploadFiles = $args['input']['file'];
            $result = [];
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
            $cartQuote->save();

            $returnData = ['success' => true];
            return $returnData;
        } catch (\Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

        return $returnData;
    }
}
