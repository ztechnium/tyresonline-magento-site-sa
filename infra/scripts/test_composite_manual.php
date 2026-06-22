<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}

$m = include '/var/www/magento/generated/metadata/frontend.php';
$providerMap = $m['arguments']['Magento\\Checkout\\Model\\CompositeConfigProvider']['configProviders']['_vac_'] ?? [];
$instances = [];
foreach ($providerMap as $name => $info) {
    $class = $info['_i_'] ?? null;
    if (!$class) {
        echo "$name=NO_CLASS\n";
        continue;
    }
    try {
        $instances[] = $om->get($class);
    } catch (Throwable $e) {
        echo "$name=FAIL: " . $e->getMessage() . "\n";
    }
}
echo 'instances=' . count($instances) . PHP_EOL;

try {
    $composite = $om->create(Magento\Checkout\Model\CompositeConfigProvider::class, [
        'configProviders' => $instances
    ]);
    echo 'manual_composite=OK' . PHP_EOL;
    $config = $composite->getConfig();
    echo 'config_keys=' . count($config) . PHP_EOL;
} catch (Throwable $e) {
    echo 'manual_composite=FAIL: ' . $e->getMessage() . PHP_EOL;
}

try {
    $om->get(Magento\Checkout\Model\CompositeConfigProvider::class);
    echo 'om_composite=OK' . PHP_EOL;
} catch (Throwable $e) {
    echo 'om_composite=FAIL: ' . $e->getMessage() . PHP_EOL;
}
