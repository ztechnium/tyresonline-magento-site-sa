<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}

header('Content-Type: text/plain');
$tests = [
    'ItemPoolInterface' => Magento\Checkout\CustomerData\ItemPoolInterface::class,
    'DefaultConfigProvider' => Magento\Checkout\Model\DefaultConfigProvider::class,
    'CompositeConfigProvider' => Magento\Checkout\Model\CompositeConfigProvider::class,
];
foreach ($tests as $label => $class) {
    try {
        $obj = $om->get($class);
        echo "$label=OK\n";
    } catch (Throwable $e) {
        echo "$label=FAIL: " . $e->getMessage() . "\n";
    }
}
