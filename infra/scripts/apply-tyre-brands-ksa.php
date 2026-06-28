<?php
/**
 * Apply KSA Tyre Brands page copy per client docx.
 * Run: cd /var/www/magento && sudo -u www-data php infra/scripts/apply-tyre-brands-ksa.php
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

$enDescription = '<p><strong>Quality tyre brands, anywhere in Saudi Arabia.</strong></p>'
    . '<p>TyresOnline.sa is simply the best way to purchase tyres in Saudi Arabia. We ensure that you get the best at a competitive price. Our wide range of quality tyre brands are all available online, making it easy for you to search and find tyres based on your criteria.</p>'
    . '<p>Why not start with the tyre brand you already trust and feel comfortable with? If your preferred brand is not listed here, you can always reach out to us — our team will do their best to get you the tyre brand you want.</p>';

$arDescription = '<p><strong>كفرات عالية الجودة من أجلك أينما كنت في المملكة العربية السعودية</strong></p>'
    . '<p>موقع TyresOnline هو وجهتك الأولى والمثالية لشراء الكفرات في المملكة العربية السعودية. يضمن لك موفعنا الحصول على أفضل نوع بأسعارٍ تنافسية لن تجد لها مثيل، ونوفر لك تشكيلة واسعة من أشهر الماركات العالمية في صناعة الكفرات، وكلها من خلال موقعنا الإلكتروني.</p>'
    . '<p>لتسهيل عملية البحث عن الكفرات المناسبة لسيارتك، كل ما عليك أن تبدأ باختيار الكفر الذي تريده من الماركة التي تثق فيها، وإن لم تكن متاحةً متاحة على موقعنا، تواصل معنا لتبلغنا بنوعه، وفريقنا سيبذل أقصى مجهود لتأمين طلبك.</p>';

$configByStore = [
    0 => [
        'mgs_brand/list_page_settings/title' => 'TYRE BRANDS WE TRUST | Best Tyres in KSA | TyresOnline.sa',
        'mgs_brand/list_page_settings/description' => $enDescription,
        'mgs_brand/list_page_settings/meta_description' => 'Shop trusted tyre brands in Saudi Arabia. TyresOnline.sa offers premium brands at competitive prices with online search and nationwide fitment.',
        'mgs_brand/list_page_settings/meta_keywords' => 'tyre brands, tyresonline, saudi arabia, bridgestone, michelin, pirelli, tyre shop ksa',
    ],
    1 => [
        'mgs_brand/list_page_settings/title' => 'TYRE BRANDS WE TRUST | Best Tyres in KSA | TyresOnline.sa',
        'mgs_brand/list_page_settings/description' => $enDescription,
        'mgs_brand/list_page_settings/meta_description' => 'Shop trusted tyre brands in Saudi Arabia. TyresOnline.sa offers premium brands at competitive prices with online search and nationwide fitment.',
        'mgs_brand/list_page_settings/meta_keywords' => 'tyre brands, tyresonline, saudi arabia, bridgestone, michelin, pirelli, tyre shop ksa',
    ],
    2 => [
        'mgs_brand/list_page_settings/title' => 'كفرات من علامات تجارية نثق بها | TyresOnline.sa',
        'mgs_brand/list_page_settings/description' => $arDescription,
        'mgs_brand/list_page_settings/meta_description' => 'كفرات من علامات تجارية نثق بها في المملكة العربية السعودية | TyresOnline.sa',
        'mgs_brand/list_page_settings/meta_keywords' => 'ماركات كفرات, تايرز اونلاين, السعودية, كفرات السيارات',
    ],
];

function upsertConfig($conn, string $scope, int $scopeId, string $path, string $value): void
{
    $existing = $conn->fetchOne(
        'SELECT config_id FROM core_config_data WHERE path = ? AND scope = ? AND scope_id = ?',
        [$path, $scope, $scopeId]
    );
    if ($existing) {
        $conn->update('core_config_data', ['value' => $value], ['config_id = ?' => $existing]);
        echo "Updated {$scope} {$scopeId} {$path}\n";
    } else {
        $conn->insert('core_config_data', [
            'scope' => $scope,
            'scope_id' => $scopeId,
            'path' => $path,
            'value' => $value,
        ]);
        echo "Inserted {$scope} {$scopeId} {$path}\n";
    }
}

foreach ($configByStore as $scopeId => $paths) {
    $scope = $scopeId === 0 ? 'default' : 'stores';
    foreach ($paths as $path => $value) {
        upsertConfig($conn, $scope, $scopeId, $path, $value);
    }
}

// Website scope can override EN store title on this install.
foreach ($configByStore[1] as $path => $value) {
    upsertConfig($conn, 'websites', 1, $path, $value);
}

$uaePairs = [
    'TyresOnline.ae' => 'TyresOnline.sa',
    'tyresonline.ae' => 'tyresonline.sa',
    'Best Tires in UAE' => 'Best Tyres in KSA',
    'Best Tyres in UAE' => 'Best Tyres in KSA',
    'Quality tyre brands, anywhere in the UAE [In all 7 Emirates].' => 'Quality tyre brands, anywhere in Saudi Arabia.',
    'Quality tyre brands, anywhere in the UAE. All the regions.' => 'Quality tyre brands, anywhere in Saudi Arabia.',
    'purchase tyres in the UAE' => 'purchase tyres in Saudi Arabia',
    'الإمارات العربية المتحدة' => 'المملكة العربية السعودية',
    'في جميع أنحاء الإمارات' => 'في جميع أنحاء المملكة العربية السعودية',
    'ماركات الإطارات' => 'ماركات الكفرات',
];

foreach ($conn->fetchAll("SELECT config_id, path, value FROM core_config_data WHERE path LIKE 'mgs_brand/list_page_settings/%' OR path LIKE 'brand/list_page_settings/%'") as $row) {
    $new = $row['value'];
    foreach ($uaePairs as $from => $to) {
        $new = str_replace($from, $to, $new);
    }
    if ($new !== $row['value']) {
        $conn->update('core_config_data', ['value' => $new], ['config_id = ?' => $row['config_id']]);
        echo "Replaced UAE refs in config {$row['path']}\n";
    }
}

foreach ($conn->fetchAll("SELECT page_id FROM cms_page WHERE identifier = 'all-tyre-brands'") as $row) {
    $pageId = (int)$row['page_id'];
    foreach (['content', 'meta_title', 'meta_description', 'meta_keywords'] as $col) {
        $cols = $conn->describeTable('cms_page');
        if (!isset($cols[$col])) {
            continue;
        }
        $val = (string)$conn->fetchOne("SELECT {$col} FROM cms_page WHERE page_id = ?", [$pageId]);
        $new = $val;
        foreach ($uaePairs as $from => $to) {
            $new = str_replace($from, $to, $new);
        }
        if ($new !== $val && $val !== '') {
            $conn->update('cms_page', [$col => $new], ['page_id = ?' => $pageId]);
            echo "Updated cms_page {$col} page_id={$pageId}\n";
        }
    }
}

echo "Done\n";
