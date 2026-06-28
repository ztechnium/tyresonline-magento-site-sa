<?php
/**
 * Simulate full HTTP checkout request through Magento front controller.
 */
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

require '/var/www/magento/app/bootstrap.php';

$_SERVER['REQUEST_URI'] = '/en/checkout/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'stg.tyresonline.sa';
$_SERVER['SERVER_NAME'] = 'stg.tyresonline.sa';
$_SERVER['HTTPS'] = 'on';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');

$quoteId = (int)($argv[1] ?? 74);
$quote = $om->get(\Magento\Quote\Api\CartRepositoryInterface::class)->get($quoteId);
$session = $om->get(\Magento\Checkout\Model\Session::class);
$session->replaceQuote($quote);
$session->setQuoteId($quoteId);

/** @var \Magento\Framework\App\Http $http */
$http = $om->get(\Magento\Framework\App\Http::class);
$response = $http->launch();
$body = $response->getBody();

file_put_contents('/tmp/http_checkout.html', $body);
echo 'len=' . strlen($body) . PHP_EOL;
echo 'div=' . substr_count($body, 'id="checkout"') . PHP_EOL;
echo 'config=' . substr_count($body, 'window.checkoutConfig') . PHP_EOL;
echo 'opc=' . substr_count($body, 'opc-wrapper') . PHP_EOL;
