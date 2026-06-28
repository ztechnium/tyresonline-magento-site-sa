#!/usr/bin/env php
<?php
declare(strict_types=1);
require '/var/www/magento/app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}

echo "=== CompositeConfigProvider class ===\n";
echo class_exists(\Hdweb\Core\Model\Checkout\CompositeConfigProvider::class) ? "EXISTS\n" : "MISSING\n";

try {
    $provider = $om->get(\Magento\Checkout\Model\CompositeConfigProvider::class);
    $config = $provider->getConfig();
    echo "checkoutConfig keys: " . count($config) . "\n";
    echo "has totals url: " . (isset($config['totals']) || isset($config['quoteData']) ? 'yes' : 'partial') . "\n";
} catch (\Throwable $e) {
    echo "CompositeConfigProvider ERROR: " . $e->getMessage() . "\n";
}

$session = $om->get(\Magento\Checkout\Model\Session::class);
$quote = $session->getQuote();
echo "Active quote id: " . ($quote->getId() ?: 'none') . " items: " . count($quote->getAllItems()) . "\n";
if ($quote->getId()) {
    $quote->collectTotals();
    echo "Subtotal: " . $quote->getSubtotal() . " Grand: " . $quote->getGrandTotal() . " " . $quote->getQuoteCurrencyCode() . "\n";
}

echo "=== Cart page HTML check ===\n";
$_SERVER['HTTP_HOST'] = 'stg.tyresonline.sa';
$_SERVER['REQUEST_URI'] = '/en/checkout/cart/';
$_SERVER['HTTPS'] = 'on';
$CJ = '/tmp/carttest_cj';
@unlink($CJ);
exec('curl -sS -c ' . escapeshellarg($CJ) . ' -b ' . escapeshellarg($CJ) . ' https://stg.tyresonline.sa/en/checkout/cart/ -o /tmp/cart_page.html');
$html = @file_get_contents('/tmp/cart_page.html') ?: '';
echo "page size: " . strlen($html) . "\n";
echo "cart-totals: " . substr_count($html, 'cart-totals') . "\n";
echo "grand totals: " . substr_count($html, 'grand.totals') . "\n";
echo "checkoutConfig: " . substr_count($html, 'checkoutConfig') . "\n";
echo "data-th=\"Subtotal\": " . substr_count($html, 'Subtotal') . "\n";
if (preg_match('/class=\"price[^\"]*\"[^>]*>([^<]+)</', $html, $m)) {
    echo "sample price: " . trim($m[1]) . "\n";
}
