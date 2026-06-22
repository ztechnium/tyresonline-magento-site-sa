<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}

$tests = [
    'ItemPoolInterface' => Magento\Checkout\CustomerData\ItemPoolInterface::class,
    'ItemPool' => Magento\Checkout\CustomerData\ItemPool::class,
    'CaptchaConfigProvider' => Magento\Captcha\Model\Checkout\ConfigProvider::class,
];

foreach ($tests as $label => $class) {
    try {
        $obj = $om->get($class);
        echo "$label=OK " . get_class($obj) . PHP_EOL;
    } catch (Throwable $e) {
        echo "$label=FAIL " . $e->getMessage() . PHP_EOL;
    }
}

$m = include '/var/www/magento/generated/metadata/frontend.php';
echo 'pref_ItemPool=' . ($m['preferences'][Magento\Checkout\CustomerData\ItemPoolInterface::class] ?? 'NONE') . PHP_EOL;
echo 'Captcha_formIds=' . json_encode($m['arguments']['Magento\\Captcha\\Model\\Checkout\\ConfigProvider']['formIds'] ?? null) . PHP_EOL;
