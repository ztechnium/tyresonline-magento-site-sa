<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$sm = $bootstrap->getObjectManager()->get('Magento\Store\Model\StoreManagerInterface');
foreach ($sm->getStores() as $store) {
    echo "store {$store->getId()} code={$store->getCode()} locale={$store->getConfig('general/locale/code')}\n";
}
$sm->setCurrentStore(2);
echo "current store 2 locale: " . $sm->getStore()->getConfig('general/locale/code') . "\n";
