#!/usr/bin/env php
<?php
require '/var/www/magento/app/bootstrap.php';
$params = $_SERVER;
$params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = 'en';
$params[\Magento\Store\Model\StoreManager::PARAM_RUN_TYPE] = 'store';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $params);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}
$om->get(\Magento\Framework\App\AreaList::class)->getArea('frontend')->load();
$om->get(\Magento\Framework\View\DesignInterface::class)->setDesignTheme('Hditsol/tyresonline', 'frontend');

$layoutFactory = $om->get(\Magento\Framework\View\LayoutFactory::class);
$layout = $layoutFactory->create(['cacheable' => false]);
$layout->getUpdate()->addHandle('default');
$layout->getUpdate()->addHandle('checkout_cart_index');
$layout->getUpdate()->load();
$layout->generateXml();
$layout->generateElements();

$xml = $layout->getUpdate()->asSimplexml();
$nodes = $xml->xpath("//block[@name='checkout.cart.checkout_config']");
echo 'xml_has_checkout_config=' . count($nodes) . PHP_EOL;
$nodes2 = $xml->xpath("//container[@name='checkout.cart.totals.container']");
echo 'xml_has_totals_container=' . count($nodes2) . PHP_EOL;

$configBlock = $layout->getBlock('checkout.cart.checkout_config');
echo 'block=' . ($configBlock ? get_class($configBlock) : 'NULL') . PHP_EOL;

$totals = $layout->getBlock('checkout.cart.totals');
if ($totals) {
    echo 'totals parent=' . ($totals->getParentBlock() ? $totals->getParentBlock()->getNameInLayout() : 'NONE') . PHP_EOL;
}

if ($configBlock) {
    echo 'config html len=' . strlen($configBlock->toHtml()) . PHP_EOL;
}
