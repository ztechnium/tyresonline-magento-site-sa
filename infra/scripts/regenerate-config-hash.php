#!/usr/bin/env php
<?php
/**
 * Regenerate deploy config_hash flag so config.php sync check passes.
 */
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;

require '/var/www/magento/app/bootstrap.php';

$params = $_SERVER;
$params[Bootstrap::INIT_PARAM_FILESYSTEM_DIR_PATHS] = [];
$bootstrap = Bootstrap::create(BP, $params);
$om = $bootstrap->getObjectManager();

/** @var State $state */
$state = $om->get(State::class);
try {
    $state->setAreaCode('adminhtml');
} catch (\Exception $e) {
    // already set
}

/** @var \Magento\Deploy\Model\DeploymentConfig\Hash $hash */
$hash = $om->get(\Magento\Deploy\Model\DeploymentConfig\Hash::class);
$hash->regenerate();
echo "config_hash regenerated successfully\n";

// Flush caches
try {
    $cache = $om->get(\Magento\Framework\App\Cache\TypeListInterface::class);
    foreach ($cache->getTypes() as $type) {
        $cache->cleanType($type->getId());
    }
    echo "cache flushed\n";
} catch (\Exception $e) {
    echo "cache flush skipped: " . $e->getMessage() . "\n";
}
