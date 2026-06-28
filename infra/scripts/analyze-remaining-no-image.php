<?php
declare(strict_types=1);
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);

$brandAttr = (int)$pdo->query("SELECT attribute_id FROM eav_attribute WHERE attribute_code='mgs_brand' AND entity_type_id=4")->fetchColumn();
$imageAttr = (int)$pdo->query("SELECT attribute_id FROM eav_attribute WHERE attribute_code='image' AND entity_type_id=4")->fetchColumn();

$sql = "
SELECT cpe.sku, bi.value AS brand_opt
FROM catalog_product_entity cpe
LEFT JOIN catalog_product_entity_varchar img ON img.entity_id=cpe.entity_id AND img.attribute_id=$imageAttr AND img.store_id=0
LEFT JOIN catalog_product_entity_int bi ON bi.entity_id=cpe.entity_id AND bi.attribute_id=$brandAttr AND bi.store_id=0
WHERE img.value IS NULL OR img.value='' OR img.value='no_selection'
";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$brands = [];
foreach ($rows as $r) {
    $lbl = $pdo->prepare('SELECT value FROM eav_attribute_option_value WHERE option_id=? AND store_id=0');
    $lbl->execute([(int)$r['brand_opt']]);
    $name = (string)($lbl->fetchColumn() ?: 'unknown');
    $brands[$name] = ($brands[$name] ?? 0) + 1;
}
arsort($brands);
echo 'remaining=' . count($rows) . ' brands=' . count($brands) . "\n";
foreach ($brands as $b => $c) {
    $hasLogo = $pdo->prepare('SELECT image FROM mgs_brand WHERE LOWER(name)=LOWER(?) LIMIT 1');
    $hasLogo->execute([$b]);
    $img = $hasLogo->fetchColumn();
    echo "$c\t$b\t" . ($img ? 'has_logo' : 'NO_LOGO') . "\n";
}
