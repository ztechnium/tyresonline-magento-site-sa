#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('adminhtml'); } catch (\Exception $e) {}
$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

$pairs = [
    'العلامات التجارية للإطارات في الإمارات العربية المتحدة' => 'العلامات التجارية للكفرات في المملكة العربية السعودية',
    'على أفضل العلامات التجارية للإطارات في الإمارات العربية المتحدة' => 'على أفضل العلامات التجارية للكفرات في المملكة العربية السعودية',
    'TyresOnline.ae' => 'TyresOnline.sa',
    'tyresonline.ae' => 'tyresonline.sa',
    'Best Tires in UAE' => 'Best Tyres in KSA',
    'BRANDS WE TRUST | Best Tires in UAE |TyresOnline.ae' => 'BRANDS WE TRUST | Best Tyres in KSA | TyresOnline.sa',
    '© TyresOnline.ae, All Rights Reserved.' => '© TyresOnline.sa, All Rights Reserved.',
    'Tyresonline.ae, All Rights Reserved.' => 'TyresOnline.sa, All Rights Reserved.',
    'Tyres Online UAE' => 'Tyres Online KSA',
    'TyresOnline.ae Home' => 'TyresOnline.sa Home',
];

$updates = 0;
$tableColumns = [
    'cms_block' => ['content'],
    'cms_page' => ['content', 'meta_title', 'meta_description'],
];
foreach ($tableColumns as $table => $columns) {
    $idCol = $table === 'cms_block' ? 'block_id' : 'page_id';
    $colsSql = implode(', ', array_merge([$idCol . ' AS id'], $columns));
    $rows = $conn->fetchAll("SELECT {$colsSql} FROM {$table}");
    foreach ($rows as $row) {
        foreach ($columns as $col) {
            if (!isset($row[$col]) || $row[$col] === null) continue;
            $val = $row[$col];
            $new = $val;
            foreach ($pairs as $from => $to) $new = str_replace($from, $to, $new);
            if ($new !== $val) {
                $conn->update($table, [$col => $new], ["{$idCol} = ?" => $row['id']]);
                echo "UPDATED {$table} {$row['id']} {$col}\n";
                $updates++;
            }
        }
    }
}

foreach ($conn->fetchAll("SELECT config_id, path, value FROM core_config_data WHERE path LIKE 'brand/list_page_settings/%' OR value LIKE '%TyresOnline.ae%' OR value LIKE '%Tyres Online UAE%' OR value LIKE '%tyresonline.ae%' OR value LIKE '%Best Tires in UAE%'") as $row) {
    $new = $row['value'];
    foreach ($pairs as $from => $to) $new = str_replace($from, $to, $new);
    if ($row['path'] === 'brand/list_page_settings/title' && $new === $row['value']) {
        $new = 'TYRE BRANDS WE TRUST | Best Tyres in KSA | TyresOnline.sa';
    }
    if ($row['path'] === 'brand/list_page_settings/description' && (stripos($new, 'المملكة') === false)) {
        $new = '<p>كفرات عالية الجودة من أجلك أينما كنت في المملكة العربية السعودية</p>'
            . '<p>موقع TyresOnline هو وجهتك الأولى والمثالية لشراء الكفرات في المملكة العربية السعودية. يضمن لك موقعنا الحصول على أفضل نوع بأسعارٍ تنافسية لن تجد لها مثيل، ونوفر لك تشكيلة واسعة من أشهر الماركات العالمية في صناعة الكفرات، وكلها من خلال موقعنا الإلكتروني.</p>'
            . '<p>لتسهيل عملية البحث عن الكفرات المناسبة لسيارتك، كل ما عليك أن تبدأ باختيار الكفر الذي تريده من الماركة التي تثق فيها، وإن لم تكن متاحة على موقعنا، تواصل معنا لتبلغنا بنوعه، وفريقنا سيبذل أقصى مجهود لتأمين طلبك.</p>';
    }
    if ($new !== $row['value']) {
        $conn->update('core_config_data', ['value' => $new], ['config_id = ?' => $row['config_id']]);
        echo "UPDATED config {$row['path']} ({$row['config_id']})\n";
        $updates++;
    }
}

// Insert brand list settings for Arabic store if missing
$brandSettings = [
    'brand/list_page_settings/title' => 'TYRE BRANDS WE TRUST | Best Tyres in KSA | TyresOnline.sa',
    'brand/list_page_settings/description' => '<p>كفرات عالية الجودة من أجلك أينما كنت في المملكة العربية السعودية</p><p>موقع TyresOnline هو وجهتك الأولى والمثالية لشراء الكفرات في المملكة العربية السعودية. يضمن لك موقعنا الحصول على أفضل نوع بأسعارٍ تنافسية لن تجد لها مثيل، ونوفر لك تشكيلة واسعة من أشهر الماركات العالمية في صناعة الكفرات، وكلها من خلال موقعنا الإلكتروني.</p><p>لتسهيل عملية البحث عن الكفرات المناسبة لسيارتك، كل ما عليك أن تبدأ باختيار الكفر الذي تريده من الماركة التي تثق فيها، وإن لم تكن متاحة على موقعنا، تواصل معنا لتبلغنا بنوعه، وفريقنا سيبذل أقصى مجهود لتأمين طلبك.</p>',
    'brand/list_page_settings/meta_description' => 'كفرات من علامات تجارية نثق بها في المملكة العربية السعودية | TyresOnline.sa',
];
foreach ($brandSettings as $path => $value) {
    $exists = $conn->fetchOne("SELECT config_id FROM core_config_data WHERE path = ? AND scope = 'stores' AND scope_id = 2", [$path]);
    if ($exists) {
        $conn->update('core_config_data', ['value' => $value], ['config_id = ?' => $exists]);
        echo "SET store config {$path}\n";
        $updates++;
    }
}

$storeConfigPaths = [
    'design/head/default_title' => 'Tyres Online KSA',
    'design/footer/copyright' => '© TyresOnline.sa, All Rights Reserved.',
];
foreach ($storeConfigPaths as $path => $value) {
    foreach ($conn->fetchAll("SELECT config_id, value FROM core_config_data WHERE path = ?", [$path]) as $row) {
        $new = $row['value'];
        foreach ($pairs as $from => $to) $new = str_replace($from, $to, $new);
        if ($new === $row['value'] && (stripos($new, 'UAE') !== false || stripos($new, '.ae') !== false)) {
            $new = $value;
        }
        if ($new !== $row['value']) {
            $conn->update('core_config_data', ['value' => $new], ['config_id = ?' => $row['config_id']]);
            echo "UPDATED store config {$path} ({$row['config_id']})\n";
            $updates++;
        }
    }
}

// Block 18 (anywhere_in_the_uae) remaining UAE wording
$block18 = $conn->fetchOne("SELECT content FROM cms_block WHERE block_id = 18");
if ($block18) {
    $b18 = $block18;
    $b18 = str_replace('في جميع أنحاء الإمارات', 'في جميع أنحاء المملكة العربية السعودية', $b18);
    $b18 = str_replace('الإمارات العربية المتحدة', 'المملكة العربية السعودية', $b18);
    foreach ($pairs as $from => $to) $b18 = str_replace($from, $to, $b18);
    if ($b18 !== $block18) {
        $conn->update('cms_block', ['content' => $b18], ['block_id = ?' => 18]);
        echo "UPDATED cms_block 18 content\n";
        $updates++;
    }
}

$cacheManager = $om->get(\Magento\Framework\App\Cache\Manager::class);
$cacheManager->flush(array_keys($om->get(\Magento\Framework\App\Cache\TypeListInterface::class)->getTypes()));
echo "Done {$updates}\n";
