<?php

declare(strict_types=1);

namespace Hdweb\Tyrefinder\Helper;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Model\StoreManagerInterface;

class ProductImage extends AbstractHelper
{
    private const THEME_PLACEHOLDER = 'images/tyre-placeholder.svg';
    private const CONTENT_PLACEHOLDER = 'images/content-placeholder.svg';
    private const MEDIA_CONTENT_PLACEHOLDER = 'images/content-placeholder.svg';
    private const MEDIA_PLACEHOLDER = 'catalog/product/placeholder/tyre-placeholder.png';
    private const LAZYLOAD_BLANK = 'images/section/blank.png';
    private const BANNER_MEDIA_PATH = 'mageplaza/bannerslider/banner/image/';
    private const BLOG_MEDIA_PATH = 'mgs_blog/';
    private const PRODUCTION_MEDIA_HOST = 'https://www.tyresonline.sa/media/';
    private const PRODUCTION_MEDIA_BLOCKED_PREFIXES = [
        'wysiwyg/about-us/',
        'images/offers/',
    ];
    private const PRODUCTION_MEDIA_BLOCKED_FILES = [
        'wysiwyg/tyresonline-whatsapp-1.svg',
    ];

    private Filesystem\Directory\ReadInterface $mediaDirectory;

    public function __construct(
        Context $context,
        Filesystem $filesystem,
        private readonly ImageHelper $imageHelper,
        private readonly Productlisting $productListingHelper,
        private readonly StoreManagerInterface $storeManager,
        private readonly AssetRepository $assetRepository
    ) {
        parent::__construct($context);
        $this->mediaDirectory = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
    }

    /**
     * @return string[]
     */
    public function getGalleryImageUrls(
        Product $product,
        string $imageRole = 'category_page_grid',
        int $width = 300,
        int $height = 300
    ): array {
        $urls = [];
        $gallery = $product->getMediaGalleryImages();

        if ($gallery && $gallery->getSize()) {
            foreach ($gallery as $image) {
                $file = (string) $image->getFile();
                if (!$this->catalogMediaFileExists($file)) {
                    continue;
                }
                $url = (string) $image->getUrl();
                if ($url !== '') {
                    $urls[] = $url;
                }
            }
        }

        if ($urls !== []) {
            return $urls;
        }

        return [$this->getFallbackImageUrl($product, $imageRole, $width, $height)];
    }

    public function getFallbackImageUrl(
        Product $product,
        string $imageRole = 'category_page_grid',
        int $width = 300,
        int $height = 300
    ): string {
        $brandUrl = $this->getBrandFallbackUrl($product);
        if ($brandUrl !== null) {
            return $brandUrl;
        }

        $mediaPlaceholder = $this->getMediaPlaceholderUrl();
        if ($mediaPlaceholder !== null) {
            return $mediaPlaceholder;
        }

        try {
            return (string) $this->imageHelper
                ->init($product, $imageRole)
                ->resize($width, $height)
                ->getUrl();
        } catch (\Throwable) {
            return $this->getThemePlaceholderUrl();
        }
    }

    /**
     * Resolve a placeholder when the catalog image file is missing on disk.
     * Avoids calling the catalog image helper to prevent recursive plugin calls.
     */
    public function resolveMissingProductImageUrl(
        Product $product,
        string $imageRole = 'category_page_grid',
        int $width = 300,
        int $height = 300
    ): string {
        $brandUrl = $this->getBrandFallbackUrl($product);
        if ($brandUrl !== null) {
            return $brandUrl;
        }

        $mediaPlaceholder = $this->getMediaPlaceholderUrl();
        if ($mediaPlaceholder !== null) {
            return $mediaPlaceholder;
        }

        return $this->getThemePlaceholderUrl();
    }

    public function getJsFallbackUrl(): string
    {
        $mediaPlaceholder = $this->getMediaPlaceholderUrl();
        if ($mediaPlaceholder !== null) {
            return $mediaPlaceholder;
        }

        return $this->getThemePlaceholderUrl();
    }

    public function getContentJsFallbackUrl(): string
    {
        return $this->getContentPlaceholderUrl();
    }

    public function getLazyloadBlankUrl(): string
    {
        if ($this->mediaFileExists(self::LAZYLOAD_BLANK)) {
            return $this->getMediaBaseUrl() . self::LAZYLOAD_BLANK;
        }

        try {
            return $this->assetRepository->getUrlWithParams(
                'images/blank.png',
                ['area' => 'frontend']
            );
        } catch (\Throwable) {
            return '';
        }
    }

    public function getBlogThumbnailUrl(object $post): string
    {
        return $this->resolveBlogImageUrl(
            (string) $post->getThumbnail(),
            (string) ($post->getContent() ?? '')
        );
    }

    public function getBlogFeaturedImageUrl(object $post): string
    {
        $image = (string) $post->getImage();
        if ($image !== '' && $image !== 'no_image.png') {
            $url = $this->resolveBlogImageUrl($image, '');
            if (!$this->isContentPlaceholderUrl($url)) {
                return $url;
            }
        }

        return $this->getBlogThumbnailUrl($post);
    }

    public function getBannerImageUrl(?string $filename): string
    {
        $filename = trim((string) $filename);
        if ($filename === '') {
            return $this->getContentPlaceholderUrl();
        }

        $relative = self::BANNER_MEDIA_PATH . ltrim($filename, '/');
        return $this->resolveContentMediaPath($relative);
    }

    public function resolveMediaUrl(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return $this->getContentPlaceholderUrl();
        }

        return $this->resolveContentMediaUrl($url);
    }

    /**
     * Resolve a media path or absolute media URL for CMS/content images.
     */
    public function resolveContentMediaUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || $this->isContentPlaceholderUrl($url) || str_contains($url, 'blank.png')) {
            return $url !== '' ? $url : $this->getContentPlaceholderUrl();
        }

        $url = $this->rewriteLegacyMediaHost($url);
        $relative = $this->urlToMediaRelativePath($url);
        if ($relative === null) {
            return filter_var($url, FILTER_VALIDATE_URL) ? $url : $this->getContentPlaceholderUrl();
        }

        return $this->resolveContentMediaPath($relative);
    }

    /**
     * Rewrites legacy UAE media hosts and fixes broken CMS media URLs (carousel, blog, etc.).
     */
    public function normalizeContentHtmlImages(string $html): string
    {
        if ($html === '' || !str_contains($html, 'media')) {
            return $html;
        }

        $html = $this->rewriteLegacyMediaHostsInHtml($html);
        $html = $this->rewriteMediaAttributesInHtml($html);

        $fallback = htmlspecialchars($this->getContentJsFallbackUrl(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($fallback === '') {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<img\b[^>]*>/i',
            static function (array $matches) use ($fallback): string {
                $tag = $matches[0];
                if (stripos($tag, 'onerror=') !== false) {
                    return $tag;
                }

                if (!preg_match('/\s(?:data-)?src=["\']([^"\']+)["\']/i', $tag, $srcMatch)) {
                    return $tag;
                }

                $src = $srcMatch[1];
                if (!str_contains($src, '/media/') && !str_contains($src, 'wysiwyg/')) {
                    return $tag;
                }

                return rtrim(substr($tag, 0, -1))
                    . ' onerror="this.onerror=null;this.src=\'' . $fallback . '\'" />';
            },
            $html
        );
    }

    private function rewriteMediaAttributesInHtml(string $html): string
    {
        return (string) preg_replace_callback(
            '#\b((?:data-)?srcset|(?:data-)?src)=("|\')([^"\']*)(\2)#i',
            function (array $matches): string {
                $attribute = $matches[1];
                $quote = $matches[2];
                $value = $matches[3];

                if (stripos($attribute, 'srcset') !== false) {
                    $value = $this->rewriteSrcsetValue($value);
                } else {
                    $value = $this->rewriteSingleMediaAttributeUrl($value);
                }

                return $attribute . '=' . $quote . $value . $quote;
            },
            $html
        );
    }

    private function rewriteSrcsetValue(string $srcset): string
    {
        $parts = array_map('trim', explode(',', $srcset));
        $rewritten = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (preg_match('/^(\S+)(\s+.+)?$/', $part, $matches)) {
                $url = $this->rewriteSingleMediaAttributeUrl($matches[1]);
                $rewritten[] = $url . ($matches[2] ?? '');
            }
        }

        return implode(', ', $rewritten);
    }

    private function rewriteSingleMediaAttributeUrl(string $url): string
    {
        if ($url === ''
            || $this->isContentPlaceholderUrl($url)
            || str_contains($url, 'blank.png')
        ) {
            return $url;
        }

        if (!str_contains($url, '/media/') && !str_contains($url, 'wysiwyg/')) {
            return $url;
        }

        return $this->resolveContentMediaUrl($url);
    }

    private function resolveContentMediaPath(string $relative): string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        if ($relative === '') {
            return $this->getContentPlaceholderUrl();
        }

        if ($this->mediaFileExists($relative)) {
            return $this->getMediaBaseUrl() . $relative;
        }

        if ($this->shouldUseProductionMediaFallback($relative)) {
            return self::PRODUCTION_MEDIA_HOST . $relative;
        }

        return $this->getContentPlaceholderUrl();
    }

    private function shouldUseProductionMediaFallback(string $relative): bool
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        if ($relative === '' || in_array($relative, self::PRODUCTION_MEDIA_BLOCKED_FILES, true)) {
            return false;
        }

        foreach (self::PRODUCTION_MEDIA_BLOCKED_PREFIXES as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                return false;
            }
        }

        return str_starts_with($relative, 'wysiwyg/');
    }

    public function mediaFileExists(?string $relativePath): bool
    {
        if ($relativePath === null || $relativePath === '') {
            return false;
        }

        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        return $this->mediaDirectory->isFile($relativePath);
    }

    public function catalogMediaFileExists(?string $file): bool
    {
        if ($file === null || $file === '' || $file === 'no_selection') {
            return false;
        }

        $file = ltrim(str_replace('\\', '/', $file), '/');
        if (str_starts_with($file, 'catalog/product/')) {
            return $this->mediaFileExists($file);
        }

        return $this->mediaFileExists('catalog/product/' . $file);
    }

    private function getBrandFallbackUrl(Product $product): ?string
    {
        try {
            $brandValue = $product->getData('mgs_brand');
            if ($brandValue === null || $brandValue === '') {
                return null;
            }

            $brandDetails = $this->productListingHelper->getBrandDetails($brandValue);
            $brandImageUrl = $this->productListingHelper->getBrandImageUrl($brandDetails);
            if (!$brandImageUrl) {
                return null;
            }

            $path = parse_url($brandImageUrl, PHP_URL_PATH);
            if (!is_string($path) || $path === '') {
                return null;
            }

            $relative = ltrim(str_replace('/media/', '', $path), '/');
            if ($this->mediaDirectory->isFile($relative)) {
                return $brandImageUrl;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function getMediaPlaceholderUrl(): ?string
    {
        if (!$this->mediaDirectory->isFile(self::MEDIA_PLACEHOLDER)) {
            return null;
        }

        try {
            return rtrim($this->storeManager->getStore()->getBaseUrl(), '/')
                . '/media/' . self::MEDIA_PLACEHOLDER;
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    private function getThemePlaceholderUrl(): string
    {
        return $this->getThemeAssetUrl(self::THEME_PLACEHOLDER);
    }

    private function getContentPlaceholderUrl(): string
    {
        if ($this->mediaFileExists(self::MEDIA_CONTENT_PLACEHOLDER)) {
            return $this->getMediaBaseUrl() . self::MEDIA_CONTENT_PLACEHOLDER;
        }

        return $this->getThemeAssetUrl(self::CONTENT_PLACEHOLDER);
    }

    private function getThemeAssetUrl(string $assetPath): string
    {
        try {
            return $this->assetRepository->getUrlWithParams(
                $assetPath,
                ['area' => 'frontend']
            );
        } catch (\Throwable) {
            return '';
        }
    }

    private function getMediaBaseUrl(): string
    {
        try {
            return $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );
        } catch (NoSuchEntityException) {
            return '';
        }
    }

    private function getBlogMediaUrl(string $filename): string
    {
        $filename = preg_replace('#^' . preg_quote(self::BLOG_MEDIA_PATH, '#') . '#', '', $filename);
        $relative = self::BLOG_MEDIA_PATH . ltrim($filename, '/');
        if ($this->mediaFileExists($relative)) {
            return $this->getMediaBaseUrl() . $relative;
        }

        return $this->getContentPlaceholderUrl();
    }

    private function resolveBlogImageUrl(string $filename, string $content): string
    {
        $filename = trim($filename);
        if ($filename !== '' && $filename !== 'no_image.png') {
            $localUrl = $this->getBlogMediaUrl($filename);
            if (!$this->isContentPlaceholderUrl($localUrl)) {
                return $localUrl;
            }

            $cdnUrl = $this->getUaeBlogCdnUrl($filename);
            if ($cdnUrl !== null) {
                return $cdnUrl;
            }

            $pexelsUrl = $this->getPexelsFallbackUrl($filename);
            if ($pexelsUrl !== null) {
                return $pexelsUrl;
            }
        }

        $contentImage = $this->getFirstContentImageUrl($content);
        if ($contentImage !== null) {
            return $contentImage;
        }

        return $this->getContentPlaceholderUrl();
    }

    private function getPexelsFallbackUrl(string $filename): ?string
    {
        if (!preg_match('/(\d{5,})\.(jpg|jpeg|png|webp)$/i', $filename, $matches)) {
            return null;
        }

        $photoId = $matches[1];
        return 'https://images.pexels.com/photos/' . $photoId . '/pexels-photo-' . $photoId . '.jpeg?auto=compress&cs=tinysrgb&w=640';
    }

    private function isContentPlaceholderUrl(string $url): bool
    {
        return str_contains($url, 'content-placeholder.svg');
    }

    private function getUaeBlogCdnUrl(string $filename): ?string
    {
        // Legacy UAE CDN paths are not available on KSA; use local media or placeholders.
        return null;
    }

    private function getFirstContentImageUrl(string $content): ?string
    {
        if ($content === '' || !preg_match('/src=["\']([^"\']+)["\']/', $content, $matches)) {
            return null;
        }

        return $this->resolveBlogContentImageSrc(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function resolveBlogContentImageSrc(string $src): ?string
    {
        $src = trim($src);
        if ($src === '') {
            return null;
        }

        if (preg_match('#^\{\{media url=(.+?)\}\}$#', $src, $matches)) {
            $relative = ltrim(str_replace('\\', '/', $matches[1]), '/');
            foreach ([$relative, 'wysiwyg/' . $relative, ltrim(preg_replace('#^wysiwyg/#', '', $relative), '/')] as $candidate) {
                if ($this->mediaFileExists($candidate)) {
                    return $this->getMediaBaseUrl() . ltrim($candidate, '/');
                }
            }

            return $this->resolveContentMediaPath($relative);
        }

        $src = $this->rewriteLegacyMediaHost($src);

        if (preg_match('#^https?://[^/]+/media/(.+)$#i', $src, $matches)) {
            $relative = ltrim($matches[1], '/');
            if ($this->mediaFileExists($relative)) {
                return $this->getMediaBaseUrl() . $relative;
            }

            return $this->resolveContentMediaPath($relative);
        }

        if (str_starts_with($src, '/media/')) {
            $relative = ltrim(substr($src, 7), '/');
            if ($this->mediaFileExists($relative)) {
                return $this->getMediaBaseUrl() . $relative;
            }

            return $this->resolveContentMediaPath($relative);
        }

        if (filter_var($src, FILTER_VALIDATE_URL)) {
            return $src;
        }

        return null;
    }

    private function rewriteLegacyMediaHostsInHtml(string $html): string
    {
        $replacements = [
            'https://media.tyresonline.ae/media/' => self::PRODUCTION_MEDIA_HOST,
            'http://media.tyresonline.ae/media/' => self::PRODUCTION_MEDIA_HOST,
            '//media.tyresonline.ae/media/' => self::PRODUCTION_MEDIA_HOST,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    private function rewriteLegacyMediaHost(string $url): string
    {
        return str_replace(
            [
                'https://media.tyresonline.ae/media/',
                'http://media.tyresonline.ae/media/',
                '//media.tyresonline.ae/media/',
            ],
            self::PRODUCTION_MEDIA_HOST,
            $url
        );
    }

    private function isProductionMediaUrl(string $url): bool
    {
        return str_starts_with($url, self::PRODUCTION_MEDIA_HOST);
    }

    private function urlToMediaRelativePath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return null;
        }

        $mediaPos = strpos($path, '/media/');
        if ($mediaPos !== false) {
            return ltrim(substr($path, $mediaPos + 7), '/');
        }

        return ltrim($path, '/');
    }
}
