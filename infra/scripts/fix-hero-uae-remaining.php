<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$c = $bootstrap->getObjectManager()->get('Magento\Framework\App\ResourceConnection')->getConnection();

$row = $c->fetchRow("SELECT block_id, content FROM cms_block WHERE identifier='home_main_banner_tyres'");
echo "block_id={$row['block_id']}\n";
echo substr($row['content'], 0, 2000) . "\n";

$pairs = [
    'Fit Anywhere in the UAE' => 'Fit Anywhere in the KSA',
    'TyresOnline.ae' => 'TyresOnline.sa',
    'Tyresonline.ae' => 'TyresOnline.sa',
    '700 درهم إماراتي' => '700 ريال سعودي',
    '700 AED' => '700 SAR',
    'in the UAE' => 'in the KSA',
    'In The UAE' => 'In The KSA',
    'الإمارات' => 'المملكة العربية السعودية',
];
$new = $row['content'];
foreach ($pairs as $from => $to) {
    $new = str_replace($from, $to, $new);
}
if ($new !== $row['content']) {
    $c->update('cms_block', ['content' => $new], ['block_id = ?' => $row['block_id']]);
    echo "UPDATED home_main_banner_tyres\n";
} else {
    echo "No CMS block changes needed\n";
}

// Also sweep cms_block + mageplaza for remaining UAE strings
foreach (['cms_block', 'mageplaza_bannerslider_banner'] as $table) {
    $cols = $table === 'cms_block' ? ['content'] : array_keys(array_filter(
        $c->describeTable($table),
        fn($col) => in_array(strtolower($col['DATA_TYPE'] ?? ''), ['varchar', 'text', 'mediumtext', 'longtext'], true)
    ));
    foreach ($c->fetchAll("SELECT * FROM {$table}") as $r) {
        $patch = [];
        $idCol = $table === 'cms_block' ? 'block_id' : 'banner_id';
        foreach ($cols as $col) {
            $val = $r[$col] ?? '';
            if (!$val) continue;
            $n = $val;
            foreach ($pairs as $from => $to) {
                $n = str_replace($from, $to, $n);
            }
            if ($n !== $val) $patch[$col] = $n;
        }
        if ($patch) {
            $c->update($table, $patch, ["{$idCol} = ?" => $r[$idCol]]);
            $name = $r['identifier'] ?? $r['banner_id'];
            echo "UPDATED {$table} {$name}: " . implode(',', array_keys($patch)) . "\n";
        }
    }
}

echo "Done\n";
