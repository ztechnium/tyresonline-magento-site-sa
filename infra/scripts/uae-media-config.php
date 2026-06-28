<?php
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);
$rows = $pdo->query("SELECT path, value FROM core_config_data WHERE path LIKE '%media%' OR path LIKE '%s3%' OR path LIKE '%remote_storage%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['path'] . '=' . $r['value'] . "\n";
}
$cnt = $pdo->query("SELECT COUNT(*) FROM catalog_product_entity_media_gallery WHERE value LIKE '/catalog/product/%'")->fetchColumn();
$cnt2 = $pdo->query("SELECT COUNT(*) FROM catalog_product_entity_media_gallery WHERE value LIKE '/tyresonline-tyres/%'")->fetchColumn();
echo "catalog_product_paths=$cnt tyresonline_paths=$cnt2\n";
