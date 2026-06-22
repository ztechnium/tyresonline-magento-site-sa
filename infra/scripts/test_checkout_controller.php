<?php
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;

require '/var/www/magento/app/bootstrap.php';

$_SERVER['REQUEST_URI'] = '/en/checkout/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'stg.tyresonline.sa';
$_SERVER['SERVER_NAME'] = 'stg.tyresonline.sa';
$_SERVER['HTTPS'] = 'on';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var State $state */
$state = $om->get(State::class);
try {
    $state->setAreaCode('frontend');
} catch (Exception $e) {
}

$storeManager = $om->get(Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');

$quoteId = 29;
$quote = $om->get(Magento\Quote\Api\CartRepositoryInterface::class)->get($quoteId);
$session = $om->get(Magento\Checkout\Model\Session::class);
$session->replaceQuote($quote);
$session->setQuoteId($quoteId);

/** @var Hdweb\Coreoverride\Controller\Checkout\Index\Index $controller */
$controller = $om->create(Hdweb\Coreoverride\Controller\Checkout\Index\Index::class);
$result = $controller->execute();

if ($result instanceof Magento\Framework\Controller\Result\Redirect) {
    echo 'REDIRECT to ' . $result->getUrl() . PHP_EOL;
    exit;
}

if ($result instanceof Magento\Framework\View\Result\Page) {
    $result->renderResult($om->get(Magento\Framework\App\Response\Http::class));
    $body = $om->get(Magento\Framework\App\Response\Http::class)->getBody();
    echo 'body_len=' . strlen($body) . PHP_EOL;
    echo 'config=' . (substr_count($body, 'checkoutConfig') ?: 0) . PHP_EOL;
    echo 'checkout_div=' . (substr_count($body, 'id="checkout"') ?: 0) . PHP_EOL;
    if (preg_match('/<title>([^<]+)<\/title>/', $body, $m)) {
        echo 'title=' . $m[1] . PHP_EOL;
    }
}
