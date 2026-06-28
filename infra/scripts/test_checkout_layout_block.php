<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (Exception $e) {
}
$om->get(Magento\Store\Model\StoreManagerInterface::class)->setCurrentStore('en');

/** @var Magento\Framework\View\LayoutInterface $layout */
$layout = $om->create(Magento\Framework\View\Layout::class);
$layout->getUpdate()->addHandle('default')->addHandle('checkout_index_index')->addHandle('onestepcheckout');
$layout->generateXml();
$layout->generateElements();

$names = [];
foreach ($layout->getAllBlocks() as $name => $b) {
    $names[] = $name;
}
echo 'blocks=' . implode(',', array_slice($names, 0, 30)) . PHP_EOL;

$block = $layout->getBlock('checkout.root');
if (!$block) {
    echo "checkout.root missing\n";
    exit(1);
}

$ref = new ReflectionClass($block);
$prop = $ref->getProperty('jsLayout');
$prop->setAccessible(true);
$raw = $prop->getValue($block);
$renders = $raw['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['renders']['children'] ?? null;
echo 'raw_renders=' . (is_array($renders) ? implode(',', array_keys($renders)) : 'null') . PHP_EOL;

$processed = json_decode($block->getJsLayout(), true);
$renders2 = $processed['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['renders']['children'] ?? null;
echo 'processed_renders=' . (is_array($renders2) ? implode(',', array_keys($renders2)) : 'null') . PHP_EOL;

echo 'checkout_component=' . ($raw['components']['checkout']['component'] ?? 'missing') . PHP_EOL;
echo 'steps_displayArea=' . ($raw['components']['checkout']['children']['steps']['displayArea'] ?? 'missing') . PHP_EOL;
echo 'customer_email=' . (isset($raw['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['customer-email']) ? 'yes' : 'no') . PHP_EOL;
