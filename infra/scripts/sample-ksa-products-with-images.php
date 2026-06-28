<?php
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);
$row = $pdo->query("SELECT cpe.sku, cpev.value AS url_key, img.value AS image
FROM catalog_product_entity cpe
JOIN catalog_product_entity_varchar cpev ON cpev.entity_id=cpe.entity_id AND cpev.attribute_id=(SELECT attribute_id FROM eav_attribute WHERE attribute_code='url_key' AND entity_type_id=4 LIMIT 1) AND cpev.store_id=0
JOIN catalog_product_entity_varchar img ON img.entity_id=cpe.entity_id AND img.attribute_id=(SELECT attribute_id FROM eav_attribute WHERE attribute_code='image' AND entity_type_id=4 LIMIT 1) AND img.store_id=0
WHERE img.value IS NOT NULL AND img.value != '' AND img.value != 'no_selection'
LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
foreach ($row as $r) {
    echo $r['sku'] . "\t" . $r['url_key'] . "\t" . $r['image'] . "\n";
}
