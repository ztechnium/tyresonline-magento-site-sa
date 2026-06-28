<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$scope = $om->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
echo 'resolved_engine=' . $scope->getValue('catalog/search/engine') . PHP_EOL;
echo 'alias=' . $scope->getValue('smile_elasticsuite_core_base_settings/indices_settings/alias') . PHP_EOL;
echo 'prefix=' . $scope->getValue('catalog/search/opensearch_index_prefix') . PHP_EOL;

$resource = $om->get(\Magento\Framework\App\ResourceConnection::class);
$conn = $resource->getConnection();
$rows = $conn->fetchAll("SELECT indexer_id, status FROM indexer_state WHERE indexer_id IN ('catalogsearch_fulltext','inventory')");
foreach ($rows as $row) {
    echo 'indexer_' . $row['indexer_id'] . '=' . $row['status'] . PHP_EOL;
}
