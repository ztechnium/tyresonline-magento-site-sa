<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}

$m = include '/var/www/magento/generated/metadata/frontend.php';
$providers = $m['arguments']['Magento\\Checkout\\Model\\CompositeConfigProvider']['configProviders']['_vac_'] ?? [];
foreach ($providers as $name => $info) {
    $class = $info['_i_'] ?? 'unknown';
    try {
        $om->get($class);
        echo "$name=OK\n";
    } catch (Throwable $e) {
        echo "$name=FAIL ($class): " . $e->getMessage() . "\n";
    }
}
