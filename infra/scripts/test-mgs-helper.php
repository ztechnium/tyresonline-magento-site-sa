<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (\Exception $e) {
}
$helper = $om->get(\MGS\Ajaxlayernavigation\Helper\Config::class);
echo 'helper_ok=1' . PHP_EOL;
echo 'ajax=' . ($helper->iaAjaxEnable() ? '1' : '0') . PHP_EOL;
