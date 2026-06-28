<?php
declare(strict_types=1);

/**
 * Resolve Magento catalog product image path on disk.
 * Gallery DB values like /m/i/foo.jpg or /tyresonline-tyres/bar.png
 * map to pub/media/catalog/product/...
 */
function magentoMediaPath(string $mediaRoot, string $file): string
{
    $file = ltrim($file, '/');
    if (str_starts_with($file, 'catalog/product/')) {
        return rtrim($mediaRoot, '/') . '/' . $file;
    }
    return rtrim($mediaRoot, '/') . '/catalog/product/' . $file;
}

function magentoMediaExists(string $mediaRoot, string $file): bool
{
    return is_file(magentoMediaPath($mediaRoot, $file));
}
