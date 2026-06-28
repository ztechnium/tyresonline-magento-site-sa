#!/usr/bin/env php
<?php
/**
 * Fix Arabic homepage translations:
 * - Ensure AR store uses Hditsol/tyresonline-ar theme
 * - Translate home_brands CMS block for Arabic store view
 * - Flush translation cache
 */
use Magento\Framework\App\Bootstrap;
use Magento\Store\Model\ScopeInterface;

require __DIR__ . '/../../app/bootstrap.php';
$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();
$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$configWriter = $om->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);
$cacheManager = $om->get(\Magento\Framework\App\Cache\Manager::class);

$arStore = $storeManager->getStore('ar');
$arStoreId = (int) $arStore->getId();

echo "=== AR store id={$arStoreId} locale={$arStore->getConfig('general/locale/code')} ===\n";

$arThemeId = $conn->fetchOne(
    "SELECT theme_id FROM theme WHERE theme_path = 'Hditsol/tyresonline-ar' LIMIT 1"
);
if (!$arThemeId) {
    echo "ERROR: Hditsol/tyresonline-ar theme not registered\n";
    exit(1);
}

$currentThemeId = $arStore->getConfig('design/theme/theme_id');
echo "Current AR theme_id={$currentThemeId}, target={$arThemeId}\n";

if ((string) $currentThemeId !== (string) $arThemeId) {
    $configWriter->save('design/theme/theme_id', $arThemeId, ScopeInterface::SCOPE_STORES, $arStoreId);
    echo "UPDATED AR store theme to tyresonline-ar (id={$arThemeId})\n";
} else {
    echo "AR store theme already correct\n";
}

$titleAr = 'ماركات إطارات معتمدة';
$paraAr = 'لدينا علامات تجارية تلبي كل الأذواق والاحتياجات، من العلامات العالمية الراسخة والحائزة على جوائز والمبتكرة، إلى العلامات الناشئة في السوق التي تسعى لترك بصمتها.';
$paraEn = 'We have brands for every taste and need. From globally established, award winning and innovative brands to newcomers in the market looking to make their mark.';

$row = $conn->fetchRow(
    "SELECT block_id, content FROM cms_block WHERE identifier = ?",
    ['home_brands']
);
if ($row) {
    $content = (string) $row['content'];
    $updated = $content;
    $updated = preg_replace(
        '/<h2[^>]*>\s*Authorized\s*<span>\s*Tyre brands\s*<\/span>\s*<\/h2>/i',
        '<h2 class="text-uppercase">' . $titleAr . '</h2>',
        $updated
    );
    $updated = str_replace($paraEn, $paraAr, $updated);
    if ($updated !== $content) {
        $conn->update('cms_block', ['content' => $updated], ['block_id = ?' => (int) $row['block_id']]);
        echo "UPDATED cms_block home_brands\n";
    } else {
        echo "home_brands CMS already translated or structure differs\n";
    }
}

$cacheManager->flush(['config', 'full_page', 'block_html', 'translate']);
echo "Cache flushed (config, fpc, block_html, translate)\n";
echo "Done.\n";
