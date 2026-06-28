#!/usr/bin/env php
<?php
/**
 * Fix homepage Mageplaza banner slider UAE copy → KSA (Arabic + EN fields).
 * Run: cd /var/www/magento && sudo -u www-data php infra/scripts/fix-homepage-banners-ksa.php
 */
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('adminhtml');
} catch (\Exception $e) {
}

$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$table = 'mageplaza_bannerslider_banner';

$pairs = [
    'TyresOnline.ae' => 'TyresOnline.sa',
    'tyresonline.ae' => 'tyresonline.sa',
    'Tyres Online UAE' => 'Tyres Online KSA',
    'TyresOnline UAE' => 'TyresOnline KSA',
    'tyresonline UAE' => 'tyresonline KSA',
    'in the UAE' => 'in the KSA',
    'In The UAE' => 'In The KSA',
    'in UAE' => 'in KSA',
    'In UAE' => 'In KSA',
    "UAE's Top Tyre Brands" => "KSA's Top Tyre Brands",
    'UAE Top Tyre Brands' => 'KSA Top Tyre Brands',
    'Lowest Price In The UAE' => 'Lowest Price In The KSA',
    'Get 15% Off on UAE\'s Top Tyre Brands' => 'Get 15% Off on KSA\'s Top Tyre Brands',
    'mobile-tyre-fitting-service-in-uae' => 'mobile-tyre-fitting-service-in-ksa',
    'car-battery-discount-offer-uae' => 'car-battery-discount-offer-ksa',
    'تايرز اونلاين الإمارات' => 'تايرز اونلاين السعودية',
    'TyresOnline الإمارات' => 'TyresOnline السعودية',
    'الإمارات العربية المتحدة' => 'المملكة العربية السعودية',
    'في الإمارات السبعة' => 'في جميع أنحاء المملكة العربية السعودية',
    'في أي إمارة من الإمارات السبعة' => 'في أي مكان في المملكة العربية السعودية',
    'أدنى سعر في الإمارات العربية المتحدة' => 'أدنى سعر في المملكة العربية السعودية',
    'احصل على خصم 15% على أفضل العلامات التجارية للإطارات في الإمارات العربية المتحدة' => 'احصل على خصم 15% على أفضل العلامات التجارية للكفرات في المملكة العربية السعودية',
    'على أفضل العلامات التجارية للإطارات في الإمارات العربية المتحدة' => 'على أفضل العلامات التجارية للكفرات في المملكة العربية السعودية',
    'دولة الإمارات العربية المتحدة' => 'المملكة العربية السعودية',
    'دولة المملكة العربية السعودية' => 'المملكة العربية السعودية',
    'في الإمارات' => 'في السعودية',
    'الإمارات' => 'المملكة العربية السعودية',
];

// Avoid over-replacing short substring inside already-fixed strings — apply longer phrases first (already ordered).

$cols = array_keys(array_filter(
    $conn->describeTable($table),
    fn($c) => in_array(strtolower($c['DATA_TYPE'] ?? ''), ['varchar', 'text', 'mediumtext', 'longtext'], true)
));

$updates = 0;
foreach ($conn->fetchAll("SELECT banner_id, " . implode(', ', $cols) . " FROM {$table}") as $row) {
    $id = (int)$row['banner_id'];
    $patch = [];
    foreach ($cols as $col) {
        $val = $row[$col] ?? null;
        if ($val === null || $val === '') {
            continue;
        }
        $new = $val;
        foreach ($pairs as $from => $to) {
            $new = str_replace($from, $to, $new);
        }
        if ($new !== $val) {
            $patch[$col] = $new;
        }
    }
    if ($patch) {
        $conn->update($table, $patch, ['banner_id = ?' => $id]);
        echo "UPDATED banner {$id}: " . implode(', ', array_keys($patch)) . "\n";
        $updates++;
    }
}

// Homepage CMS blocks that may still embed UAE in slider HTML
$blockPairs = $pairs;
foreach ($conn->fetchAll(
    "SELECT block_id, identifier FROM cms_block WHERE identifier LIKE '%offer%' OR identifier LIKE '%home%' OR identifier LIKE '%banner%'"
) as $b) {
    $content = $conn->fetchOne('SELECT content FROM cms_block WHERE block_id = ?', [$b['block_id']]);
    if (!$content || strpos($content, 'الإمارات') === false && stripos($content, 'UAE') === false) {
        continue;
    }
    $new = $content;
    foreach ($blockPairs as $from => $to) {
        $new = str_replace($from, $to, $new);
    }
    if ($new !== $content) {
        $conn->update('cms_block', ['content' => $new], ['block_id = ?' => $b['block_id']]);
        echo "UPDATED cms_block {$b['block_id']} ({$b['identifier']})\n";
        $updates++;
    }
}

$om->get(\Magento\Framework\App\Cache\Manager::class)->flush(
    array_keys($om->get(\Magento\Framework\App\Cache\TypeListInterface::class)->getTypes())
);
echo "Done {$updates} records\n";
