<?php
declare(strict_types=1);

namespace Amasty\ImageOptimizer\Model\Image;

use Amasty\ImageOptimizer\Model\ImageProcessor\DumpOriginal;
use Amasty\PageSpeedTools\Model\OptionSource\Resolutions;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Io\File;

class ClearGeneratedImageForFile
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var File
     */
    private $file;

    /**
     * @var WriteInterface
     */
    private $mediaDirectory;

    public function __construct(
        Filesystem $filesystem,
        File $file
    ) {
        $this->filesystem = $filesystem;
        $this->file = $file;
    }

    public function execute($filePath): void
    {
        $filePath = $this->getMediaDirectory()->getRelativePath($filePath);
        $absolutePath = $this->getMediaDirectory()->getAbsolutePath($filePath);
        $toRemoveByPath = [
            DumpOriginal::DUMP_DIRECTORY . $filePath,
            Resolutions::WEBP_DIR . $this->getWebpFilePath($filePath)
        ];
        foreach ($toRemoveByPath as $toRemove) {
            if ($this->getMediaDirectory()->isExist($toRemove)) {
                $this->getMediaDirectory()->delete($toRemove);
            }
        }
        foreach (Resolutions::RESOLUTIONS as $resolutionKey => $resolutionData) {
            $resolutionName = str_replace(
                $filePath,
                $resolutionData['dir'] . $filePath,
                $absolutePath
            );
            $toRemoveResolutionsByPath = [
                $resolutionName,
                $this->getWebpFilePath($resolutionName)
            ];
            foreach ($toRemoveResolutionsByPath as $toRemove) {
                if ($this->getMediaDirectory()->isExist($toRemove)) {
                    $this->getMediaDirectory()->delete($toRemove);
                }
            }
        }
    }

    protected function getWebpFilePath(string $filePath): string
    {
        $pathInfo = $this->file->getPathInfo($filePath);
        if (isset($pathInfo['extension'])) {
            $extension = $pathInfo['extension'];
            $filePath = str_replace(
                '.' . $extension,
                '_'. $extension . '.webp',
                $filePath
            );
        }

        return $filePath;
    }

    protected function getMediaDirectory(): WriteInterface
    {
        if (null === $this->mediaDirectory) {
            $this->mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        }

        return $this->mediaDirectory;
    }
}
