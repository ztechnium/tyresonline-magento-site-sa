<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

require '/var/www/magento/app/bootstrap.php';

$quoteId = (int)($argv[1] ?? 74);

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('frontend');
$om->get(\Magento\Store\Model\StoreManagerInterface::class)->setCurrentStore('en');

$quote = $om->get(\Magento\Quote\Api\CartRepositoryInterface::class)->get($quoteId);
$session = $om->get(\Magento\Checkout\Model\Session::class);
$session->replaceQuote($quote);

$pageFactory = $om->get(\Magento\Framework\View\Result\PageFactory::class);
$page = $pageFactory->create(false, ['cacheable' => false]);
$page->addHandle('checkout_index_index');
$page->addHandle('onestepcheckout');
$layout = $page->getLayout();
$layout->generateXml();
$layout->generateElements();

$block = $layout->getBlock('checkout.root');
echo 'has_block=' . ($block ? 'yes' : 'no') . PHP_EOL;

$jsLayout = $block->getJsLayout();
echo 'jsLayout_type=' . gettype($jsLayout) . PHP_EOL;
echo 'jsLayout_top_keys=' . (is_array($jsLayout) ? implode(',', array_keys($jsLayout)) : 'n/a') . PHP_EOL;

$checkout = $jsLayout['components']['checkout'] ?? null;
echo 'checkout_type=' . gettype($checkout) . PHP_EOL;
if (is_array($checkout)) {
    echo 'checkout_keys=' . implode(',', array_keys($checkout)) . PHP_EOL;
    $children = $checkout['children'] ?? null;
    if (is_array($children)) {
        echo 'checkout_children=' . implode(',', array_keys($children)) . PHP_EOL;
    }
}

try {
    $om->get(\Magento\Checkout\Block\Checkout\LayoutProcessor::class)->process($jsLayout);
    echo "process=ok\n";
} catch (\Throwable $e) {
    echo 'process_error=' . $e->getMessage() . PHP_EOL;
}

try {
    $html = $block->toHtml();
    echo 'html_len=' . strlen($html) . PHP_EOL;
    echo 'has_checkout_div=' . (strpos($html, 'id="checkout"') !== false ? 'yes' : 'no') . PHP_EOL;
} catch (\Throwable $e) {
    echo 'html_error=' . $e->getMessage() . PHP_EOL;
}
