<?php

namespace Meetanshi\OrderUpload\Model\OrderUpload;

use Meetanshi\OrderUpload\Helper\Data;
use Magento\Framework\File\Uploader;

/**
 * Class FileUploader
 * @package Meetanshi\OrderUpload\Model\OrderUpload
 */
class FileUploader extends Uploader
{
    /**
     * @var bool
     */
    protected $_allowRenameFiles = false;
    /**
     * @var bool
     */
    protected $_enableFilesDispersion = true;
    /**
     * @var null
     */
    protected $_allowedExtensions = null;
    /**
     * @var Data
     */
    protected $helper;

    /**
     * FileUploader constructor.
     * @param string|array $fileId
     * @param Data $helper
     */
    public function __construct($fileId, Data $helper)
    {
        parent::__construct($fileId);
        $this->helper = $helper;
    }

    /**
     * @param array $result
     * @return Uploader
     */
    protected function _afterSave($result)
    {
        $this->_result['text_file_size'] = $this->helper->getDOCFileSize($this->_file['size']);
        return parent::_afterSave($result);
    }
}
