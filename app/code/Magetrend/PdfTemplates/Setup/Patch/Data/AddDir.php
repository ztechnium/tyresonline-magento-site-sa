<?php

namespace Magetrend\PdfTemplates\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class AddDir implements DataPatchInterface
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * InstallSchema constructor.
     * @param File $io
     * @param DirectoryList $directoryList
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $mediaDir->create('pdftemplates');
        $mediaDir->create('pdftemplates/files');
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
