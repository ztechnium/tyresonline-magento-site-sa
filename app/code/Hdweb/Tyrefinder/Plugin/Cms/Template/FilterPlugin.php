<?php

declare(strict_types=1);

namespace Hdweb\Tyrefinder\Plugin\Cms\Template;

use Hdweb\Tyrefinder\Helper\ProductImage as ProductImageHelper;
use Magento\Cms\Model\Template\Filter;

class FilterPlugin
{
    public function __construct(
        private readonly ProductImageHelper $productImageHelper
    ) {
    }

    public function afterFilter(Filter $subject, string $result): string
    {
        return $this->productImageHelper->normalizeContentHtmlImages($result);
    }
}
