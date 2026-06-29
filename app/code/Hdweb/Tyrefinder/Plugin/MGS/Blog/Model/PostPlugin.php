<?php

declare(strict_types=1);

namespace Hdweb\Tyrefinder\Plugin\MGS\Blog\Model;

use Hdweb\Tyrefinder\Helper\ProductImage as ProductImageHelper;
use MGS\Blog\Model\Post;

class PostPlugin
{
    public function __construct(
        private readonly ProductImageHelper $productImageHelper
    ) {
    }

    public function afterGetThumbnailUrl(Post $subject, string $result): string
    {
        return $this->productImageHelper->getBlogThumbnailUrl($subject);
    }
}
