#!/usr/bin/env php
<?php
declare(strict_types=1);
/**
 * Point staging media URLs at origin so new mgs_brand files are served (CDN may lag).
 * Run: sudo -u www-data php infra/scripts/fix-stg-media-url.php
 */
require __DIR__ . '/../../app/bootstrap.php';
$om = Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$conn = $om->get(Magento\Framework\App\ResourceConnection::class)->getConnection();
$base = 'https://stg.tyresonline.sa/media/';
foreach (['web/unsecure/base_media_url', 'web/secure/base_media_url'] as $path) {
    foreach ([['default', 0], ['websites', 1], ['stores', 1], ['stores', 2]] as [$scope, $scopeId]) {
        $existing = $conn->fetchOne(
            'SELECT config_id FROM core_config_data WHERE path = ? AND scope = ? AND scope_id = ?',
            [$path, $scope, $scopeId]
        );
        if ($existing) {
            $conn->update('core_config_data', ['value' => $base], ['config_id = ?' => $existing]);
        } else {
            $conn->insert('core_config_data', [
                'scope' => $scope,
                'scope_id' => $scopeId,
                'path' => $path,
                'value' => $base,
            ]);
        }
        echo "Set {$scope} {$scopeId} {$path} => {$base}\n";
    }
}
echo "Done\n";
