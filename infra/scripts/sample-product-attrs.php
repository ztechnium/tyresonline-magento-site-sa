#!/usr/bin/env php
<?php
declare(strict_types=1);
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

function attrId(PDO $pdo, string $code): int {
    static $cache = [];
    if (!isset($cache[$code])) {
        $stmt = $pdo->prepare("SELECT attribute_id FROM eav_attribute WHERE attribute_code = ? AND entity_type_id = 4");
        $stmt->execute([$code]);
        $cache[$code] = (int)$stmt->fetchColumn();
    }
    return $cache[$code];
}

$tyreSizeId = attrId($pdo, 'tyre_size');
$brandId = attrId($pdo, 'mgs_brand');

$sql = "
SELECT cpe.sku,
       ts.value AS tyre_size,
       COALESCE(b_int.value, b_varchar.value) AS brand
FROM catalog_product_entity cpe
LEFT JOIN catalog_product_entity_int ts ON ts.entity_id = cpe.entity_id AND ts.attribute_id = ? AND ts.store_id = 0
LEFT JOIN catalog_product_entity_int b_int ON b_int.entity_id = cpe.entity_id AND b_int.attribute_id = ? AND b_int.store_id = 0
LEFT JOIN catalog_product_entity_varchar b_varchar ON b_varchar.entity_id = cpe.entity_id AND b_varchar.attribute_id = ? AND b_varchar.store_id = 0
WHERE cpe.sku LIKE 'TYO-%'
LIMIT 5
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$tyreSizeId, $brandId, $brandId]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
