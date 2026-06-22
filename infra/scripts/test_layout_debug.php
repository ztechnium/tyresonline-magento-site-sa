<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}

$env = include BP . '/app/etc/env.php';
echo 'BP=' . BP . PHP_EOL;
echo 'redis_host=' . ($env['cache']['frontend']['default']['backend_options']['server'] ?? 'MISSING') . PHP_EOL;

$config = $om->get(Ecomteck\OneStepCheckout\Helper\Config::class);
echo 'onestep=' . ($config->isEnabled() ? 'yes' : 'no') . PHP_EOL;

$layoutFactory = $om->get(Magento\Framework\View\LayoutFactory::class);
$layout = $layoutFactory->create(['cacheable' => false]);
$update = $layout->getUpdate();
$update->addHandle('default');
$update->addHandle('checkout_index_index');
if ($config->isEnabled()) {
    $update->addHandle('onestepcheckout');
}
$update->load();
$xml = $update->asString();
echo 'has_checkout_root_in_xml=' . (strpos($xml, 'checkout.root') !== false ? 'yes' : 'no') . PHP_EOL;
echo 'page_layout=' . (preg_match('/layout=\"([^\"]+)\"/', $xml, $m) ? end($m) : 'unknown') . PHP_EOL;

$layout->generateXml();
$layout->generateElements();

echo 'hasElement_checkout.root=' . ($layout->hasElement('checkout.root') ? 'yes' : 'no') . PHP_EOL;
echo 'getBlock_checkout.root=' . ($layout->getBlock('checkout.root') ? 'yes' : 'no') . PHP_EOL;

$names = [];
foreach ($layout->getAllBlocks() as $block) {
    $names[] = $block->getNameInLayout();
}
sort($names);
echo 'all_blocks=' . implode(',', $names) . PHP_EOL;

$content = $layout->getBlock('content');
echo 'content_block=' . ($content ? get_class($content) : 'null') . PHP_EOL;

if ($layout->hasElement('content')) {
    echo 'content_children=' . implode(',', array_keys((array)$layout->getChildNames('content'))) . PHP_EOL;
}
