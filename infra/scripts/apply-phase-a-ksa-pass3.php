#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$updates = 0;

$brandTitle = 'TYRE BRANDS WE TRUST | Best Tyres in KSA | TyresOnline.sa';
$brandDesc = '<p>كفرات عالية الجودة من أجلك أينما كنت في المملكة العربية السعودية</p><p>موقع TyresOnline هو وجهتك الأولى والمثالية لشراء الكفرات في المملكة العربية السعودية. يضمن لك موقعنا الحصول على أفضل نوع بأسعارٍ تنافسية لن تجد لها مثيل، ونوفر لك تشكيلة واسعة من أشهر الماركات العالمية في صناعة الكفرات، وكلها من خلال موقعنا الإلكتروني.</p><p>لتسهيل عملية البحث عن الكفرات المناسبة لسيارتك، كل ما عليك أن تبدأ باختيار الكفر الذي تريده من الماركة التي تثق فيها، وإن لم تكن متاحة على موقعنا، تواصل معنا لتبلغنا بنوعه، وفريقنا سيبذل أقصى مجهود لتأمين طلبك.</p>';
$brandMeta = 'كفرات من علامات تجارية نثق بها في المملكة العربية السعودية | TyresOnline.sa';

foreach (['default', 'stores'] as $scope) {
    $scopeId = $scope === 'default' ? 0 : 2;
    foreach ([
        'mgs_brand/list_page_settings/title' => $brandTitle,
        'mgs_brand/list_page_settings/description' => $brandDesc,
        'mgs_brand/list_page_settings/meta_description' => $brandMeta,
    ] as $path => $value) {
        $id = $conn->fetchOne(
            "SELECT config_id FROM core_config_data WHERE path = ? AND scope = ? AND scope_id = ?",
            [$path, $scope, $scopeId]
        );
        if ($id) {
            $conn->update('core_config_data', ['value' => $value], ['config_id = ?' => $id]);
            echo "UPDATED {$scope} {$scopeId} {$path}\n";
        } else {
            $conn->insert('core_config_data', ['scope' => $scope, 'scope_id' => $scopeId, 'path' => $path, 'value' => $value]);
            echo "INSERTED {$scope} {$scopeId} {$path}\n";
        }
        $updates++;
    }
}

// Block 18 remaining UAE strings
$content = $conn->fetchOne('SELECT content FROM cms_block WHERE block_id = 18');
if ($content) {
    $replacements = [
        'العلامات التجارية للإطارات في الإمارات العربية المتحدة' => 'العلامات التجارية للكفرات في المملكة العربية السعودية',
        'في كل أنحاء الإمارات' => 'في جميع أنحاء المملكة العربية السعودية',
        'في أي إمارة من الإمارات السبعة' => 'في أي مكان في المملكة العربية السعودية',
        'أينما كان مكانك في أي إمارة من الإمارات السبعة' => 'أينما كان مكانك في المملكة العربية السعودية',
        'الإمارات العربية المتحدة' => 'المملكة العربية السعودية',
    ];
    $new = $content;
    foreach ($replacements as $from => $to) {
        $new = str_replace($from, $to, $new);
    }
    if ($new !== $content) {
        $conn->update('cms_block', ['content' => $new], ['block_id = ?' => 18]);
        echo "UPDATED cms_block 18\n";
        $updates++;
    }
}

// About page AR (20) alt text etc
$page20 = $conn->fetchOne('SELECT content FROM cms_page WHERE page_id = 20');
if ($page20 && strpos($page20, 'الإمارات') !== false) {
    $new = str_replace(['عن تايرز أونلاين الإمارات', 'الإمارات'], ['عن تايرز أونلاين السعودية', 'المملكة العربية السعودية'], $page20);
    if ($new !== $page20) {
        $conn->update('cms_page', ['content' => $new], ['page_id = ?' => 20]);
        echo "UPDATED cms_page 20\n";
        $updates++;
    }
}

$om->get(\Magento\Framework\App\Cache\Manager::class)->flush(array_keys($om->get(\Magento\Framework\App\Cache\TypeListInterface::class)->getTypes()));
echo "Done {$updates}\n";
