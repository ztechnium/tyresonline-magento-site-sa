<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}

$storeManager = $om->get(Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');

$providers = [
    'DefaultConfigProvider' => Magento\Checkout\Model\DefaultConfigProvider::class,
    'CaptchaConfigProvider' => Magento\Captcha\Model\Checkout\ConfigProvider::class,
    'CompositeConfigProvider' => Magento\Checkout\Model\CompositeConfigProvider::class,
    'Onepage' => Magento\Checkout\Block\Onepage::class,
];

foreach ($providers as $label => $class) {
    try {
        $obj = $om->get($class);
        echo "$label=OK " . get_class($obj) . PHP_EOL;
    } catch (Throwable $e) {
        echo "$label=FAIL " . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    }
}
