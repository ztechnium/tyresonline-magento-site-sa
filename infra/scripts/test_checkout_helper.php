<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (Exception $e) {
}
$helper = $om->get(Hdweb\Core\Helper\Data::class);
$list = $helper->getOnestepCheckoutVehcilelist();
echo 'vehicle_count=' . count($list) . PHP_EOL;
