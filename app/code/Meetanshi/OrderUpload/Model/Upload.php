<?php

namespace Meetanshi\OrderUpload\Model;

use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Model\Exception as FrameworkException;
use Magento\Framework\File\Uploader;

/**
 * Class Upload
 * @package Meetanshi\OrderUpload\Model
 */
class Upload
{
    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * Upload constructor.
     * @param UploaderFactory $uploaderFactory
     */
    public function __construct(UploaderFactory $uploaderFactory)
    {
        $this->uploaderFactory = $uploaderFactory;
    }

    /**
     * @param $input
     * @param $destinationFolder
     * @param $data
     * @return mixed|string
     */
    public function uploadFileAndGetName($input, $destinationFolder, $data)
    {
        try {
            if (isset($data[$input]['delete'])) {
                return '';
            } else {
                $uploader = $this->uploaderFactory->create(['fileId' => $input]);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $uploader->setAllowCreateFolders(true);
                $result = $uploader->save($destinationFolder);
                return $result['file'];
            }
        } catch (\Exception $e) {
            if ($e->getCode() != Uploader::TMP_NAME_EMPTY) {
                throw new FrameworkException($e->getMessage());
            } else {
                if (isset($data[$input]['value'])) {
                    return $data[$input]['value'];
                }
            }
        }
        return '';
    }
}
