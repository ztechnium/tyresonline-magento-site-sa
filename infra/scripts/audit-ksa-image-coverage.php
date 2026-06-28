<?php
declare(strict_types=1);
$env = include '/var/www/magento/app/etc/env.php';
$db = $env['db']['connection']['default'];
$pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['username'], $db['password']);

$total = (int)$pdo->query('SELECT COUNT(*) FROM catalog_product_entity')->fetchColumn();
$withImage = (int)$pdo->query("SELECT COUNT(DISTINCT entity_id) FROM catalog_product_entity_varchar WHERE attribute_id=(SELECT attribute_id FROM eav_attribute WHERE attribute_code='image' AND entity_type_id=4) AND store_id=0 AND value IS NOT NULL AND value != '' AND value != 'no_selection'")->fetchColumn();
$withGallery = (int)$pdo->query('SELECT COUNT(DISTINCT entity_id) FROM catalog_product_entity_media_gallery_value_to_entity')->fetchColumn();
$noImage = $total - $withImage;

echo "total_products=$total\n";
echo "with_image_attr=$withImage\n";
echo "with_gallery=$withGallery\n";
echo "without_image=$noImage\n";

$sample = $pdo->query("SELECT cpe.sku, cpe.entity_id
FROM catalog_product_entity cpe
LEFT JOIN catalog_product_entity_varchar img ON img.entity_id=cpe.entity_id
  AND img.attribute_id=(SELECT attribute_id FROM eav_attribute WHERE attribute_code='image' AND entity_type_id=4)
  AND img.store_id=0
WHERE img.value IS NULL OR img.value='' OR img.value='no_selection'
LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "sample_no_image:\n";
foreach ($sample as $r) echo "  {$r['sku']} entity={$r['entity_id']}\n";
