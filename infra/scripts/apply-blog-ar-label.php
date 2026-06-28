#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';
$conn = Bootstrap::create(BP, $_SERVER)->getObjectManager()
    ->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

$arStoreId = 2;
$updates = [
    'blog/general_settings/title' => 'المدونة',
    'blog/general_settings/meta_title' => 'المدونة | TyresOnline.sa',
    'blog/general_settings/meta_keywords' => 'المدونة, كفرات, السعودية, TyresOnline.sa',
    'blog/general_settings/meta_description' => 'اقرأ أحدث مقالات تايرز أونلاين عن الكفرات والسيارات في المملكة العربية السعودية.',
    'design/header/translate_title' => '1',
];

foreach ($updates as $path => $value) {
    $row = $conn->fetchRow(
        'SELECT config_id, value FROM core_config_data WHERE path = ? AND scope = ? AND scope_id = ?',
        [$path, 'stores', $arStoreId]
    );
    if ($row) {
        if ($row['value'] !== $value) {
            $conn->update('core_config_data', ['value' => $value], ['config_id = ?' => $row['config_id']]);
            echo "UPDATED $path\n";
        } else {
            echo "OK $path\n";
        }
    } else {
        $conn->insert('core_config_data', [
            'scope' => 'stores',
            'scope_id' => $arStoreId,
            'path' => $path,
            'value' => $value,
        ]);
        echo "INSERTED $path\n";
    }
}

echo "Done.\n";
