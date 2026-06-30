#!/usr/bin/env php
<?php
declare(strict_types=1);
/**
 * Restore staging base URLs (prevents redirects to www.tyresonline.sa).
 * Run: sudo -u www-data php infra/scripts/fix-stg-base-urls.php
 */
require __DIR__ . '/../../app/bootstrap.php';
$om = Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$conn = $om->get(Magento\Framework\App\ResourceConnection::class)->getConnection();

$base = 'https://stg.tyresonline.sa/';
$media = 'https://stg.tyresonline.sa/media/';
$static = 'https://cdn.tyresonline.sa/static/';

$paths = [
    'web/unsecure/base_url' => $base,
    'web/secure/base_url' => $base,
    'web/unsecure/base_link_url' => $base,
    'web/secure/base_link_url' => $base,
    'web/unsecure/base_media_url' => $media,
    'web/secure/base_media_url' => $media,
    'web/unsecure/base_static_url' => $static,
    'web/secure/base_static_url' => $static,
];

$scopes = [
    ['default', 0],
    ['websites', 1],
    ['stores', 1],
    ['stores', 2],
];

foreach ($scopes as [$scope, $scopeId]) {
    foreach ($paths as $path => $value) {
        $existing = $conn->fetchOne(
            'SELECT config_id FROM core_config_data WHERE path = ? AND scope = ? AND scope_id = ?',
            [$path, $scope, $scopeId]
        );
        if ($existing) {
            $conn->update('core_config_data', ['value' => $value], ['config_id = ?' => $existing]);
        } else {
            $conn->insert('core_config_data', [
                'scope' => $scope,
                'scope_id' => $scopeId,
                'path' => $path,
                'value' => $value,
            ]);
        }
        echo "Set {$scope}:{$scopeId} {$path} => {$value}\n";
    }
}

echo "Done\n";
