<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Amasty_PageSpeedOptimizer
*/


declare(strict_types=1);

namespace Amasty\PageSpeedOptimizer\Model\Asset\Collector;

class CssCollector extends AbstractAssetCollector
{
    public const REGEX = '/<link[^>]*href\s*=\s*["|\'](?<asset_url>[^"\']*\.css[^"\']*)["\']+[^>]*>/is';

    public function getAssetContentType(): string
    {
        return 'style';
    }
}
