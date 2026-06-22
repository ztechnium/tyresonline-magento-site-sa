<?php

namespace Meetanshi\OrderUpload\Model;

use Meetanshi\OrderUpload\Model\OrderUpload as AttachmentList;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Framework\Exception\LocalizedException;
use Meetanshi\OrderUpload\Helper\Data;

/**
 * Class OrderUpload
 * @package Meetanshi\OrderUpload\Model
 */
class OrderUpload extends AbstractModel
{
    /**
     *
     */
    const ORDERUPLOAD_TMP_PATH = 'tmp/orderupload/';

    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;
    /**
     * @var Database
     */
    protected $coreFileStorageDatabase;

    /**
     * OrderUpload constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Filesystem $filesystem
     * @param Database $coreFileStorageDatabase
     * @param Data $helper
     * @param array $data
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(Context $context, Registry $registry, Filesystem $filesystem, Database $coreFileStorageDatabase, Data $helper, array $data = [])
    {
        parent::__construct($context, $registry, null, null, $data);
        $this->filesystem = $filesystem;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->coreFileStorageDatabase = $coreFileStorageDatabase;
        $this->helper = $helper;
    }

    /**
     *
     */
    public function _construct()
    {
        $this->_init('Meetanshi\OrderUpload\Model\ResourceModel\OrderUpload');
    }

    /**
     * @param $name
     * @return mixed
     * @throws LocalizedException
     */
    public function moveFileFromTmp($name)
    {
        if ($name != null) {
            $mainPath = $this->helper->attachFilePath();
            $baseFilePath = $mainPath . $name;
            $baseTmpFilePath = AttachmentList::ORDERUPLOAD_TMP_PATH . $name;

            try {
                $this->coreFileStorageDatabase->copyFile($baseTmpFilePath, $baseFilePath);
                $this->mediaDirectory->renameFile($baseTmpFilePath, $baseFilePath);
            } catch (\Exception $e) {
                throw new LocalizedException(__('Something went wrong while saving the file(s).'));
            }
        }
        return $name;
    }

    /**
     * @return AbstractModel
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $fileName = $mediaDirectory->getAbsolutePath(AttachmentList::ORDERUPLOAD_TMP_PATH) . $this->getFilePath();
        if (file_exists($fileName)) {
            $this->moveFileFromTmp($this->getFilePath());
        }
        return parent::beforeSave();
    }

    /**
     * @return mixed
     */
    public function getContentLength()
    {
        $contentLength = $this->getData('content_length');
        if ($contentLength === null) {
            $this->setData('content_length', strlen($this->getContent()));
        }
        return $this->getData('content_length');
    }
}
