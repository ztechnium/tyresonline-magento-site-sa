<?php

declare(strict_types=1);

namespace Hdweb\Tyrefinder\Plugin\Catalog\Helper;

use Hdweb\Tyrefinder\Helper\ProductImage as ProductImageHelper;
use Magento\Catalog\Helper\Image as CatalogImageHelper;
use Magento\Catalog\Model\Product;

class ImagePlugin
{
    private bool $resolvingFallback = false;

    public function __construct(
        private readonly ProductImageHelper $productImageHelper
    ) {
    }

    public function afterGetUrl(CatalogImageHelper $subject, string $result): string
    {
        if ($this->resolvingFallback || $result === '') {
            return $result;
        }

        try {
            $product = $subject->getProduct();
            if (!$product instanceof Product || !$product->getId()) {
                return $result;
            }

            $imageFile = (string) $subject->getImageFile();
            if ($imageFile === '' || $imageFile === 'no_selection') {
                return $result;
            }

            if ($this->productImageHelper->catalogMediaFileExists($imageFile)) {
                return $result;
            }

            return $this->resolveFallback($product, $subject);
        } catch (\Throwable) {
            return $result;
        }
    }

    private function resolveFallback(Product $product, CatalogImageHelper $subject): string
    {
        $this->resolvingFallback = true;

        try {
            $width = (int) ($subject->getWidth() ?: 300);
            $height = (int) ($subject->getHeight() ?: 300);
            $imageRole = (string) ($subject->getType() ?: 'category_page_grid');

            return $this->productImageHelper->resolveMissingProductImageUrl(
                $product,
                $imageRole,
                $width,
                $height
            );
        } finally {
            $this->resolvingFallback = false;
        }
    }
}
