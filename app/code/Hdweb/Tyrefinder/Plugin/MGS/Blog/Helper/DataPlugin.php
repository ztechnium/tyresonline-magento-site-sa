<?php

declare(strict_types=1);

namespace Hdweb\Tyrefinder\Plugin\MGS\Blog\Helper;

use Hdweb\Tyrefinder\Helper\ProductImage as ProductImageHelper;
use MGS\Blog\Helper\Data;

class DataPlugin
{
    public function __construct(
        private readonly ProductImageHelper $productImageHelper
    ) {
    }

    public function afterGetThumbnailPost(Data $subject, string $result, $post): string
    {
        if ($post->getVideoThumbId() !== '' && $post->getVideoThumbId() !== null) {
            return $result;
        }

        $url = $this->productImageHelper->getBlogThumbnailUrl($post);
        $fallback = htmlspecialchars(
            $this->productImageHelper->getContentJsFallbackUrl(),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $title = htmlspecialchars((string) $post->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $src = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<img class="img-responsive" alt="' . $title . '" src="' . $src
            . '" onerror="this.onerror=null;this.src=\'' . $fallback . '\'" />';
    }
}
