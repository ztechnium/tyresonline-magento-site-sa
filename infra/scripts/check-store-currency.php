#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();
try { $om->get(\Magento\Framework\App\State::class)->setAreaCode('frontend'); } catch (\Exception $e) {}
$sm = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
foreach ([1 => 'en', 2 => 'ar'] as $id => $code) {
    $s = $sm->getStore($id);
    echo "{$code}: currency={$s->getCurrentCurrencyCode()} base={$s->getBaseCurrencyCode()}\n";
}
