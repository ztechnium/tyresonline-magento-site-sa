<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$resource = $om->get(\Magento\Framework\App\ResourceConnection::class);
$conn = $resource->getConnection();
$rows = $conn->fetchAll(
    "SELECT path, value FROM core_config_data WHERE path LIKE '%search%' OR path LIKE '%elastic%' OR path LIKE '%opensearch%' ORDER BY path"
);
foreach ($rows as $row) {
    echo $row['path'] . '=' . $row['value'] . PHP_EOL;
}
