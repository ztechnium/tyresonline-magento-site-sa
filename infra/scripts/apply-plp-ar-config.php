<?php
/**
 * Set Arabic price-filter button label for AR store.
 * Run: cd /var/www/magento && sudo -u www-data php infra/scripts/apply-plp-ar-config.php
 */
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
try {
    $om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');
} catch (\Exception $e) {
}

$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$path = 'mgs_ajaxnavigation/general/button_text';
$value = 'تطبيق';
$storeId = 2;

$existing = $conn->fetchOne(
    'SELECT config_id FROM core_config_data WHERE path = ? AND scope = ? AND scope_id = ?',
    [$path, 'stores', $storeId]
);

if ($existing) {
    $conn->update('core_config_data', ['value' => $value], ['config_id = ?' => $existing]);
    echo "Updated store {$storeId} {$path} = {$value}\n";
} else {
    $conn->insert('core_config_data', [
        'scope' => 'stores',
        'scope_id' => $storeId,
        'path' => $path,
        'value' => $value,
    ]);
    echo "Inserted store {$storeId} {$path} = {$value}\n";
}

echo "Done.\n";
