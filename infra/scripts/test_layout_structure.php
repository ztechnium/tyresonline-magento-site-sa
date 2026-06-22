<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}

$pageFactory = $om->get(Magento\Framework\View\Result\PageFactory::class);
$page = $pageFactory->create(false, ['cacheable' => false]);
$page->addHandle('checkout_index_index');
$page->addHandle('onestepcheckout');
$layout = $page->getLayout();
$layout->generateXml();

$node = $layout->getNode('checkout.root');
echo 'checkout_root_node=' . ($node ? json_encode($node->asArray()) : 'MISSING') . PHP_EOL;

$content = $layout->getNode('content');
echo 'content_node=' . ($content ? json_encode($content->asArray()) : 'MISSING') . PHP_EOL;

$main = $layout->getNode('main');
if ($main) {
    $children = [];
    foreach ($main as $child) {
        $children[] = (string)$child['name'];
    }
    echo 'main_children=' . implode(',', $children) . PHP_EOL;
}

$layout->generateElements();
echo 'after_gen_hasElement=' . ($layout->hasElement('checkout.root') ? 'yes' : 'no') . PHP_EOL;
echo 'after_gen_getBlock=' . ($layout->getBlock('checkout.root') ? 'yes' : 'no') . PHP_EOL;

// Check if block creation throws
try {
    $reader = $om->get(Magento\Framework\View\Layout\ReaderPool::class);
    echo 'reader_pool_ok' . PHP_EOL;
} catch (Throwable $e) {
    echo 'reader_err=' . $e->getMessage() . PHP_EOL;
}
