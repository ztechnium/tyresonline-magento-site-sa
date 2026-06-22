<?php
declare(strict_types=1);

namespace Amasty\ImageOptimizer\Model\OptionSource;

use Amasty\PageSpeedTools\Model\OptionSource\ToOptionArrayTrait;
use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    public const DISABLED = 0;
    public const ENABLED = 1;

    use ToOptionArrayTrait;

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::DISABLED => __('Disabled'),
            self::ENABLED => __('Enabled'),
        ];
    }
}
