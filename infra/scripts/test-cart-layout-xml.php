#!/usr/bin/env php
<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

$om->get(\Magento\Framework\App\AreaList::class)->getArea('frontend')->load();

/** @var \Magento\Framework\View\DesignInterface $design */
$design = $om->get(\Magento\Framework\View\DesignInterface::class);
$design->setDesignTheme('Hditsol/tyresonline', 'frontend');

$layoutFactory = $om->get(\Magento\Framework\View\LayoutFactory::class);
$layout = $layoutFactory->create(['cacheable' => false]);
$layout->getUpdate()->addHandle('default');
$layout->getUpdate()->addHandle('checkout_cart_index');
$layout->getUpdate()->load();
$layout->generateXml();
$layout->generateElements();

foreach (['checkout.cart.shipping', 'checkout.cart.totals', 'checkout.cart', 'cart.summary'] as $name) {
    $block = $layout->getBlock($name);
    echo $name . '=' . ($block ? get_class($block) : 'NULL') . PHP_EOL;
}

$xml = $layout->getUpdate()->asSimplexml();
$shippingNodes = $xml->xpath("//block[@name='checkout.cart.shipping']");
echo 'layout_has_shipping_block=' . count($shippingNodes) . PHP_EOL;
