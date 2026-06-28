<?php
require '/var/www/magento/app/bootstrap.php';
$objectManager = Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');

$resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resource->getConnection();
$urlFormatter = $objectManager->get(\Magento\Catalog\Model\Product\Url::class);

$rows = $connection->fetchAll(
    "SELECT brand_id, name, url_key FROM mgs_brand WHERE url_key IS NULL OR url_key = ''"
);

echo "Found " . count($rows) . " brands with empty url_key\n";

$usedKeys = $connection->fetchCol("SELECT url_key FROM mgs_brand WHERE url_key != ''");
$usedKeys = array_flip($usedKeys);

foreach ($rows as $row) {
    $baseKey = $urlFormatter->formatUrlKey($row['name']);
    if ($baseKey === '') {
        $baseKey = 'brand-' . $row['brand_id'];
    }
    $urlKey = $baseKey;
    $suffix = 1;
    while (isset($usedKeys[$urlKey])) {
        $urlKey = $baseKey . '-' . $suffix;
        $suffix++;
    }
    $usedKeys[$urlKey] = true;

    $connection->update(
        'mgs_brand',
        ['url_key' => $urlKey],
        ['brand_id = ?' => $row['brand_id']]
    );
    echo "Updated brand_id {$row['brand_id']} ({$row['name']}) => {$urlKey}\n";
}

echo "Done.\n";
