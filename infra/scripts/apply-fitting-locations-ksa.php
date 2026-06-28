<?php
/**
 * Apply KSA Fitting Locations copy (meta + config text) per client docx.
 * Run: cd /var/www/magento && sudo -u www-data php infra/scripts/apply-fitting-locations-ksa.php
 */
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
try {
    $om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');
} catch (\Exception $e) {
}

$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

$metaByStore = [
    1 => [
        'ecomteck_storelocator/seo/meta_title' => 'Find Your Nearest TyresOnline Fitment Location in Saudi Arabia | TyresOnline.sa',
        'ecomteck_storelocator/seo/meta_description' => 'Find the nearest TyresOnline fitment centre in Riyadh, Jeddah, or Dammam for top-quality tyres and expert service. Your local tyre solution is just a click away!',
        'ecomteck_storelocator/seo/meta_keywords' => 'TyresOnline fitment Riyadh, TyresOnline fitment Jeddah, TyresOnline fitment Dammam, tyre fitting Saudi Arabia, fitting locations near me',
    ],
    2 => [
        'ecomteck_storelocator/seo/meta_title' => 'ابحث عن أقرب مركز تركيب كفرات TyresOnline في السعودية | TyresOnline.sa',
        'ecomteck_storelocator/seo/meta_description' => 'اعثر على أقرب مركز تركيب كفرات TyresOnline في الرياض أو جدة أو الدمام. كفرات عالية الجودة وخدمة احترافية — الحل المحلي لكفرات سيارتك على بعد نقرة واحدة!',
        'ecomteck_storelocator/seo/meta_keywords' => 'مراكز تركيب كفرات, تايرز اونلاين, الرياض, جدة, الدمام, كفرات بالقرب مني, السعودية',
    ],
];

$textReplacements = [
    'Tyre Shop Near Me - Find Your Location' => 'متجر كفرات بالقرب مني - ابحث عن موقعك',
    'Find Your Location' => 'ابحث عن موقعك',
    'Find your location' => 'ابحث عن موقعك',
    'Near Me' => 'بالقرب مني',
    'All Over UAE' => 'في جميع أنحاء السعودية',
    'ALL OVER THE UAE' => 'في جميع أنحاء السعودية',
    'في جميع أنحاء الإمارات' => 'في جميع أنحاء السعودية',
    'across the UAE' => 'across Saudi Arabia',
    'wherever your are in the UAE' => 'wherever you are in Saudi Arabia',
    'wherever your are in the UAE' => 'wherever you are in Saudi Arabia',
    'UAE TYRES' => 'KSA TYRES',
    'IN ALL 7 EMIRATES' => 'ALL OVER THE KSA',
    'Tyres Dubai' => 'Tyres Riyadh',
    'Tyres Abu Dhabi' => 'Tyres Jeddah',
    'Tyres Sharjah' => 'Tyres Dammam',
    'Welcome To Tyres Online UAE' => 'Welcome To Tyres Online KSA',
    'Find Your Nearest TyresOnline fitment location in UAE' => 'Find Your Nearest TyresOnline Fitment Location in Saudi Arabia',
];

foreach ($metaByStore as $storeId => $paths) {
    foreach ($paths as $path => $value) {
        $existing = $conn->fetchOne(
            'SELECT config_id FROM core_config_data WHERE path = ? AND scope = ? AND scope_id = ?',
            [$path, 'stores', $storeId]
        );
        if ($existing) {
            $conn->update('core_config_data', ['value' => $value], ['config_id = ?' => $existing]);
            echo "Updated store {$storeId} {$path}\n";
        } else {
            $conn->insert('core_config_data', [
                'scope' => 'stores',
                'scope_id' => $storeId,
                'path' => $path,
                'value' => $value,
            ]);
            echo "Inserted store {$storeId} {$path}\n";
        }
    }
}

foreach ($conn->fetchAll(
    "SELECT config_id, path, value FROM core_config_data WHERE path LIKE 'ecomteck_storelocator/%'"
) as $row) {
    $val = $row['value'];
    $new = $val;
    foreach ($textReplacements as $from => $to) {
        $new = str_replace($from, $to, $new);
    }
    if ($new !== $val) {
        $conn->update('core_config_data', ['value' => $new], ['config_id = ?' => $row['config_id']]);
        echo "Replaced in config {$row['path']}\n";
    }
}

foreach ($conn->fetchAll(
    "SELECT page_id FROM cms_page WHERE identifier IN ('storelocator','fitting-locations')"
) as $row) {
    $pageId = (int)$row['page_id'];
    $content = (string)$conn->fetchOne('SELECT content FROM cms_page WHERE page_id = ?', [$pageId]);
    $new = $content;
    foreach ($textReplacements as $from => $to) {
        $new = str_replace($from, $to, $new);
    }
    if ($new !== $content) {
        $conn->update('cms_page', ['content' => $new], ['page_id = ?' => $pageId]);
        echo "Updated cms_page page_id={$pageId}\n";
    }
    foreach (['meta_title', 'meta_description', 'meta_keywords'] as $col) {
        $meta = (string)$conn->fetchOne("SELECT {$col} FROM cms_page WHERE page_id = ?", [$pageId]);
        $newMeta = $meta;
        foreach ($textReplacements as $from => $to) {
            $newMeta = str_replace($from, $to, $newMeta);
        }
        if ($newMeta !== $meta && $meta !== '') {
            $conn->update('cms_page', [$col => $newMeta], ['page_id = ?' => $pageId]);
            echo "Updated cms_page {$col} page_id={$pageId}\n";
        }
    }
}

echo "Done\n";
