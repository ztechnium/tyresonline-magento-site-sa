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
$state = $om->get(State::class);
try { $state->setAreaCode('frontend'); } catch (Exception $e) {}

$storeManager = $om->get(Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('en');

$quoteId = 29;
$quote = $om->get(Magento\Quote\Api\CartRepositoryInterface::class)->get($quoteId);
$session = $om->get(Magento\Checkout\Model\Session::class);
$session->replaceQuote($quote);
$session->setQuoteId($quoteId);

$controller = $om->create(Hdweb\Coreoverride\Controller\Checkout\Index\Index::class);
$result = $controller->execute();

if ($result instanceof Magento\Framework\Controller\Result\Redirect) {
    echo 'REDIRECT=' . $result->getUrl() . PHP_EOL;
    exit;
}

if ($result instanceof Magento\Framework\View\Result\Page) {
    $result->addHandle('onestepcheckout');
    $result->renderResult($om->get(Magento\Framework\App\Response\Http::class));
    $body = $om->get(Magento\Framework\App\Response\Http::class)->getBody();

    file_put_contents('/tmp/checkout_body.html', $body);
    echo 'body_len=' . strlen($body) . PHP_EOL;
    echo 'config=' . substr_count($body, 'checkoutConfig') . PHP_EOL;
    echo 'checkout_div=' . substr_count($body, 'id="checkout"') . PHP_EOL;
    echo 'checkout_root=' . substr_count($body, 'checkout.root') . PHP_EOL;
    echo 'opc=' . substr_count($body, 'opc-wrapper') . PHP_EOL;
    if (preg_match('/column main">(.*?)<\/div>\s*<\/div>\s*<\/main>/s', $body, $m)) {
        $text = strip_tags($m[1]);
        echo 'main_text=' . substr(trim($text), 0, 200) . PHP_EOL;
    }
}
