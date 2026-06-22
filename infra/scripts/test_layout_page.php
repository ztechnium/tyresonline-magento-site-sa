<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}

$storeManager = $om->get(Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');

$config = $om->get(Ecomteck\OneStepCheckout\Helper\Config::class);
echo 'onestep=' . ($config->isEnabled() ? 'yes' : 'no') . PHP_EOL;

// Method 1: raw layout factory
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
echo 'raw_has_content=' . (strpos($xml, 'name="content"') !== false ? 'yes' : 'no') . PHP_EOL;
echo 'raw_has_checkout_root=' . (strpos($xml, 'checkout.root') !== false ? 'yes' : 'no') . PHP_EOL;

try {
    $layout->generateXml();
    $layout->generateElements();
    echo 'raw_hasElement_content=' . ($layout->hasElement('content') ? 'yes' : 'no') . PHP_EOL;
    echo 'raw_hasElement_checkout.root=' . ($layout->hasElement('checkout.root') ? 'yes' : 'no') . PHP_EOL;
    echo 'raw_block_count=' . count($layout->getAllBlocks()) . PHP_EOL;
} catch (Throwable $e) {
    echo 'raw_ERR: ' . $e->getMessage() . PHP_EOL;
}

// Method 2: Result Page like controller
$pageFactory = $om->get(Magento\Framework\View\Result\PageFactory::class);
$page = $pageFactory->create(false, ['cacheable' => false]);
$page->addHandle('checkout_index_index');
if ($config->isEnabled()) {
    $page->addHandle('onestepcheckout');
}
$pageLayout = $page->getLayout();
$pageLayout->generateXml();
$pageLayout->generateElements();
echo 'page_hasElement_content=' . ($pageLayout->hasElement('content') ? 'yes' : 'no') . PHP_EOL;
echo 'page_hasElement_checkout.root=' . ($pageLayout->hasElement('checkout.root') ? 'yes' : 'no') . PHP_EOL;
echo 'page_getBlock_checkout.root=' . ($pageLayout->getBlock('checkout.root') ? 'yes' : 'no') . PHP_EOL;
echo 'page_block_count=' . count($pageLayout->getAllBlocks()) . PHP_EOL;

$block = $pageLayout->getBlock('checkout.root');
if ($block) {
    try {
        $html = $block->toHtml();
        echo 'page_html_len=' . strlen($html) . PHP_EOL;
        echo 'page_has_checkoutConfig=' . (strpos($html, 'checkoutConfig') !== false ? 'yes' : 'no') . PHP_EOL;
    } catch (Throwable $e) {
        echo 'page_BLOCK_ERR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    }
}
